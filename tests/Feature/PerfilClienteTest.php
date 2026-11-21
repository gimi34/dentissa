<?php

namespace Tests\Feature;

use App\Models\Usuario;
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

    // ── CA2 · guardar el perfil ───────────────────────────────────────────────────────

    /** Datos de un perfil completo y válido. */
    private function datosDelPerfil(Usuario $usuario, array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Pablo Nuevo',
            'apellido' => 'Paciente Nuevo',
            'email' => $usuario->email,
            'telefono' => '443-555-0202',
            'fecha_nacimiento' => '1990-05-10',
            'genero' => 'masculino',
            'direccion' => 'Avenida Guardada 45',
            'ciudad' => 'Ario',
        ], $cambios);
    }

    public function test_el_paciente_guarda_su_perfil(): void
    {
        $this->actingAs($this->paciente1)->from(self::PERFIL)
            ->put(self::PERFIL, $this->datosDelPerfil($this->paciente1))
            ->assertRedirect(self::PERFIL)
            ->assertSessionHas('success', 'Tu perfil fue actualizado correctamente.');

        $usuario = DB::table('usuarios')->where('id', $this->paciente1->id)->first();
        $this->assertSame(['Pablo Nuevo', 'Paciente Nuevo', '443-555-0202'], [$usuario->nombre, $usuario->apellido, $usuario->telefono]);

        $ficha = DB::table('pacientes')->where('id', $this->p1)->first();
        $this->assertSame(['1990-05-10', 'masculino', 'Avenida Guardada 45', 'Ario'], [
            (string) $ficha->fecha_nacimiento, $ficha->genero, $ficha->direccion, $ficha->ciudad,
        ]);
        $this->assertSame($this->d1, $ficha->dentista_id, 'El dentista asignado no cambia.');
    }

    public function test_guardar_crea_la_ficha_si_no_existia(): void
    {
        $sinFicha = $this->crearUsuarioDePrueba('cliente', 'Sonia', 'Sinficha');
        $this->assertSame(0, DB::table('pacientes')->where('usuario_id', $sinFicha->id)->count());

        $this->actingAs($sinFicha)->from(self::PERFIL)
            ->put(self::PERFIL, $this->datosDelPerfil($sinFicha, ['nombre' => 'Sonia', 'apellido' => 'Sinficha']))
            ->assertRedirect(self::PERFIL)
            ->assertSessionHas('success');

        $ficha = DB::table('pacientes')->where('usuario_id', $sinFicha->id)->sole();
        $this->assertSame(['Avenida Guardada 45', 'Ario'], [$ficha->direccion, $ficha->ciudad]);
    }
}
