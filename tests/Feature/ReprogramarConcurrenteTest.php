<?php

namespace Tests\Feature;

use App\Agenda\CitaNoAgendable;
use App\Agenda\EstadosCita;
use App\Agenda\ReprogramacionCitas;
use App\Agenda\ReservaCitas;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use PDOException;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;
use Throwable;

/**
 * Dos peticiones por el mismo hueco (spec 028, CA10; TM6 y TM7).
 *
 * Los tests de bloqueo usan una segunda conexión que confirma un dentista con horario, un
 * paciente y su cita, y retiene `FOR UPDATE` sobre el dentista o sobre la cita mientras la app
 * reprograma. Esas filas confirmadas se borran en `tearDown`, después del rollback de la app.
 * Reloj fijo: lunes 2026-10-05 a las 16:00 UTC (10:00 en la clínica); las citas, el miércoles 7.
 */
class ReprogramarConcurrenteTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const NO_DISPONIBLE = 'Ese horario ya no está disponible. Por favor elige otro.';

    private const OCUPADA = 'Otra persona está cambiando esta cita en este momento. Recarga la página y vuelve a intentarlo.';

    private const NOTA_DE_PRUEBA = 'motivo-confidencial-de-prueba';

    private ?Connection $segunda = null;

    private array $confirmados = [];

    private array $mensajesLog = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->conHorarios();
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $segunda = $this->segunda;
        $confirmados = $this->confirmados;

        try {
            // 1. La segunda conexión suelta su FOR UPDATE, si lo tenía.
            if ($segunda !== null && $segunda->transactionLevel() > 0) {
                $segunda->rollBack();
            }
        } finally {
            try {
                // 2. La app deshace su transacción.
                parent::tearDown();
            } finally {
                // 3. Siempre: se borra lo que confirmó la segunda conexión, en orden inverso.
                if ($segunda !== null) {
                    foreach (array_reverse($confirmados) as [$tabla, $id]) {
                        $segunda->table($tabla)->where('id', $id)->delete();
                    }
                    $segunda->disconnect();
                }
            }
        }
    }

    // ── Bloqueo real con una segunda conexión ─────────────────────────

    public function test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible(): void
    {
        [$dentista, $cita] = $this->agendaConfirmadaPorOtraConexion();
        $this->segunda->beginTransaction();
        $this->segunda->select('select id from personal where id = ? for update', [$dentista]);
        $this->esperaDeUnSegundo();

        $respuesta = $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00');

        $this->assertNotSame(500, $respuesta->status(), 'El bloqueo agotado no debe terminar en un error del servidor.');
        $this->assertSame(self::NO_DISPONIBLE, $this->errorDe('hora'));
        $this->assertSame('2026-10-07 10:00', $this->fechaHoraDe($cita));
    }

    public function test_la_cita_bloqueada_por_un_cambio_de_estado_responde_ocupada(): void
    {
        [, $cita] = $this->agendaConfirmadaPorOtraConexion();
        $this->segunda->beginTransaction();
        $this->segunda->select('select id from citas where id = ? for update', [$cita]);
        $this->esperaDeUnSegundo();

        $respuesta = $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00');

        $this->assertNotSame(500, $respuesta->status());
        $respuesta->assertSessionHas('error', self::OCUPADA);
        $this->assertSame('2026-10-07 10:00', $this->fechaHoraDe($cita));
    }

    public function test_reprogramar_y_agendar_el_mismo_hueco_solo_guarda_uno(): void
    {
        // (a) Otra conexión confirma un agendado nuevo a las 12:00 después de la primera lectura de la app.
        [$dentista, $cita] = $this->agendaConfirmadaPorOtraConexion();
        DB::table('citas')->count();
        $this->confirmar('citas', [
            'id' => (string) Str::uuid(), 'paciente_id' => $this->pacienteConfirmado, 'dentista_id' => $dentista,
            'fecha_hora' => '2026-10-07 12:00:00', 'duracion_min' => 30, 'estado' => 'agendada',
        ]);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '12:00')->assertStatus(302);
        $this->assertSame(self::NO_DISPONIBLE, $this->errorDe('hora'));
        $this->assertSame('2026-10-07 10:00', $this->fechaHoraDe($cita), 'La cita que no se movió conserva su fecha.');

        // (b) Al revés, en la app: se reprograma primero y el agendado que llega después recibe el rechazo.
        $movida = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');
        $this->reprogramar($this->admin, $movida, '2026-10-07', '15:00')->assertSessionHasNoErrors();
        $servicio = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $servicio, 'nombre' => 'Limpieza de prueba', 'duracion_min' => 30, 'activo' => 1]);

        $this->actingAs($this->admin)->post('/admin/citas', [
            'paciente_id' => $this->p2, 'dentista_id' => $this->d1, 'servicio_id' => $servicio,
            'fecha' => '2026-10-07', 'hora' => '15:00', 'duracion_min' => 30,
        ])->assertSessionHasErrors(['hora' => self::NO_DISPONIBLE]);

        $this->assertSame(1, DB::table('citas')->where('dentista_id', $this->d1)->where('fecha_hora', '2026-10-07 15:00:00')->count());
    }

    // ── Conexión simulada ─────────────────────────────────────────────

    public function test_un_interbloqueo_se_convierte_en_no_disponible(): void
    {
        foreach ($this->erroresDeConcurrencia() as $caso => $error) {
            $this->assertSame(self::NO_DISPONIBLE, $this->rechazoConConexionSimulada($error), "Caso {$caso}.");
        }
    }

    public function test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs(): void
    {
        $this->escucharLogs();

        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-07 10:00');
        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00', self::NOTA_DE_PRUEBA)->assertSessionHasNoErrors();
        $this->rechazoConConexionSimulada($this->erroresDeConcurrencia()['QueryException 1205']);

        foreach ($this->mensajesLog as $mensaje) {
            $this->assertDoesNotMatchRegularExpression('/\b(select|insert into|update|delete from)\b/i', $mensaje, 'Un log contiene SQL.');
            $this->assertStringNotContainsString(self::NOTA_DE_PRUEBA, $mensaje, 'Un log contiene el motivo.');
        }
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private string $pacienteConfirmado;

    /**
     * Con la segunda conexión, confirma un dentista con horario el miércoles de 09:00 a 18:00,
     * un paciente y su cita del miércoles 7 a las 10:00. Todo antes de la primera lectura de la app.
     *
     * @return array{0: string, 1: string} id del dentista y de la cita
     */
    private function agendaConfirmadaPorOtraConexion(): array
    {
        $usuarioDentista = $this->confirmar('usuarios', [
            'id' => (string) Str::uuid(), 'nombre' => 'Dentista', 'apellido' => 'Concurrente', 'rol' => 'dentista',
            'email' => 'concurrente.'.Str::random(10).'@example.test', 'activo' => 1,
        ]);
        $dentista = $this->confirmar('personal', ['id' => (string) Str::uuid(), 'usuario_id' => $usuarioDentista, 'tipo' => 'dentista']);
        $this->confirmar('horarios_dentista', [
            'id' => (string) Str::uuid(), 'personal_id' => $dentista, 'dia_semana' => 3,
            'hora_inicio' => '09:00:00', 'hora_fin' => '18:00:00', 'disponible' => 1,
        ]);
        $usuarioPaciente = $this->confirmar('usuarios', [
            'id' => (string) Str::uuid(), 'nombre' => 'Paciente', 'apellido' => 'Concurrente', 'rol' => 'cliente',
            'email' => 'concurrente.'.Str::random(10).'@example.test', 'activo' => 1,
        ]);
        $this->pacienteConfirmado = $this->confirmar('pacientes', ['id' => (string) Str::uuid(), 'usuario_id' => $usuarioPaciente]);
        $cita = $this->confirmar('citas', [
            'id' => (string) Str::uuid(), 'paciente_id' => $this->pacienteConfirmado, 'dentista_id' => $dentista,
            'fecha_hora' => '2026-10-07 10:00:00', 'duracion_min' => 30, 'estado' => 'agendada',
        ]);

        return [$dentista, $cita];
    }

    /** La app espera como mucho 1 s por un bloqueo, en su sesión y en el servicio. */
    private function esperaDeUnSegundo(): void
    {
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        $this->app->bind(ReservaCitas::class, fn () => new ReservaCitas(DB::connection(), 1));
    }

    private function segundaConexion(): Connection
    {
        if ($this->segunda === null) {
            $default = config('database.default');
            config(['database.connections.segunda_reprogramacion' => config("database.connections.{$default}")]);
            $this->segunda = DB::connection('segunda_reprogramacion');
            $this->segunda->statement('SET SESSION innodb_lock_wait_timeout = 1');
        }

        return $this->segunda;
    }

    /** Inserta y confirma una fila con la segunda conexión y la apunta para borrarla en `tearDown`. */
    private function confirmar(string $tabla, array $fila): string
    {
        $this->segundaConexion()->table($tabla)->insert($fila);
        $this->confirmados[] = [$tabla, $fila['id']];

        return $fila['id'];
    }

    /** Errores de concurrencia tal como pueden llegar al servicio. */
    private function erroresDeConcurrencia(): array
    {
        $consulta = fn (int $codigo, string $texto) => new QueryException(
            'mariadb',
            'select id from personal where id = ? for update',
            ['id-de-prueba'],
            tap(new PDOException("SQLSTATE[HY000]: General error: {$codigo} {$texto}"), function (PDOException $e) use ($codigo, $texto) {
                $e->errorInfo = [$codigo === 1213 ? '40001' : 'HY000', $codigo, $texto];
            }),
        );

        $bloqueo = $consulta(1205, 'Lock wait timeout exceeded; try restarting transaction');
        $interbloqueo = $consulta(1213, 'Deadlock found when trying to get lock; try restarting transaction');

        return [
            'QueryException 1205' => $bloqueo,
            'QueryException 1213' => $interbloqueo,
            'DeadlockException' => new DeadlockException($bloqueo->getMessage(), 0, $bloqueo),
        ];
    }

    /** Llama al servicio con una conexión cuya transacción lanza el error dado y devuelve el mensaje. */
    private function rechazoConConexionSimulada(Throwable $error): string
    {
        $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
        $conexion->shouldReceive('transaction')->andThrow($error);
        $servicio = new ReprogramacionCitas($conexion, new ReservaCitas($conexion, 1), new EstadosCita($conexion));

        try {
            $servicio->reprogramar((string) Str::uuid(), '2026-10-07', '11:00', $this->admin, self::NOTA_DE_PRUEBA);
        } catch (Throwable $e) {
            $this->assertInstanceOf(CitaNoAgendable::class, $e);

            return $e->getMessage();
        }

        $this->fail('El servicio debía rechazar la reprogramación.');
    }

    private function escucharLogs(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $evento) {
            $this->mensajesLog[] = $evento->message.' '.json_encode($evento->context);
        });

        // Control: el listener capta los logs; si no, comprobar que no hay datos no probaría nada.
        Log::info('control del listener de logs');
        $this->assertNotEmpty($this->mensajesLog, 'El listener de logs no capta nada.');
        $this->mensajesLog = [];
    }
}
