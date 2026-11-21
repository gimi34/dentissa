<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Ficha 2026-11-20-fix-expediente-sin-cita (050/R14): `cita_id` es opcional en el alta de expediente,
 * también cuando la petición no trae el campo (el formulario lo envía siempre, vacío si no hay cita).
 */
class AltaExpedienteSinCitaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_el_alta_sin_el_campo_cita_id_guarda_el_expediente_sin_cita(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->dentista1)->post('/dentista/expedientes', [
            'paciente_id' => $this->p1,
            'dentista_id' => $this->d1,
            'diagnostico' => 'Alta sin el campo de la cita',
        ])->assertSessionHasNoErrors()->assertRedirect(route('dentista.pacientes.show', $this->p1));

        $this->assertDatabaseHas('expedientes', [
            'paciente_id' => $this->p1,
            'diagnostico' => 'Alta sin el campo de la cita',
            'cita_id' => null,
        ]);
    }

    public function test_el_alta_con_cita_id_vacio_sigue_guardando_el_expediente_sin_cita(): void
    {
        $this->actingAs($this->admin)->post('/admin/expedientes', [
            'paciente_id' => $this->p1,
            'dentista_id' => $this->d1,
            'cita_id' => '',
            'diagnostico' => 'Alta con la cita vacía',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.pacientes.show', $this->p1));

        $this->assertDatabaseHas('expedientes', [
            'paciente_id' => $this->p1,
            'diagnostico' => 'Alta con la cita vacía',
            'cita_id' => null,
        ]);
    }

    /** Mismo defecto en las notas internas de la cita: `notas_internas` es opcional (`nullable`). */
    public function test_guardar_notas_sin_el_campo_notas_internas_las_deja_vacias(): void
    {
        $this->withoutExceptionHandling();
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-12-01 10:00');

        $this->actingAs($this->admin)->from("/admin/citas/{$cita}")
            ->patch("/admin/citas/{$cita}/notas", [])
            ->assertSessionHasNoErrors()->assertRedirect("/admin/citas/{$cita}");

        $this->assertDatabaseHas('citas', ['id' => $cita, 'notas_internas' => null]);
    }
}
