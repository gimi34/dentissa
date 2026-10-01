<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regla de agenda única (spec 025, CA1–CA7): el agendado del personal y el del
 * paciente aplican la misma regla de horario, encaje en la franja y solape.
 *
 * Cada caso se ejecuta por los dos flujos (data set `personal` y `paciente`,
 * filtrables con `--filter`). Los datos se crean en la transacción del test:
 * un dentista con horario de lunes de 09:00 a 13:00, un paciente y un servicio
 * de 30 minutos.
 */
class ReglaAgendaTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE_SIN_HORARIO = 'El dentista no tiene horario disponible ese día de la semana.';

    private const MENSAJE_FUERA_DE_FRANJA = 'El horario elegido está fuera del horario de atención del dentista (09:00 - 13:00).';

    private const MENSAJE_NO_DISPONIBLE = 'Ese horario ya no está disponible. Por favor elige otro.';

    private Usuario $admin;

    private Usuario $usuarioPaciente;

    private string $pacienteId;

    private string $servicioId;

    private string $lunes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->crearUsuario('admin');
        $this->usuarioPaciente = $this->crearUsuario('cliente');
        $this->pacienteId = $this->crearPaciente($this->usuarioPaciente);
        $this->servicioId = $this->crearServicio('Limpieza dental');
        // Un lunes futuro: el paciente no puede agendar en fechas pasadas.
        $this->lunes = Carbon::now()->addWeeks(2)->startOfWeek()->format('Y-m-d');
    }

    public static function flujos(): array
    {
        return ['personal' => ['personal'], 'paciente' => ['paciente']];
    }

    // ── CA1 ────────────────────────────────────────────────────────────

    public function test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita(): void
    {
        $casos = [
            'sin horario' => fn (string $dentista) => null,
            'fuera de franja' => fn (string $dentista) => $this->crearHorario($dentista),
            'solape' => function (string $dentista) {
                $this->crearHorario($dentista);
                $this->crearCita($dentista, '10:00');
            },
            'válida' => fn (string $dentista) => $this->crearHorario($dentista),
        ];
        $horas = ['sin horario' => '10:00', 'fuera de franja' => '08:30', 'solape' => '10:15', 'válida' => '10:00'];

        foreach ($casos as $caso => $preparar) {
            $resultados = [];
            foreach (['personal', 'paciente'] as $flujo) {
                $dentista = $this->crearDentista();
                $preparar($dentista);
                $resultados[$flujo] = $this->resultado($this->agendar($flujo, $dentista, $horas[$caso]));
            }

            $this->assertSame($resultados['personal'], $resultados['paciente'], "Caso «{$caso}»: los dos flujos deben dar el mismo resultado.");
        }
    }

    // ── CA2 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_un_dia_sin_horario_o_no_disponible_se_rechaza(string $flujo): void
    {
        $sinHorario = $this->crearDentista();
        $this->agendar($flujo, $sinHorario, '10:00')->assertSessionHasErrors(['hora' => self::MENSAJE_SIN_HORARIO]);

        $noDisponible = $this->crearDentista();
        $this->crearHorario($noDisponible, disponible: 0);
        $this->agendar($flujo, $noDisponible, '10:00')->assertSessionHasErrors(['hora' => self::MENSAJE_SIN_HORARIO]);

        $this->assertSame(0, $this->citasDe($sinHorario) + $this->citasDe($noDisponible));
    }

    // ── CA3 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_una_cita_fuera_de_la_franja_se_rechaza_indicando_la_franja(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);

        // Empieza antes de la franja.
        $this->agendar($flujo, $dentista, '08:45')
            ->assertSessionHasErrors(['hora' => self::MENSAJE_FUERA_DE_FRANJA])
            // A28: el rechazo de la regla conserva la entrada del formulario.
            ->assertSessionHasInput('fecha', $this->lunes)
            ->assertSessionHasInput('hora', '08:45')
            ->assertSessionHasInput('dentista_id', $dentista);

        // Termina después de la franja (12:45 + 30 min = 13:15).
        $this->agendar($flujo, $dentista, '12:45')->assertSessionHasErrors(['hora' => self::MENSAJE_FUERA_DE_FRANJA]);

        $this->assertSame(0, $this->citasDe($dentista));
    }

    #[DataProvider('flujos')]
    public function test_los_bordes_exactos_de_la_franja_se_aceptan(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);

        $this->agendar($flujo, $dentista, '09:00')->assertSessionHasNoErrors();
        $this->agendar($flujo, $dentista, '12:30')->assertSessionHasNoErrors();

        $this->assertSame(2, $this->citasDe($dentista));
    }

    // ── CA4 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);
        $otroUsuario = $this->crearUsuario('cliente', ['nombre' => 'Zacarías', 'apellido' => 'Secreto']);
        $otroPaciente = $this->crearPaciente($otroUsuario);
        $servicioAjeno = $this->crearServicio('Endodoncia confidencial');
        $this->crearCita($dentista, '10:00', pacienteId: $otroPaciente, servicioId: $servicioAjeno);

        $respuesta = $this->agendar($flujo, $dentista, '10:15');

        $respuesta->assertSessionHasErrors(['hora' => self::MENSAJE_NO_DISPONIBLE]);
        $mensaje = $this->mensajeDeHora();
        $this->assertStringNotContainsString('Zacarías', $mensaje);
        $this->assertStringNotContainsString('Secreto', $mensaje);
        $this->assertStringNotContainsString('Endodoncia', $mensaje);
        $this->assertSame(1, $this->citasDe($dentista));
    }

    // ── CA5 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_una_cita_que_termina_cuando_empieza_otra_se_acepta(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);
        $this->crearCita($dentista, '10:30');

        // 10:00 + 30 min termina justo a las 10:30.
        $this->agendar($flujo, $dentista, '10:00')->assertSessionHasNoErrors();

        $this->assertSame(2, $this->citasDe($dentista));
    }

    #[DataProvider('flujos')]
    public function test_una_cita_que_empieza_cuando_termina_otra_se_acepta(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);
        $this->crearCita($dentista, '10:00');

        $this->agendar($flujo, $dentista, '10:30')->assertSessionHasNoErrors();

        $this->assertSame(2, $this->citasDe($dentista));
    }

    // ── CA6 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_las_citas_canceladas_o_no_asistio_no_bloquean_el_hueco(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);
        $this->crearCita($dentista, '10:00', estado: 'cancelada');
        $this->crearCita($dentista, '10:00', estado: 'no_asistio');

        $this->agendar($flujo, $dentista, '10:00')->assertSessionHasNoErrors();

        $this->assertSame(3, $this->citasDe($dentista));
    }

    // ── CA7 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_la_cita_de_otro_dentista_no_bloquea_el_hueco(string $flujo): void
    {
        $dentista = $this->crearDentista();
        $this->crearHorario($dentista);
        $otroDentista = $this->crearDentista();
        $this->crearHorario($otroDentista);
        $this->crearCita($otroDentista, '10:00');

        $this->agendar($flujo, $dentista, '10:00')->assertSessionHasNoErrors();

        $this->assertSame(1, $this->citasDe($dentista));
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function agendar(string $flujo, string $dentistaId, string $hora): TestResponse
    {
        $this->flushSession();

        if ($flujo === 'personal') {
            return $this->actingAs($this->admin)->post('/admin/citas', [
                'paciente_id' => $this->pacienteId,
                'dentista_id' => $dentistaId,
                'servicio_id' => $this->servicioId,
                'fecha' => $this->lunes,
                'hora' => $hora,
                'duracion_min' => 30,
            ]);
        }

        return $this->actingAs($this->usuarioPaciente)->post('/cliente/agendar', [
            'dentista_id' => $dentistaId,
            'servicio_id' => $this->servicioId,
            'fecha' => $this->lunes,
            'hora' => $hora,
        ]);
    }

    /**
     * Resultado comparable entre flujos: si se aceptó y, si no, el mensaje del campo hora.
     */
    private function resultado(TestResponse $respuesta): array
    {
        $mensaje = $this->mensajeDeHora();

        return ['aceptada' => $mensaje === '', 'mensaje' => $mensaje];
    }

    /**
     * Primer error del campo hora en la sesión. Con `session.serialization = json`
     * los errores llegan como array; en otro caso, como ViewErrorBag.
     */
    private function mensajeDeHora(): string
    {
        $errores = app('session.store')->get('errors');

        if ($errores instanceof ViewErrorBag) {
            return $errores->first('hora');
        }

        return (string) ($errores['default']['messages']['hora'][0] ?? '');
    }

    private function citasDe(string $dentistaId): int
    {
        return DB::table('citas')->where('dentista_id', $dentistaId)->count();
    }

    private function crearUsuario(string $rol, array $valores = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'agenda.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ], $valores));
    }

    private function crearDentista(): string
    {
        $usuario = $this->crearUsuario('dentista');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'dentista']);

        return $id;
    }

    private function crearHorario(string $dentistaId, int $disponible = 1): void
    {
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(),
            'personal_id' => $dentistaId,
            'dia_semana' => 1,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'disponible' => $disponible,
        ]);
    }

    private function crearPaciente(Usuario $usuario): string
    {
        $id = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $id, 'usuario_id' => $usuario->id]);

        return $id;
    }

    private function crearServicio(string $nombre): string
    {
        $id = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $id, 'nombre' => $nombre, 'duracion_min' => 30, 'activo' => 1]);

        return $id;
    }

    private function crearCita(string $dentistaId, string $hora, string $estado = 'agendada', ?string $pacienteId = null, ?string $servicioId = null): void
    {
        DB::table('citas')->insert([
            'id' => (string) Str::uuid(),
            'paciente_id' => $pacienteId ?? $this->pacienteId,
            'dentista_id' => $dentistaId,
            'servicio_id' => $servicioId,
            'fecha_hora' => $this->lunes.' '.$hora.':00',
            'duracion_min' => 30,
            'estado' => $estado,
        ]);
    }
}
