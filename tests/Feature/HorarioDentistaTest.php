<?php

namespace Tests\Feature;

use App\Agenda\HorarioDentista;
use App\Agenda\HorarioNoGuardado;
use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Mockery;
use PDOException;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;
use Throwable;

/**
 * Horario válido del dentista (spec 027, CA1–CA6, CA9 y TM1–TM5).
 *
 * El horario se envía como lo envía la ficha del personal: `formulario=horario` y
 * `dias[N][activo|hora_inicio|hora_fin]`, con N de 1 (lunes) a 7 (domingo). El reloj se
 * fija en UTC el lunes 2026-10-05 a las 17:00 (11:00 en la clínica).
 */
class HorarioDentistaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const MENSAJE_FIN_POSTERIOR = 'La hora de fin debe ser posterior a la de inicio, el mismo día.';

    private const MENSAJE_FORMATO = 'Escribe la hora con el formato HH:MM.';

    private const MENSAJE_FALTA_INICIO = 'Indica la hora de inicio de este día.';

    private const MENSAJE_FALTA_FIN = 'Indica la hora de fin de este día.';

    private const MENSAJE_NO_VALIDO = 'El horario enviado no es válido.';

    private const MENSAJE_GUARDADO = 'Horario guardado.';

    private const MENSAJE_SIN_DIAS = 'Horario guardado sin días de atención: no se podrán agendar citas con este dentista hasta que tenga horario.';

    private const MENSAJE_OCUPADO = 'Otra persona está agendando con este dentista en este momento. Vuelve a guardar el horario.';

    /** Horario previo del dentista D1: lunes de 09:00 a 13:00. */
    private const HORARIO_PREVIO = [1 => ['09:00:00', '13:00:00']];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── CA1 ────────────────────────────────────────────────────────────

    public function test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra(): void
    {
        $this->guardar($this->d1, [
            1 => ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
            3 => ['activo' => '1', 'hora_inicio' => '10:00', 'hora_fin' => '18:00'],
            // Un día con horas pero sin marcar no se guarda.
            5 => ['hora_inicio' => '08:00', 'hora_fin' => '12:00'],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success', self::MENSAJE_GUARDADO);

        $esperado = [1 => ['09:00:00', '13:00:00'], 3 => ['10:00:00', '18:00:00']];
        $this->assertSame($esperado, $this->horarioDe($this->d1));

        // La ficha muestra las horas en H:i, el formato que se valida (D6).
        $this->flushSession();
        $horario = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'horario');
        $this->assertNotNull($horario, 'La ficha de un dentista debe tener la sección horario.');
        $this->assertStringContainsString('value="09:00"', $horario);
        $this->assertStringContainsString('value="13:00"', $horario);
        $this->assertStringNotContainsString('09:00:00', $horario);

        // Al reenviar el formulario tal como lo muestra la ficha, el horario sigue igual.
        $this->flushSession();
        $this->actingAs($this->admin)
            ->post("/admin/personal/{$this->d1}/horarios", $this->datosDelFormulario($horario))
            ->assertSessionHasNoErrors();
        $this->assertSame($esperado, $this->horarioDe($this->d1));
    }

    // ── CA2 ────────────────────────────────────────────────────────────

    public function test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');

        foreach (['invertida' => [2, '18:00', '09:00'], 'cruza la medianoche' => [4, '22:00', '02:00'], 'igual' => [5, '10:00', '10:00']] as $caso => [$dia, $inicio, $fin]) {
            $this->flushSession();
            $this->guardar($this->d1, [
                1 => ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
                $dia => ['activo' => '1', 'hora_inicio' => $inicio, 'hora_fin' => $fin],
            ])->assertRedirect();

            $this->assertSame(self::MENSAJE_FIN_POSTERIOR, $this->errorDe("dias.{$dia}.hora_fin"), $caso);
            $this->assertSame(self::HORARIO_PREVIO, $this->horarioDe($this->d1), "{$caso}: el horario anterior no cambia.");

            // El mensaje se ve junto a ese día.
            $fila = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), "fila-{$dia}");
            $this->assertNotNull($fila, "{$caso}: la ficha debe tener la fila del día {$dia}.");
            $this->assertStringContainsString(self::MENSAJE_FIN_POSTERIOR, $fila, $caso);
        }
    }

    // ── CA3 (TM4) ──────────────────────────────────────────────────────

    public function test_un_horario_manipulado_se_rechaza_sin_error_500(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $valido = ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => '10:00'];

        // Caso => [datos de `dias`, ¿el resumen dice "no válido"?].
        $casos = [
            'día 8' => [[8 => $valido], true],
            'día 0' => [[0 => $valido], true],
            'hora 25:99' => [[1 => ['activo' => '1', 'hora_inicio' => '25:99', 'hora_fin' => '10:00']], false],
            'hora en texto' => [[1 => ['activo' => '1', 'hora_inicio' => 'por la mañana', 'hora_fin' => '10:00']], false],
            'lista en hora_inicio' => [[1 => ['activo' => '1', 'hora_inicio' => ['09:00'], 'hora_fin' => '10:00']], false],
            'lista en hora_fin' => [[1 => ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => ['10:00']]], false],
            'lista en activo' => [[1 => ['activo' => ['1'], 'hora_inicio' => '09:00', 'hora_fin' => '10:00']], true],
            'campo desconocido' => [[1 => $valido + ['disponible' => '0']], true],
            'dias en texto' => ['lunes', true],
            'día en texto' => [[1 => 'lunes'], true],
        ];

        foreach ($casos as $caso => [$dias, $noValido]) {
            $this->flushSession();
            $respuesta = $this->actingAs($this->admin)->post("/admin/personal/{$this->d1}/horarios", [
                'formulario' => 'horario',
                'dias' => $dias,
            ]);

            $this->assertSame(302, $respuesta->status(), "{$caso}: debe rechazarse con una redirección, no con {$respuesta->status()}.");
            $this->assertNotEmpty($this->erroresDeDias(), "{$caso}: debe haber un error de validación.");
            $this->assertSame(self::HORARIO_PREVIO, $this->horarioDe($this->d1), "{$caso}: el horario anterior no cambia.");

            // Al seguir la redirección, la ficha se muestra sin error del servidor (A22, A29).
            $html = $this->ficha($this->d1)->assertOk()->getContent();
            if ($noValido) {
                $this->assertStringContainsString(self::MENSAJE_NO_VALIDO, $this->resumen($html), $caso);
            }
        }

        // Una hora inicial mal escrita solo da su error de formato, sin un error engañoso en el fin (A5).
        $this->flushSession();
        $this->guardar($this->d1, [1 => ['activo' => '1', 'hora_inicio' => '25:99', 'hora_fin' => '10:00']]);
        $this->assertSame(self::MENSAJE_FORMATO, $this->errorDe('dias.1.hora_inicio'));
        $this->assertSame('', $this->errorDe('dias.1.hora_fin'));
    }

    // ── CA4 ────────────────────────────────────────────────────────────

    public function test_un_dia_marcado_sin_horas_se_rechaza(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');

        foreach (['sin campos' => ['activo' => '1'], 'campos vacíos' => ['activo' => '1', 'hora_inicio' => '', 'hora_fin' => '']] as $caso => $dia) {
            $this->flushSession();
            $this->guardar($this->d1, [2 => $dia])->assertRedirect();

            $this->assertSame(self::MENSAJE_FALTA_INICIO, $this->errorDe('dias.2.hora_inicio'), $caso);
            $this->assertSame(self::MENSAJE_FALTA_FIN, $this->errorDe('dias.2.hora_fin'), $caso);
            $this->assertSame(self::HORARIO_PREVIO, $this->horarioDe($this->d1), "{$caso}: no se rellenan horas por defecto.");
        }
    }

    // ── CA5 ────────────────────────────────────────────────────────────

    public function test_un_horario_sin_dias_se_guarda_con_aviso(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');

        $this->guardar($this->d1, [])->assertSessionHasNoErrors()->assertSessionHas('success', self::MENSAJE_SIN_DIAS);
        $this->assertSame([], $this->horarioDe($this->d1));

        // Con una cita futura, que queda fuera, el aviso añade el recuento (A11).
        $this->cita($this->d1, $this->p1, 'agendada', '2026-10-12 10:00');
        $this->flushSession();
        $this->guardar($this->d1, [])
            ->assertSessionHas('success', self::MENSAJE_SIN_DIAS.' 1 cita quedó fuera del horario: revísala en «Citas fuera del horario».');
    }

    // ── CA6 (TM3) ──────────────────────────────────────────────────────

    public function test_no_se_guarda_horario_para_quien_no_es_dentista(): void
    {
        [, $asistente] = $this->crearPersonal('asistente', 'Aurora', 'Ayuda');
        $administrador = $this->crearAdministrador();

        $destinos = ['asistente' => $asistente, 'administrador' => $administrador, 'id inexistente' => (string) Str::uuid()];
        foreach ($destinos as $caso => $id) {
            $this->flushSession();
            $this->guardar($id, [1 => ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => '13:00']])->assertNotFound();

            $this->assertSame(0, DB::table('horarios_dentista')->where('personal_id', $id)->count(), $caso);
        }
    }

    // ── CA9 (TM2) ──────────────────────────────────────────────────────

    public function test_solo_la_administracion_guarda_horarios(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');

        foreach (['dentista' => $this->dentista1, 'asistente' => $this->asistente, 'paciente' => $this->paciente1] as $rol => $usuario) {
            $this->flushSession();
            $this->guardar($this->d1, [2 => ['activo' => '1', 'hora_inicio' => '09:00', 'hora_fin' => '13:00']], $usuario)
                ->assertForbidden();

            $this->assertSame(self::HORARIO_PREVIO, $this->horarioDe($this->d1), "{$rol}: el horario no cambia.");
        }
    }

    // ── TM5 (servicio) ─────────────────────────────────────────────────

    public function test_el_horario_bloquea_al_dentista_antes_de_borrar(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');

        $registro = new \ArrayObject;
        Event::listen(TransactionBeginning::class, fn () => $registro->append('BEGIN'));
        DB::listen(fn ($consulta) => $registro->append($consulta->sql));

        app(HorarioDentista::class)->guardar($this->d1, [2 => ['inicio' => '10:00', 'fin' => '14:00']]);

        $consultas = $registro->getArrayCopy();
        $inicio = array_search('BEGIN', $consultas, true);
        $this->assertNotFalse($inicio, 'El guardado debe hacerse en una transacción.');
        $consultas = array_slice($consultas, $inicio + 1);

        $bloqueo = $this->posicion($consultas, '/^select .* from [`"]?personal[`"]? .*for update/i');
        $borrado = $this->posicion($consultas, '/^delete from [`"]?horarios_dentista[`"]?/i');
        $this->assertNotNull($bloqueo, 'Debe bloquear la fila del dentista en personal.');
        $this->assertNotNull($borrado, 'Debe borrar el horario anterior.');
        $this->assertLessThan($borrado, $bloqueo, 'El bloqueo del dentista va antes de borrar el horario.');

        $this->assertSame([2 => ['10:00:00', '14:00:00']], $this->horarioDe($this->d1));
        $this->assertSame(1, (int) DB::table('horarios_dentista')->where('personal_id', $this->d1)->value('disponible'));
    }

    public function test_un_bloqueo_agotado_no_da_error_500(): void
    {
        foreach ($this->erroresDeConcurrencia() as $caso => $error) {
            $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
            $conexion->shouldReceive('transaction')->andThrow($error);

            try {
                (new HorarioDentista($conexion))->guardar($this->d1, [1 => ['inicio' => '09:00', 'fin' => '13:00']]);
                $this->fail("{$caso}: el servicio debía rechazar el guardado.");
            } catch (HorarioNoGuardado $e) {
                $this->assertSame(self::MENSAJE_OCUPADO, $e->getMessage(), $caso);
            }
        }

        // Otro error de base de datos no se convierte en el mensaje.
        $previa = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');
        $previa->errorInfo = ['23000', 1062, 'Duplicate entry'];
        $otro = new QueryException('mariadb', 'insert into horarios_dentista (id) values (?)', ['id-de-prueba'], $previa);
        $conexion = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();
        $conexion->shouldReceive('transaction')->andThrow($otro);

        try {
            (new HorarioDentista($conexion))->guardar($this->d1, [1 => ['inicio' => '09:00', 'fin' => '13:00']]);
            $this->fail('El error SQL debía propagarse.');
        } catch (Throwable $e) {
            $this->assertSame($otro, $e, 'Un error SQL ajeno al bloqueo no se convierte en un mensaje.');
        }
    }

    // ── TM5 (HTTP, A13 y A23) ──────────────────────────────────────────

    public function test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $ocupado = HorarioNoGuardado::ocupado();
        $this->mock(HorarioDentista::class, function ($servicio) use ($ocupado) {
            $servicio->shouldReceive('guardar')->andThrow($ocupado);
            $servicio->shouldReceive('citasFuera')->andReturn(collect());
        });

        $this->guardar($this->d1, [1 => ['activo' => '1', 'hora_inicio' => '10:00', 'hora_fin' => '14:00']])
            ->assertRedirect()
            ->assertSessionHas('error', self::MENSAJE_OCUPADO);
        $this->assertSame(self::HORARIO_PREVIO, $this->horarioDe($this->d1));

        // La ficha muestra lo enviado, no el horario guardado: volver a guardar envía lo nuevo.
        $fila = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'fila-1');
        $this->assertNotNull($fila);
        $this->assertStringContainsString('value="10:00"', $fila);
        $this->assertStringContainsString('value="14:00"', $fila);
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /** Envía el horario como lo hace la ficha (con `formulario=horario`, A30). */
    private function guardar(string $personalId, array $dias, ?Usuario $quien = null): TestResponse
    {
        return $this->actingAs($quien ?? $this->admin)->post("/admin/personal/{$personalId}/horarios", [
            'formulario' => 'horario',
            'dias' => $dias,
        ]);
    }

    private function ficha(string $personalId): TestResponse
    {
        return $this->actingAs($this->admin)->get("/admin/personal/{$personalId}");
    }

    private function crearHorario(string $personalId, int $dia, string $inicio, string $fin): void
    {
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(),
            'personal_id' => $personalId,
            'dia_semana' => $dia,
            'hora_inicio' => $inicio.':00',
            'hora_fin' => $fin.':00',
            'disponible' => 1,
        ]);
    }

    /** @return array<int, array{0: string, 1: string}> horario por día: [inicio, fin] */
    private function horarioDe(string $personalId): array
    {
        return DB::table('horarios_dentista')->where('personal_id', $personalId)->orderBy('dia_semana')->get()
            ->mapWithKeys(fn ($h) => [(int) $h->dia_semana => [$h->hora_inicio, $h->hora_fin]])
            ->all();
    }

    /** Persona de `personal` de tipo administrador, con un usuario admin. */
    private function crearAdministrador(): string
    {
        $usuario = $this->crearUsuarioDePrueba('admin', 'Amparo', 'Gestiona');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'administrador']);

        return $id;
    }

    /** Errores de la sesión cuya clave empieza por `dias`. */
    private function erroresDeDias(): array
    {
        $errores = app('session.store')->get('errors');
        $mensajes = $errores instanceof ViewErrorBag
            ? $errores->getMessages()
            : ($errores['default']['messages'] ?? []);

        return array_filter($mensajes, fn ($clave) => str_starts_with($clave, 'dias'), ARRAY_FILTER_USE_KEY);
    }

    /** Texto del resumen `role="alert"` de la sección horario. */
    private function resumen(string $html): string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $nodo = (new DOMXPath($dom))->query("//*[@id='horario']//*[@role='alert']")->item(0);

        return $nodo === null ? '' : $nodo->textContent;
    }

    /**
     * Datos que enviaría el formulario de la sección: campos habilitados, casillas
     * marcadas y campos ocultos, sin el token CSRF.
     */
    private function datosDelFormulario(string $seccion): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$seccion);
        libxml_clear_errors();

        $pares = [];
        foreach ((new DOMXPath($dom))->query('//form//input[@name]') as $campo) {
            $nombre = $campo->getAttribute('name');
            $tipo = $campo->getAttribute('type');
            if ($nombre === '_token' || $campo->hasAttribute('disabled') || ($tipo === 'checkbox' && ! $campo->hasAttribute('checked'))) {
                continue;
            }
            $pares[] = urlencode($nombre).'='.urlencode($campo->getAttribute('value'));
        }
        parse_str(implode('&', $pares), $datos);

        return $datos;
    }

    private function posicion(array $consultas, string $patron): ?int
    {
        foreach ($consultas as $i => $sql) {
            if (preg_match($patron, $sql) === 1) {
                return $i;
            }
        }

        return null;
    }

    /** Errores de concurrencia tal como llegan al servicio (mismo criterio que la 026). */
    private function erroresDeConcurrencia(): array
    {
        $consulta = fn (int $codigo, string $texto) => new QueryException(
            'mariadb',
            'select * from personal where id = ? for update',
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
