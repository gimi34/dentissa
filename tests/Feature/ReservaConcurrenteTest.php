<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Mockery;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

/**
 * Doble reserva (spec 025, CA8; TM1 y TM2).
 *
 * Los tests de bloqueo (data set `personal` y `paciente`) usan una segunda
 * conexión que confirma un dentista con horario y retiene `FOR UPDATE` sobre su
 * fila mientras la app intenta reservar. Esa es la única escritura confirmada de
 * la suite: se borra en `tearDown`, después del rollback de la app.
 *
 * Los demás tests no usan la segunda conexión: su dentista vive en la
 * transacción de la app, así que no retienen bloqueos sobre filas ajenas.
 */
class ReservaConcurrenteTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE_NO_DISPONIBLE = 'Ese horario ya no está disponible. Por favor elige otro.';

    private const SERVICIO_RESERVA = 'App\Agenda\ReservaCitas';

    private const NOTA_DE_PRUEBA = 'nota-confidencial-de-prueba';

    private ?Connection $segunda = null;

    private array $confirmados = [];

    private array $mensajesLog = [];

    private string $lunes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lunes = Carbon::now()->addWeeks(2)->startOfWeek()->format('Y-m-d');
    }

    protected function tearDown(): void
    {
        $segunda = $this->segunda;
        $confirmados = $this->confirmados;

        // 1. La segunda conexión suelta el FOR UPDATE.
        $segunda?->rollBack();

        // 2. La app deshace su transacción.
        parent::tearDown();

        // 3. Se borra lo que confirmó la segunda conexión; ya no hay bloqueos de la app.
        if ($segunda !== null) {
            $segunda->table('horarios_dentista')->where('personal_id', $confirmados['personal'])->delete();
            $segunda->table('personal')->where('id', $confirmados['personal'])->delete();
            $segunda->table('usuarios')->where('id', $confirmados['usuario'])->delete();
            $segunda->disconnect();
        }
    }

    public static function flujos(): array
    {
        return ['personal' => ['personal'], 'paciente' => ['paciente']];
    }

    // ── (a) Bloqueo real con una segunda conexión, por flujo ───────────

    #[DataProvider('flujos')]
    public function test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista(string $flujo): void
    {
        $dentista = $this->dentistaBloqueadoPorOtraConexion();

        $respuesta = $this->agendar($flujo, $dentista, '10:00');

        $this->assertNotSame(500, $respuesta->status(), 'La reserva no debe terminar en un error del servidor.');
        $respuesta->assertSessionHasErrors(['hora' => self::MENSAJE_NO_DISPONIBLE]);
        $this->assertSame(0, DB::table('citas')->where('dentista_id', $dentista)->count());
    }

    #[DataProvider('flujos')]
    public function test_el_bloqueo_agotado_responde_no_disponible_sin_error_500(string $flujo): void
    {
        $dentista = $this->dentistaBloqueadoPorOtraConexion();
        $this->escucharLogs();

        $respuesta = $this->agendar($flujo, $dentista, '10:00');

        $this->assertNotSame(500, $respuesta->status(), 'El bloqueo agotado no debe terminar en un error del servidor.');
        $this->assertSame(self::MENSAJE_NO_DISPONIBLE, $this->mensajeDeHora());
        // A34: durante un bloqueo agotado real ningún log lleva SQL, bindings ni datos de la cita.
        $this->assertLogsSinDatos();
    }

    // ── (b) Sin segunda conexión, sin flujo ────────────────────────────

    public function test_el_bloqueo_del_dentista_se_toma_antes_de_leer_horario_y_citas(): void
    {
        $dentista = $this->crearDentistaEnLaApp();
        $consultas = $this->registrarConsultas();

        $this->agendar('personal', $dentista, '10:00')->assertSessionHasNoErrors();

        $bloqueo = $this->primeraConsulta($consultas, '/from [`"]?personal[`"]?.*for update/i');
        $horario = $this->primeraConsulta($consultas, '/from [`"]?horarios_dentista[`"]?/i');
        $citas = $this->primeraConsulta($consultas, '/select .* from [`"]?citas[`"]?/i');

        $this->assertNotNull($bloqueo, 'La reserva debe bloquear la fila del dentista con FOR UPDATE.');
        $this->assertNotNull($horario);
        $this->assertNotNull($citas);
        $this->assertLessThan($horario, $bloqueo, 'El bloqueo debe ir antes de leer el horario.');
        $this->assertLessThan($citas, $bloqueo, 'El bloqueo debe ir antes de leer las citas.');
    }

    public function test_la_reserva_fija_una_espera_de_5_segundos_por_defecto(): void
    {
        // A29: ReservaCitas resuelto del contenedor, sin sustituir.
        $dentista = $this->crearDentistaEnLaApp();
        $consultas = $this->registrarConsultas();

        $this->agendar('personal', $dentista, '10:00')->assertSessionHasNoErrors();

        $espera = $this->primeraConsulta($consultas, '/innodb_lock_wait_timeout\s*=\s*5\b/i');
        $bloqueo = $this->primeraConsulta($consultas, '/from [`"]?personal[`"]?.*for update/i');

        $this->assertNotNull($espera, 'La reserva debe fijar la espera por bloqueo en 5 s.');
        $this->assertNotNull($bloqueo);
        $this->assertLessThan($bloqueo, $espera, 'La espera se fija antes del FOR UPDATE.');
    }

    public function test_un_interbloqueo_se_convierte_en_no_disponible(): void
    {
        foreach ($this->erroresDeConcurrencia() as $caso => $error) {
            $this->assertSame(self::MENSAJE_NO_DISPONIBLE, $this->rechazoConConexionSimulada($error), "Caso {$caso}.");
        }
    }

    public function test_ni_la_reserva_ni_el_rechazo_por_bloqueo_escriben_datos_en_logs(): void
    {
        $this->escucharLogs();

        // Reserva normal por HTTP.
        $dentista = $this->crearDentistaEnLaApp();
        $this->agendar('personal', $dentista, '10:00')->assertSessionHasNoErrors();

        // Rechazo por bloqueo con la conexión simulada.
        $this->rechazoConConexionSimulada($this->erroresDeConcurrencia()['QueryException 1205']);

        $this->assertLogsSinDatos();
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /**
     * Crea y confirma, con una segunda conexión, un dentista con horario de lunes
     * y retiene FOR UPDATE sobre su fila. Todo antes de la primera lectura de la app.
     */
    private function dentistaBloqueadoPorOtraConexion(): string
    {
        $default = config('database.default');
        config(['database.connections.segunda_reserva' => config("database.connections.{$default}")]);
        $this->segunda = DB::connection('segunda_reserva');
        $this->segunda->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $usuarioId = (string) Str::uuid();
        $personalId = (string) Str::uuid();
        $this->segunda->table('usuarios')->insert([
            'id' => $usuarioId, 'nombre' => 'Dentista', 'apellido' => 'Concurrente', 'rol' => 'dentista',
            'email' => 'concurrente.'.Str::random(10).'@example.test', 'activo' => 1,
        ]);
        $this->segunda->table('personal')->insert(['id' => $personalId, 'usuario_id' => $usuarioId, 'tipo' => 'dentista']);
        $this->segunda->table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $personalId, 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00', 'disponible' => 1,
        ]);
        $this->confirmados = ['usuario' => $usuarioId, 'personal' => $personalId];

        $this->segunda->beginTransaction();
        $this->segunda->select('select id from personal where id = ? for update', [$personalId]);

        // La app espera como mucho 1 s, igual que ReservaCitas en los tests.
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        $this->app->bind(self::SERVICIO_RESERVA, fn () => new (self::SERVICIO_RESERVA)(DB::connection(), 1));

        return $personalId;
    }

    private function crearDentistaEnLaApp(): string
    {
        $usuario = $this->crearUsuario('dentista');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'dentista']);
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $id, 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00', 'disponible' => 1,
        ]);

        return $id;
    }

    /**
     * Reserva por HTTP. El paciente de la app lleva dentista_id nulo, para no
     * referenciar al dentista que retiene la segunda conexión.
     */
    private function agendar(string $flujo, string $dentistaId, string $hora): TestResponse
    {
        $usuarioPaciente = $this->crearUsuario('cliente');
        $pacienteId = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $pacienteId, 'usuario_id' => $usuarioPaciente->id, 'dentista_id' => null]);
        $servicioId = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $servicioId, 'nombre' => 'Limpieza dental', 'duracion_min' => 30, 'activo' => 1]);

        if ($flujo === 'personal') {
            return $this->actingAs($this->crearUsuario('admin'))->post('/admin/citas', [
                'paciente_id' => $pacienteId,
                'dentista_id' => $dentistaId,
                'servicio_id' => $servicioId,
                'fecha' => $this->lunes,
                'hora' => $hora,
                'duracion_min' => 30,
                'notas_paciente' => self::NOTA_DE_PRUEBA,
                'notas_internas' => self::NOTA_DE_PRUEBA,
            ]);
        }

        return $this->actingAs($usuarioPaciente)->post('/cliente/agendar', [
            'dentista_id' => $dentistaId,
            'servicio_id' => $servicioId,
            'fecha' => $this->lunes,
            'hora' => $hora,
            'notas_paciente' => self::NOTA_DE_PRUEBA,
        ]);
    }

    /**
     * Errores de concurrencia tal como pueden llegar al servicio: QueryException
     * en una transacción de nivel 1 y DeadlockException en una anidada.
     */
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

    /**
     * Llama al servicio con una conexión cuya transacción lanza el error dado y
     * devuelve el mensaje del rechazo.
     */
    private function rechazoConConexionSimulada(Throwable $error): string
    {
        $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
        $conexion->shouldReceive('transaction')->andThrow($error);
        $servicio = new (self::SERVICIO_RESERVA)($conexion, 1);

        try {
            $servicio->reservar([
                'paciente_id' => (string) Str::uuid(),
                'dentista_id' => (string) Str::uuid(),
                'servicio_id' => null,
                'fecha' => $this->lunes,
                'hora' => '10:00',
                'duracion_min' => 30,
                'notas_paciente' => self::NOTA_DE_PRUEBA,
                'notas_internas' => null,
                'creado_por' => null,
            ]);
        } catch (Throwable $e) {
            $this->assertInstanceOf('App\Agenda\CitaNoAgendable', $e);

            return $e->getMessage();
        }

        $this->fail('El servicio debía rechazar la reserva.');
    }

    private function registrarConsultas(): \ArrayObject
    {
        $consultas = new \ArrayObject;
        DB::listen(fn ($consulta) => $consultas->append($consulta->sql));

        return $consultas;
    }

    private function primeraConsulta(\ArrayObject $consultas, string $patron): ?int
    {
        foreach ($consultas as $indice => $sql) {
            if (preg_match($patron, $sql)) {
                return $indice;
            }
        }

        return null;
    }

    private function escucharLogs(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $evento) {
            $this->mensajesLog[] = $evento->message.' '.json_encode($evento->context);
        });
    }

    private function assertLogsSinDatos(): void
    {
        foreach ($this->mensajesLog as $mensaje) {
            $this->assertDoesNotMatchRegularExpression('/\b(select|insert into|update|delete from)\b/i', $mensaje, 'Un log contiene SQL.');
            $this->assertStringNotContainsString(self::NOTA_DE_PRUEBA, $mensaje, 'Un log contiene datos de la cita.');
            $this->assertStringNotContainsString('id-de-prueba', $mensaje, 'Un log contiene bindings.');
        }
    }

    private function mensajeDeHora(): string
    {
        $errores = app('session.store')->get('errors');

        if ($errores instanceof ViewErrorBag) {
            return $errores->first('hora');
        }

        return (string) ($errores['default']['messages']['hora'][0] ?? '');
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'reserva.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }
}
