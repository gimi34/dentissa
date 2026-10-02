<?php

namespace Tests\Feature;

use App\Agenda\CambioDeEstadoNoPermitido;
use App\Agenda\EstadosCita;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use PDOException;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;
use Throwable;

/**
 * Cambios de estado simultáneos sobre la misma cita (spec 026, CA10; TM4 y TM5).
 *
 * Los tests con bloqueo real usan una segunda conexión que crea y confirma todos sus
 * datos (usuarios, dentista, paciente y cita) antes de cualquier lectura de la app,
 * y los borra en `tearDown`, en orden inverso. El reloj se fija en UTC: la cita
 * empieza a las 10:00 en la clínica (16:00 UTC) y son las 17:00 UTC.
 */
class CambioEstadoConcurrenteTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const MENSAJE_OCUPADA = 'Otra persona está cambiando esta cita en este momento. Recarga la página y vuelve a intentarlo.';

    private const MENSAJE_CERRADA = 'Esta cita ya está cancelada y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.';

    private ?Connection $segunda = null;

    private array $confirmados = [];

    private ?int $esperaOriginal = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        $segunda = $this->segunda;
        $confirmados = $this->confirmados;

        try {
            // 1. La segunda conexión suelta el FOR UPDATE, si lo tenía.
            if ($segunda !== null && $segunda->transactionLevel() > 0) {
                $segunda->rollBack();
            }
            // 2. La conexión de la app recupera su espera por bloqueo.
            if ($this->esperaOriginal !== null) {
                DB::statement('SET SESSION innodb_lock_wait_timeout = '.$this->esperaOriginal);
            }
        } finally {
            try {
                // 3. La app deshace su transacción.
                Carbon::setTestNow();
                parent::tearDown();
            } finally {
                // 4. Siempre: se borra lo que confirmó la segunda conexión, en orden inverso.
                if ($segunda !== null) {
                    foreach (array_reverse($confirmados) as [$tabla, $id]) {
                        $segunda->table($tabla)->where('id', $id)->delete();
                    }
                    $segunda->disconnect();
                }
            }
        }
    }

    public function test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro(): void
    {
        [$admin, $cita] = $this->citaConfirmadaPorOtraConexion('confirmada');

        // La app ve la cita confirmada (formulario abierto antes del cambio ajeno).
        $this->assertSame('confirmada', DB::table('citas')->where('id', $cita)->value('estado'));

        // Otra persona la cancela y confirma su cambio.
        $this->segunda->table('citas')->where('id', $cita)->update(['estado' => 'cancelada']);

        $respuesta = $this->actingAs($admin)->patch("/admin/citas/{$cita}/estado", ['estado' => 'en_curso']);

        $this->assertNotSame(500, $respuesta->status());
        $this->assertSame(self::MENSAJE_CERRADA, $this->errorDe('estado'));
        // Lectura con bloqueo: ve la última versión, incluida cualquier escritura de la app.
        $this->assertSame('cancelada', DB::table('citas')->where('id', $cita)->lockForUpdate()->value('estado'));
    }

    public function test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado(): void
    {
        $this->crearEscenario();
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 10:00');

        $registro = new \ArrayObject;
        Event::listen(TransactionBeginning::class, fn () => $registro->append('BEGIN'));
        DB::listen(fn ($consulta) => $registro->append($consulta->sql));

        $this->cambiar($this->admin, $cita, 'confirmada')->assertSessionHasNoErrors();

        $inicio = array_search('BEGIN', $registro->getArrayCopy(), true);
        $this->assertNotFalse($inicio, 'El cambio de estado debe hacerse en una transacción.');

        $lecturasDeCitas = array_values(array_filter(
            array_slice($registro->getArrayCopy(), $inicio + 1),
            fn ($sql) => preg_match('/^select .* from [`"]?citas[`"]?/i', $sql) === 1,
        ));
        $this->assertNotEmpty($lecturasDeCitas, 'La transacción debe leer la cita.');
        $this->assertMatchesRegularExpression('/for update/i', $lecturasDeCitas[0], 'La primera lectura de la cita debe bloquearla.');
        $this->assertCount(1, $lecturasDeCitas, 'Dentro de la transacción no debe haber otra lectura de la cita.');
    }

    public function test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500(): void
    {
        [$admin, $cita] = $this->citaConfirmadaPorOtraConexion('agendada');

        $this->segunda->beginTransaction();
        $this->segunda->select('select id from citas where id = ? for update', [$cita]);

        $this->esperaOriginal = (int) DB::selectOne('select @@SESSION.innodb_lock_wait_timeout as espera')->espera;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        $respuesta = $this->actingAs($admin)->patch("/admin/citas/{$cita}/estado", ['estado' => 'confirmada']);

        $this->assertNotSame(500, $respuesta->status(), 'El bloqueo agotado no debe terminar en un error del servidor.');
        $this->assertSame(self::MENSAJE_OCUPADA, $this->errorDe('estado'));
        $this->assertSame('agendada', DB::table('citas')->where('id', $cita)->value('estado'));
    }

    public function test_un_interbloqueo_se_convierte_en_mensaje(): void
    {
        foreach ($this->erroresDeConcurrencia() as $caso => $error) {
            $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
            $conexion->shouldReceive('transaction')->andThrow($error);

            try {
                (new EstadosCita($conexion))->cambiar((string) Str::uuid(), 'confirmada', new Usuario(['rol' => 'admin']), null);
                $this->fail("{$caso}: el servicio debía rechazar el cambio.");
            } catch (CambioDeEstadoNoPermitido $e) {
                $this->assertSame('ocupada', $e->tipo, $caso);
                $this->assertSame(self::MENSAJE_OCUPADA, $e->getMessage(), $caso);
            }
        }
    }

    public function test_otro_error_de_base_de_datos_no_se_oculta(): void
    {
        $previa = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row');
        $previa->errorInfo = ['23000', 1452, 'Cannot add or update a child row: a foreign key constraint fails'];
        $error = new QueryException('mariadb', 'insert into historial_citas (id) values (?)', ['id-de-prueba'], $previa);

        $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
        $conexion->shouldReceive('transaction')->andThrow($error);

        try {
            (new EstadosCita($conexion))->cambiar((string) Str::uuid(), 'confirmada', new Usuario(['rol' => 'admin']), null);
            $this->fail('El error SQL debía propagarse.');
        } catch (Throwable $e) {
            $this->assertSame($error, $e, 'Un error SQL ajeno al bloqueo no se convierte en un mensaje.');
        }
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /**
     * Crea y confirma con la segunda conexión un admin, un dentista, un paciente y una
     * cita de las 10:00 en el estado dado. Devuelve el admin (cargado en la app) y la cita.
     *
     * @return array{0: Usuario, 1: string}
     */
    private function citaConfirmadaPorOtraConexion(string $estado): array
    {
        $usuario = fn (string $rol) => $this->confirmar('usuarios', [
            'id' => (string) Str::uuid(), 'nombre' => 'Concurrente', 'apellido' => ucfirst($rol), 'rol' => $rol,
            'email' => 'concurrente.'.Str::random(10).'@example.test', 'activo' => 1, 'password_hash' => 'x',
        ]);

        $adminId = $usuario('admin');
        $dentistaId = $this->confirmar('personal', ['id' => (string) Str::uuid(), 'usuario_id' => $usuario('dentista'), 'tipo' => 'dentista']);
        $pacienteId = $this->confirmar('pacientes', ['id' => (string) Str::uuid(), 'usuario_id' => $usuario('cliente')]);
        $cita = $this->confirmar('citas', [
            'id' => (string) Str::uuid(), 'paciente_id' => $pacienteId, 'dentista_id' => $dentistaId,
            'fecha_hora' => '2026-10-05 10:00:00', 'duracion_min' => 30, 'estado' => $estado,
        ]);

        return [Usuario::findOrFail($adminId), $cita];
    }

    private function segundaConexion(): Connection
    {
        if ($this->segunda === null) {
            $default = config('database.default');
            config(['database.connections.segunda_conexion' => config("database.connections.{$default}")]);
            $this->segunda = DB::connection('segunda_conexion');
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

    /**
     * Errores de concurrencia tal como pueden llegar al servicio: QueryException
     * en una transacción de nivel 1 y DeadlockException en una anidada.
     */
    private function erroresDeConcurrencia(): array
    {
        $consulta = fn (int $codigo, string $texto) => new QueryException(
            'mariadb',
            'select * from citas where id = ? for update',
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
}
