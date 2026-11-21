<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 011: perfil y contraseña del paciente.
 *
 * CA1, CA2 y TM5 caracterizan lo que ya funciona. CA6 (cambiar la contraseña cierra las otras
 * sesiones y renueva la actual) y CA7 (el personal no guarda el perfil de paciente) corrigen
 * comportamiento (plan, Estrategia de pruebas).
 */
class PerfilClienteTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;

    private const PERFIL = '/cliente/perfil';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    // ── CA1 · ver el perfil ───────────────────────────────────────────────────────────

    public function test_el_paciente_ve_sus_datos_y_su_dentista(): void
    {
        $this->paciente1->forceFill(['telefono' => '443-555-0101'])->save();
        DB::table('pacientes')->where('id', $this->p1)->update(['direccion' => 'Calle Pruebas 123']);

        $this->actingAs($this->paciente1)->get(self::PERFIL)
            ->assertOk()
            ->assertSee('value="Pablo"', false)
            ->assertSee('value="Paciente"', false)
            ->assertSee('value="'.$this->paciente1->email.'"', false)
            ->assertSee('value="443-555-0101"', false)
            ->assertSee('Calle Pruebas 123')
            ->assertSee('Dr. Diego Primero');
    }
}
