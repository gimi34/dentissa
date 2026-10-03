<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Citas que quedan fuera del horario del dentista (spec 027, CA7, CA8, TM6 y TM7).
 *
 * El reloj se fija en UTC el lunes 2026-10-05 a las 17:00 (11:00 en la clínica); las citas
 * se crean a hora local. Cada paciente tiene un nombre propio, para que la presencia o la
 * ausencia en la sección `citas-fuera` no coincida por accidente con otra cita.
 */
class CitasFueraDelHorarioTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const RECUENTO_UNA = '1 cita quedó fuera del horario: revísala en «Citas fuera del horario».';

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

    // ── CA7 ────────────────────────────────────────────────────────────

    public function test_la_ficha_lista_las_citas_futuras_fuera_del_horario(): void
    {
        $this->horarioAmplio($this->d1);
        $martes = $this->citaDe('Ana', 'Martes', '2026-10-06 10:00');
        $antes = $this->citaDe('Beto', 'Temprano', '2026-10-07 09:30', 'confirmada');
        $despues = $this->citaDe('Carla', 'Tarde', '2026-10-08 17:45');
        $dentro = $this->citaDe('Dario', 'Dentro', '2026-10-12 10:00');
        $antesDeGuardar = $this->estadoDeLasCitas();

        // Nuevo horario: sin martes, el miércoles desde las 10:00 y el jueves hasta las 18:00.
        $this->guardar($this->d1, [
            1 => $this->dia('09:00', '18:00'),
            3 => $this->dia('10:00', '18:00'),
            4 => $this->dia('09:00', '18:00'),
        ])->assertSessionHasNoErrors();

        $this->assertSame($antesDeGuardar, $this->estadoDeLasCitas(), 'Las citas no cambian al guardar el horario.');

        $this->flushSession();
        $lista = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'citas-fuera');
        $this->assertNotNull($lista, 'La ficha debe tener la sección citas-fuera.');

        foreach (['Ana Martes' => [$martes, '06/10/2026', '10:00', 'Agendada'], 'Beto Temprano' => [$antes, '07/10/2026', '09:30', 'Confirmada'], 'Carla Tarde' => [$despues, '08/10/2026', '17:45', 'Agendada']] as $paciente => [$id, $fecha, $hora, $estado]) {
            $fila = $this->filaDe($lista, $paciente);
            $this->assertNotNull($fila, "{$paciente} debe aparecer en la lista.");
            $this->assertStringContainsString($fecha, $fila, $paciente);
            $this->assertStringContainsString($hora, $fila, $paciente);
            $this->assertStringContainsString($estado, $fila, $paciente);
            $this->assertStringContainsString("/admin/citas/{$id}", $fila, "{$paciente}: enlace a su detalle.");
        }
        $this->assertStringNotContainsString('Dario Dentro', $lista, 'Una cita dentro del horario no aparece.');

        // A12: un horario con `disponible = 0` cuenta como día sin horario, como en la reserva.
        $this->crearHorario($this->d2, 5, '09:00', '18:00', disponible: 0);
        $this->crearHorario($this->d2, 1, '09:00', '18:00');
        $this->citaDe('Elena', 'Viernes', '2026-10-09 10:00', dentista: $this->d2);
        $this->citaDe('Fede', 'Lunes', '2026-10-12 10:00', dentista: $this->d2);
        $listaD2 = (string) $this->seccion($this->ficha($this->d2)->assertOk()->getContent(), 'citas-fuera');
        $this->assertStringContainsString('Elena Viernes', $listaD2, 'Un día con disponible = 0 no tiene horario.');
        $this->assertStringNotContainsString('Fede Lunes', $listaD2);
    }

    public function test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera(): void
    {
        $this->horarioAmplio($this->d1);
        $this->citaDe('Ana', 'Martes', '2026-10-06 10:00');

        $this->guardar($this->d1, [1 => $this->dia('09:00', '18:00')])
            ->assertSessionHas('success', 'Horario guardado. '.self::RECUENTO_UNA);

        $this->citaDe('Beto', 'Miércoles', '2026-10-07 10:00');
        $this->flushSession();
        $this->guardar($this->d1, [1 => $this->dia('09:00', '18:00')])
            ->assertSessionHas('success', 'Horario guardado. 2 citas quedaron fuera del horario: revísalas en «Citas fuera del horario».');
    }

    // ── CA8 ────────────────────────────────────────────────────────────

    public function test_sin_citas_fuera_no_hay_lista(): void
    {
        $this->horarioAmplio($this->d1);
        $this->citaDe('Dario', 'Dentro', '2026-10-12 10:00');

        $this->guardar($this->d1, [1 => $this->dia('09:00', '18:00')])
            ->assertSessionHas('success', 'Horario guardado.');

        $this->flushSession();
        $html = $this->ficha($this->d1)->assertOk()->getContent();
        $this->assertNotNull($this->seccion($html, 'horario'), 'La ficha del dentista se muestra con su horario.');
        $this->assertNull($this->seccion($html, 'citas-fuera'), 'Sin citas fuera no hay sección.');
    }

    public function test_las_citas_pasadas_o_cerradas_no_aparecen(): void
    {
        // Nuevo horario: solo los martes. Todas las citas siguientes caen fuera.
        $this->crearHorario($this->d1, 2, '09:00', '18:00');
        $this->citaDe('Pasada', 'Viernes', '2026-10-02 10:00');
        $this->citaDe('Hoy', 'Empezada', '2026-10-05 10:30');
        $this->citaDe('Cancelada', 'Futura', '2026-10-12 10:00', 'cancelada');
        $this->citaDe('Ausente', 'Futura', '2026-10-12 11:00', 'no_asistio');
        $this->citaDe('Completada', 'Futura', '2026-10-12 12:00', 'completada');
        $this->citaDe('Encurso', 'Futura', '2026-10-12 13:00', 'en_curso');
        // A4: una cita de control que sí debe aparecer, para que el test falle si no hay lista.
        $this->citaDe('Control', 'Visible', '2026-10-12 14:00');

        $lista = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'citas-fuera');

        $this->assertNotNull($lista, 'La ficha debe listar la cita de control.');
        $this->assertStringContainsString('Control Visible', $lista);
        foreach (['Pasada Viernes', 'Hoy Empezada', 'Cancelada Futura', 'Ausente Futura', 'Completada Futura', 'Encurso Futura'] as $paciente) {
            $this->assertStringNotContainsString($paciente, $lista, "{$paciente} no debe aparecer.");
        }
    }

    public function test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera(): void
    {
        // Mismos bordes que ReglaAgendaTest: lunes de 09:00 a 13:00 y citas de 30 minutos.
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $this->citaDe('Borde', 'Inicio', '2026-10-12 09:00');
        $this->citaDe('Borde', 'Final', '2026-10-12 12:30');
        $this->citaDe('Empieza', 'Antes', '2026-10-12 08:45');
        $this->citaDe('Termina', 'Despues', '2026-10-12 12:45');

        $lista = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'citas-fuera');

        $this->assertNotNull($lista);
        $this->assertStringContainsString('Empieza Antes', $lista);
        $this->assertStringContainsString('Termina Despues', $lista);
        $this->assertStringNotContainsString('Borde Inicio', $lista);
        $this->assertStringNotContainsString('Borde Final', $lista);
    }

    // ── TM6 ────────────────────────────────────────────────────────────

    public function test_solo_aparecen_citas_del_dentista(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $this->citaDe('Propia', 'Fuera', '2026-10-13 10:00');
        // D2 no tiene horario: su cita también está fuera, pero en su agenda, no en la de D1.
        $this->citaDe('Ajena', 'OtroDentista', '2026-10-13 10:00', dentista: $this->d2);

        $lista = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'citas-fuera');

        $this->assertNotNull($lista);
        $this->assertStringContainsString('Propia Fuera', $lista);
        $this->assertStringNotContainsString('Ajena OtroDentista', $lista);
    }

    // ── TM7 ────────────────────────────────────────────────────────────

    public function test_el_nombre_del_paciente_se_muestra_escapado(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $this->citaDe('<b>Inyeccion</b>', 'Marcada', '2026-10-13 10:00');

        $html = $this->ficha($this->d1)->assertOk()->getContent();
        $lista = $this->seccion($html, 'citas-fuera');

        $this->assertNotNull($lista);
        $this->assertStringContainsString('&lt;b&gt;Inyeccion&lt;/b&gt; Marcada', $lista);
        $this->assertStringNotContainsString('<b>Inyeccion</b>', $lista);
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function guardar(string $personalId, array $dias): TestResponse
    {
        return $this->actingAs($this->admin)->post("/admin/personal/{$personalId}/horarios", [
            'formulario' => 'horario',
            'dias' => $dias,
        ]);
    }

    private function ficha(string $personalId): TestResponse
    {
        return $this->actingAs($this->admin)->get("/admin/personal/{$personalId}");
    }

    private function dia(string $inicio, string $fin): array
    {
        return ['activo' => '1', 'hora_inicio' => $inicio, 'hora_fin' => $fin];
    }

    /** Horario de lunes a viernes de 08:00 a 20:00: ninguna cita de los tests queda fuera. */
    private function horarioAmplio(string $personalId): void
    {
        foreach (range(1, 5) as $dia) {
            $this->crearHorario($personalId, $dia, '08:00', '20:00');
        }
    }

    private function crearHorario(string $personalId, int $dia, string $inicio, string $fin, int $disponible = 1): void
    {
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(),
            'personal_id' => $personalId,
            'dia_semana' => $dia,
            'hora_inicio' => $inicio.':00',
            'hora_fin' => $fin.':00',
            'disponible' => $disponible,
        ]);
    }

    /** Cita de 30 minutos de un paciente nuevo con ese nombre; devuelve el id de la cita. */
    private function citaDe(string $nombre, string $apellido, string $inicio, string $estado = 'agendada', ?string $dentista = null): string
    {
        $dentista ??= $this->d1;
        [, $paciente] = $this->crearPacienteDePrueba($dentista, $nombre, $apellido);

        return $this->cita($dentista, $paciente, $estado, $inicio);
    }

    /** Fecha, hora, duración y estado de todas las citas, para comprobar que no cambian. */
    private function estadoDeLasCitas(): array
    {
        return DB::table('citas')->orderBy('id')->get(['id', 'fecha_hora', 'duracion_min', 'estado'])
            ->map(fn ($c) => (array) $c)->all();
    }

    /** HTML de la fila (`li`) de la lista que contiene el nombre del paciente. */
    private function filaDe(string $lista, string $paciente): ?string
    {
        foreach (preg_split('/<li[\s>]/', $lista) as $trozo) {
            if (str_contains($trozo, $paciente)) {
                return $trozo;
            }
        }

        return null;
    }
}
