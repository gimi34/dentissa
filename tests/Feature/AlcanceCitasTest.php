<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\TestCase;

/**
 * Spec 014: agendar y ver citas según el alcance clínico (CA9, CA11, CA13). El dentista y el asistente solo agendan
 * para pacientes de su alcance (fuera, 404 auditado con el paciente) y con sus dentistas; el detalle de una cita
 * ajena es un 403 del Gate `gestionar-cita`.
 */
class AlcanceCitasTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioAlcanceClinico;

    private string $lunes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenarioAlcance();
        $this->conHorarios();
        $this->lunes = Carbon::now()->addWeeks(2)->startOfWeek()->format('Y-m-d');
    }

    public function test_la_lista_de_pacientes_es_la_del_alcance(): void
    {
        $nuestros = [$this->p1, $this->p2, $this->p3, $this->p4, $this->p5];

        foreach ([[$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $ids = $this->actingAs($usuario)->get("/{$rol}/citas/crear")->assertOk()->viewData('pacientes')->pluck('id')
                ->intersect($nuestros)->sort()->values()->all();
            $this->assertSame(collect([$this->p1, $this->p3])->sort()->values()->all(), $ids, "{$rol}: P1 y P3.");
        }
    }

    public function test_la_preseleccion_ajena_se_ignora(): void
    {
        DB::table('expedientes')->where('id', $this->e3)->update(['proxima_visita' => Carbon::now()->addMonth()->toDateString()]);

        $respuesta = $this->actingAs($this->dentista1)->get("/dentista/citas/crear?paciente_id={$this->p2}")->assertOk();

        $this->assertNull($respuesta->viewData('pacienteSeleccionado'), 'Sin paciente preseleccionado.');
        $this->assertNull($respuesta->viewData('dentistaSeleccionado'), 'Sin su doctor.');
        $this->assertSame(Carbon::now()->addDay()->toDateString(), $respuesta->viewData('fechaSugerida'), 'Sin su próxima visita.');
    }

    public function test_agendar_para_un_paciente_ajeno_es_un_404_auditado(): void
    {
        foreach ([[$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->post("/{$rol}/citas", $this->cita014($this->p2))->assertNotFound();
            $this->assertDenegado('denegado_404', 'pacientes', $this->p2, "{$rol}.citas.store", $usuario->id);
        }

        $this->assertSame(0, $this->citasNuevas());
    }

    /** CA9: un paciente sin dentista ni citas no es del alcance de nadie; solo el admin lo agenda. */
    public function test_un_paciente_sin_dentista_solo_lo_agenda_el_admin(): void
    {
        $this->actingAs($this->dentista1)->post('/dentista/citas', $this->cita014($this->p5))->assertNotFound();
        $this->assertSame(0, $this->citasNuevas());

        $this->actingAs($this->admin)->post('/admin/citas', $this->cita014($this->p5))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->citasNuevas());
    }

    /** TM9, D17: con datos inválidos tampoco se distinguen (el Gate va antes de validar). */
    public function test_un_paciente_ajeno_y_uno_inexistente_responden_igual_al_agendar(): void
    {
        foreach ([$this->p2, (string) Str::uuid()] as $id) {
            $this->actingAs($this->dentista1)->post('/dentista/citas', $this->cita014($id))->assertNotFound();
            $this->actingAs($this->dentista1)->post('/dentista/citas', ['paciente_id' => $id, 'hora' => 'xx'])->assertNotFound();
        }
    }

    /** TM13, D16: un paciente que no es un UUID da 404, nunca 500. */
    public function test_agendar_con_un_paciente_mal_formado_es_un_404(): void
    {
        $sinPaciente = $this->cita014('');
        unset($sinPaciente['paciente_id']);

        foreach ([$this->cita014('no-es-un-uuid'), $sinPaciente, ['paciente_id' => ['x']] + $this->cita014('')] as $datos) {
            $this->actingAs($this->dentista1)->post('/dentista/citas', $datos)->assertNotFound();
        }

        $this->assertSame(0, $this->citasNuevas());
    }

    /** Caracterización. */
    public function test_el_admin_agenda_para_cualquiera(): void
    {
        $this->actingAs($this->admin)->post('/admin/citas', $this->cita014($this->p2))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, $this->citasNuevas());
    }

    /** Caracterización (CA13): el 403 del detalle lo da ahora el Gate, y queda auditado. */
    public function test_el_detalle_de_una_cita_ajena_se_deniega_por_gate(): void
    {
        $ajena = $this->cita($this->d2, $this->p2, 'confirmada', '2026-12-02 10:00');

        foreach ([[$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->get("/{$rol}/citas/{$ajena}")->assertForbidden()->assertDontSee('Paula');
            $this->assertDenegado('denegado', 'citas', $ajena, "{$rol}.citas.show", $usuario->id);
        }
    }

    /** Caracterización (CA13): un paciente del alcance, pero con un dentista que no es suyo. */
    public function test_agendar_con_un_dentista_ajeno_se_deniega(): void
    {
        $this->actingAs($this->dentista1)->post('/dentista/citas', $this->cita014($this->p3, $this->d2))->assertForbidden();

        $this->assertSame(0, $this->citasNuevas());
    }

    public function test_el_detalle_de_una_cita_propia_se_ve(): void
    {
        $propia = $this->cita($this->d1, $this->p1, 'confirmada', '2026-12-02 10:00');

        $this->actingAs($this->dentista1)->get("/dentista/citas/{$propia}")->assertOk()
            ->assertSee(route('dentista.pacientes.show', $this->p1), false);
    }

    /** D14: la cita cancelada de P4 es de D1, pero P4 ya no es de su alcance: sin enlace a la ficha. */
    public function test_la_cita_cancelada_no_enlaza_una_ficha_fuera_del_alcance(): void
    {
        $cancelada = DB::table('citas')->where('paciente_id', $this->p4)->where('dentista_id', $this->d1)->value('id');

        $this->actingAs($this->dentista1)->get("/dentista/citas/{$cancelada}")->assertOk()
            ->assertDontSee(route('dentista.pacientes.show', $this->p4), false);
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    private function cita014(string $paciente, ?string $dentista = null): array
    {
        return [
            'paciente_id' => $paciente, 'dentista_id' => $dentista ?? $this->d1,
            'fecha' => $this->lunes, 'hora' => '10:00', 'duracion_min' => 30, 'notas_internas' => 'Cita 014',
        ];
    }

    private function citasNuevas(): int
    {
        return DB::table('citas')->where('notas_internas', 'Cita 014')->count();
    }
}
