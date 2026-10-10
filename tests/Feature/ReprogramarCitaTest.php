<?php

namespace Tests\Feature;

use App\Agenda\CambioDeEstadoNoPermitido;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Reprogramar una cita como administración o asistente (spec 028, CA1, CA4, CA5, CA7 y CA9;
 * TM3, TM5, TM8, TM9 y TM10).
 *
 * El reloj se fija en UTC: el lunes 2026-10-05 a las 16:00 UTC son las 10:00 en la clínica
 * (America/Mexico_City, UTC−6). D1 y D2 atienden de lunes a viernes de 09:00 a 18:00 y cada
 * cita dura 30 minutos. Las fechas y horas de las citas son hora local de la clínica.
 */
class ReprogramarCitaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const MANANA = '2026-10-06 10:00';

    private const NO_DISPONIBLE = 'Ese horario ya no está disponible. Por favor elige otro.';

    private const EN_EL_PASADO = 'Elige una fecha y hora que aún no hayan pasado.';

    private const MISMA_FECHA = 'La cita ya está en esa fecha y hora. Elige otra.';

    private const YA_EMPEZO = 'Esta cita ya empezó y no se puede reprogramar.';

    private const DURACION_INVALIDA = 'La duración del servicio no es válida.';

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
        parent::tearDown();
    }

    // ── CA1 ───────────────────────────────────────────────────────────

    public function test_la_administracion_reprograma_cualquier_cita(): void
    {
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::MANANA);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')
            ->assertRedirect("/admin/citas/{$cita}");

        $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));
    }

    public function test_el_asistente_reprograma_citas_de_sus_dentistas(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $this->reprogramar($this->asistente, $cita, '2026-10-07', '11:00')
            ->assertRedirect("/asistente/citas/{$cita}");

        $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));
    }

    public function test_solo_cambian_fecha_y_hora_y_el_hueco_anterior_queda_libre(): void
    {
        $servicio = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $servicio, 'nombre' => 'Limpieza de prueba']);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        DB::table('citas')->where('id', $cita)->update(['servicio_id' => $servicio, 'duracion_min' => 45]);
        $antes = DB::table('citas')->where('id', $cita)->first(['paciente_id', 'dentista_id', 'servicio_id', 'duracion_min']);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')->assertStatus(302);

        $despues = DB::table('citas')->where('id', $cita)->first(['paciente_id', 'dentista_id', 'servicio_id', 'duracion_min']);
        $this->assertEquals($antes, $despues, 'Paciente, dentista, servicio y duración no cambian.');
        $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));

        // Otra cita del mismo dentista cabe ahora en el hueco que se liberó.
        $otra = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');
        $this->reprogramar($this->admin, $otra, '2026-10-06', '10:00')->assertStatus(302);
        $this->assertSame(self::MANANA, $this->fechaHoraDe($otra));
    }

    // ── CA4 ───────────────────────────────────────────────────────────

    public function test_aplica_la_regla_de_agenda_al_nuevo_hueco(): void
    {
        $ocupada = $this->cita($this->d1, $this->p2, 'confirmada', '2026-10-07 10:00');
        $this->cita($this->d1, $this->p2, 'cancelada', '2026-10-07 11:00');
        $this->cita($this->d1, $this->p2, 'no_asistio', '2026-10-07 12:00');
        $this->cita($this->d2, $this->p2, 'agendada', '2026-10-07 13:00');

        $rechazos = [
            'día sin horario (sábado)' => ['2026-10-10', '10:00', 'El dentista no tiene horario disponible ese día de la semana.'],
            'antes de la franja' => ['2026-10-07', '08:30', 'El horario elegido está fuera del horario de atención del dentista (09:00 - 18:00).'],
            'termina después de la franja' => ['2026-10-07', '17:45', 'El horario elegido está fuera del horario de atención del dentista (09:00 - 18:00).'],
            'solape con otra cita' => ['2026-10-07', '10:15', self::NO_DISPONIBLE],
        ];
        foreach ($rechazos as $caso => [$fecha, $hora, $mensaje]) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
            $this->reprogramar($this->admin, $cita, $fecha, $hora)->assertStatus(302);
            $this->assertSame($mensaje, $this->errorDe('hora'), $caso);
            $this->assertSame(self::MANANA, $this->fechaHoraDe($cita), "{$caso}: la cita no cambia.");
        }

        $aceptados = [
            'seguida, justo después' => ['2026-10-07', '10:30'],
            'seguida, justo antes' => ['2026-10-07', '09:30'],
            'sobre una cancelada' => ['2026-10-07', '11:00'],
            'sobre una no asistida' => ['2026-10-07', '12:00'],
            'cita de otro dentista a la misma hora' => ['2026-10-07', '13:00'],
            'al inicio de la franja' => ['2026-10-08', '09:00'],
            'terminando al final de la franja' => ['2026-10-08', '17:30'],
        ];
        foreach ($aceptados as $caso => [$fecha, $hora]) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-09 15:00');
            $this->reprogramar($this->admin, $cita, $fecha, $hora)->assertStatus(302)->assertSessionHasNoErrors();
            $this->assertSame("{$fecha} {$hora}", $this->fechaHoraDe($cita), $caso);
        }

        $this->assertSame('2026-10-07 10:00', $this->fechaHoraDe($ocupada));
    }

    public function test_la_propia_cita_no_cuenta_como_conflicto(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-07 10:00');

        $this->reprogramar($this->admin, $cita, '2026-10-07', '10:15')->assertSessionHasNoErrors();

        $this->assertSame('2026-10-07 10:15', $this->fechaHoraDe($cita));
    }

    // ── Casos que reprogramar también rechaza (/review 028: R7, R9, R10) ──

    public function test_no_se_reprograma_la_cita_de_un_dentista_inactivo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        DB::table('usuarios')->where('id', $this->dentista1->id)->update(['activo' => 0]);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')->assertStatus(302);

        $this->assertSame(self::NO_DISPONIBLE, $this->errorDe('hora'));
        $this->assertSame(self::MANANA, $this->fechaHoraDe($cita));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
    }

    public function test_la_misma_hora_con_segundos_se_rechaza_como_igual(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::MANANA);
        DB::table('citas')->where('id', $cita)->update(['fecha_hora' => '2026-10-06 10:00:30']);

        $this->reprogramar($this->admin, $cita, '2026-10-06', '10:00')->assertStatus(302);

        $this->assertSame(self::MISMA_FECHA, $this->errorDe('hora'));
        $this->assertSame('2026-10-06 10:00:30', (string) DB::table('citas')->where('id', $cita)->value('fecha_hora'));
        $this->assertSame('confirmada', $this->estadoDe($cita));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
    }

    public function test_una_cita_sin_duracion_no_se_reprograma(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        DB::table('citas')->where('id', $cita)->update(['duracion_min' => 0]);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')->assertStatus(302);

        $this->assertSame(self::DURACION_INVALIDA, $this->errorDe('hora'));
        $this->assertSame(self::MANANA, $this->fechaHoraDe($cita));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
    }

    public function test_una_fecha_pasada_o_igual_se_rechaza(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $this->reprogramar($this->admin, $cita, '2026-10-05', '09:00')->assertStatus(302);
        $this->assertSame(self::EN_EL_PASADO, $this->errorDe('hora'));

        $this->reprogramar($this->admin, $cita, '2026-10-06', '10:00')->assertStatus(302);
        $this->assertSame(self::MISMA_FECHA, $this->errorDe('hora'));

        $this->assertSame(self::MANANA, $this->fechaHoraDe($cita));
    }

    public function test_la_nueva_hora_se_interpreta_en_la_zona_de_la_clinica(): void
    {
        // Son las 10:00 en la clínica (16:00 UTC): las 12:00 locales aún no llegan; las 09:59 ya pasaron.
        $aceptada = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $this->reprogramar($this->admin, $aceptada, '2026-10-05', '12:00')->assertSessionHasNoErrors();
        $this->assertSame('2026-10-05 12:00', $this->fechaHoraDe($aceptada));

        $rechazada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');
        $this->reprogramar($this->admin, $rechazada, '2026-10-05', '09:59')->assertStatus(302);
        $this->assertSame(self::EN_EL_PASADO, $this->errorDe('hora'));
        $this->assertSame('2026-10-08 10:00', $this->fechaHoraDe($rechazada));
    }

    // ── CA5 ───────────────────────────────────────────────────────────

    public function test_una_confirmada_vuelve_a_agendada(): void
    {
        $confirmada = $this->cita($this->d1, $this->p1, 'confirmada', self::MANANA);
        $agendada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');

        $this->reprogramar($this->admin, $confirmada, '2026-10-07', '11:00')->assertSessionHasNoErrors();
        $this->reprogramar($this->admin, $agendada, '2026-10-07', '12:00')->assertSessionHasNoErrors();

        $this->assertSame('agendada', $this->estadoDe($confirmada));
        $this->assertSame('agendada', $this->estadoDe($agendada));
    }

    public function test_una_cita_cerrada_o_empezada_no_se_reprograma(): void
    {
        foreach (['completada', 'cancelada', 'no_asistio'] as $estado) {
            $cita = $this->cita($this->d1, $this->p1, $estado, self::MANANA);
            $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')
                ->assertStatus(302)
                ->assertSessionHas('error', CambioDeEstadoNoPermitido::cerrada($estado)->getMessage());
            $this->assertSame(self::MANANA, $this->fechaHoraDe($cita), $estado);
            $this->assertSame($estado, $this->estadoDe($cita), $estado);
        }

        $enCurso = $this->cita($this->d1, $this->p1, 'en_curso', '2026-10-05 09:45');
        $empezada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30');
        foreach (['en curso' => $enCurso, 'agendada ya empezada' => $empezada] as $caso => $cita) {
            $antes = $this->fechaHoraDe($cita);
            $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00')
                ->assertStatus(302)
                ->assertSessionHas('error', self::YA_EMPEZO);
            $this->assertSame($antes, $this->fechaHoraDe($cita), $caso);
        }
    }

    // ── CA7 ───────────────────────────────────────────────────────────

    public function test_la_reprogramacion_deja_una_sola_entrada_de_historial(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::MANANA);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00', 'Lo pidió el paciente')->assertSessionHasNoErrors();

        $entradas = $this->entradasDeHistorial($cita);
        $this->assertCount(1, $entradas);
        $entrada = $entradas->first();
        $this->assertSame($this->admin->id, $entrada->cambiado_por);
        $this->assertSame('confirmada', $entrada->estado_anterior);
        $this->assertSame('agendada', $entrada->estado_nuevo);
        $this->assertSame('2026-10-06 10:00:00', (string) $entrada->fecha_hora_anterior);
        $this->assertSame('2026-10-07 11:00:00', (string) $entrada->fecha_hora_nueva);
        $this->assertSame('Lo pidió el paciente', $entrada->motivo);
        $this->assertNotNull($entrada->fecha);
    }

    public function test_un_rechazo_no_deja_historial(): void
    {
        $this->cita($this->d1, $this->p2, 'agendada', '2026-10-07 10:00');
        $abierta = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $cerrada = $this->cita($this->d1, $this->p1, 'completada', self::MANANA);

        $this->reprogramar($this->admin, $abierta, '2026-10-07', '10:00', 'Solape')->assertStatus(302);
        $this->reprogramar($this->admin, $abierta, '2026-10-05', '09:00', 'Pasada')->assertStatus(302);
        $this->reprogramar($this->admin, $cerrada, '2026-10-07', '12:00', 'Cerrada')->assertStatus(302);
        $this->reprogramar($this->admin, $abierta, 'no-es-fecha', '10:00', 'Mal formada')->assertStatus(302);

        $this->assertCount(0, $this->entradasDeHistorial($abierta));
        $this->assertCount(0, $this->entradasDeHistorial($cerrada));
        $this->assertSame(self::MANANA, $this->fechaHoraDe($abierta));
    }

    // ── CA9 y TM3, TM5, TM8 ───────────────────────────────────────────

    public function test_los_campos_anadidos_a_mano_no_cambian_la_cita(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::MANANA);
        $antes = DB::table('citas')->where('id', $cita)->first(['paciente_id', 'dentista_id', 'servicio_id', 'duracion_min', 'notas_internas']);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00', null, [
            'dentista_id' => $this->d2,
            'paciente_id' => $this->p2,
            'servicio_id' => (string) Str::uuid(),
            'duracion_min' => 120,
            'estado' => 'completada',
            'notas_internas' => 'Cambiado a mano',
        ])->assertSessionHasNoErrors();

        $despues = DB::table('citas')->where('id', $cita)->first(['paciente_id', 'dentista_id', 'servicio_id', 'duracion_min', 'notas_internas']);
        $this->assertEquals($antes, $despues);
        $this->assertSame('agendada', $this->estadoDe($cita), 'Solo cambia por la regla (confirmada → agendada).');
        $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));
    }

    public function test_una_fecha_u_hora_mal_formada_da_error_de_validacion(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $formulario = "/admin/citas/{$cita}/reprogramar";

        $casos = [
            ['fecha' => '06/10/2026', 'hora' => '10:00', 'campo' => 'fecha'],
            ['fecha' => '2026-13-01', 'hora' => '10:00', 'campo' => 'fecha'],
            ['fecha' => ['2026-10-07'], 'hora' => '10:00', 'campo' => 'fecha'],
            ['fecha' => '2026-10-07', 'hora' => '9am', 'campo' => 'hora'],
            ['fecha' => '2026-10-07', 'hora' => '25:00', 'campo' => 'hora'],
            ['fecha' => '2026-10-07', 'hora' => ['10:00'], 'campo' => 'hora'],
        ];
        foreach ($casos as $caso) {
            $this->actingAs($this->admin)->from($formulario)
                ->patch($formulario, ['fecha' => $caso['fecha'], 'hora' => $caso['hora']])
                ->assertRedirect($formulario);
            // El error se lee antes del GET (R3): después ya no está en la sesión, y `assertSessionHasErrors`
            // aquí haría que el GET no lo recibiera.
            $mensaje = $this->errorDe($caso['campo']);
            $this->assertNotSame('', $mensaje, "Debe haber un error en {$caso['campo']}.");

            // A34: al volver al formulario con la entrada anterior (también listas), nunca hay un 500.
            $this->actingAs($this->admin)->get($formulario)
                ->assertOk()
                ->assertSee($mensaje);
        }

        $this->assertSame(self::MANANA, $this->fechaHoraDe($cita));
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $this->reprogramar($this->admin, $cita, '', '10:00');
        $this->assertSame('Elige una fecha.', $this->errorDe('fecha'));

        $this->reprogramar($this->admin, $cita, '2026-10-07', '');
        $this->assertSame('Elige una hora.', $this->errorDe('hora'));

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00', null, ['motivo' => ['lista']]);
        $this->assertSame('El motivo debe ser un texto.', $this->errorDe('motivo'));

        $this->reprogramar($this->admin, $cita, '2026-10-07', '11:00', str_repeat('a', 501));
        $this->assertSame('El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.', $this->errorDe('motivo'));

        $this->assertSame(self::MANANA, $this->fechaHoraDe($cita));
    }

    public function test_el_hueco_ocupado_no_revela_la_otra_cita(): void
    {
        $this->cita($this->d1, $this->p2, 'agendada', '2026-10-07 10:00');
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $this->reprogramar($this->admin, $cita, '2026-10-07', '10:00')->assertStatus(302);

        $this->assertSame(self::NO_DISPONIBLE, $this->errorDe('hora'));
        $this->assertStringNotContainsString('Paula', $this->errorDe('hora'));
    }
}
