<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Ficha docs/changes/2026-11-17-fix-admin-no-se-desactiva.md (RS19.a de docs/security.md): un admin
 * no puede desactivarse ni quitarse el rol de administrador a sí mismo, por ninguna de las cuatro
 * vías (desactivar en usuarios y en personal, editar su usuario y editar su ficha del personal).
 * Puede seguir haciéndolo con otro admin y editando sus propios datos sin tocar el rol ni «Activo».
 */
class AdminNoSeDesactivaTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;

    private const NO_DESACTIVAR = 'No puedes desactivar tu propia cuenta.';

    private const NO_QUITAR_ROL = 'No puedes quitarte el rol de administrador.';

    /** Id de la ficha de personal del admin del escenario. */
    private string $fichaAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->fichaAdmin = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $this->fichaAdmin, 'usuario_id' => $this->admin->id, 'tipo' => 'administrador']);
    }

    private function assertSigueSiendoAdminActivo(Usuario $usuario): void
    {
        $fila = DB::table('usuarios')->where('id', $usuario->id)->first(['rol', 'activo']);
        $this->assertSame('admin', $fila->rol, 'Conserva el rol de administrador.');
        $this->assertSame(1, (int) $fila->activo, 'Sigue activo.');
    }

    /** Lo que envía el formulario de edición de su usuario, con la casilla «Activo» marcada. */
    private function datosDeSuUsuario(array $cambios = []): array
    {
        return array_merge([
            'nombre' => $this->admin->nombre,
            'apellido' => $this->admin->apellido,
            'email' => $this->admin->email,
            'rol' => 'admin',
            'activo' => '1',
        ], $cambios);
    }

    /** Lo que envía el formulario de edición de su ficha del personal. */
    private function datosDeSuFicha(array $cambios = []): array
    {
        return array_merge([
            'nombre' => $this->admin->nombre,
            'apellido' => $this->admin->apellido,
            'email' => $this->admin->email,
            'tipo' => 'administrador',
        ], $cambios);
    }

    public function test_no_puede_desactivarse_desde_el_listado_de_usuarios(): void
    {
        $this->actingAs($this->admin)->from(route('admin.usuarios.index'))
            ->patch(route('admin.usuarios.toggle', $this->admin->id))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('error', self::NO_DESACTIVAR)
            ->assertSessionMissing('success');

        $this->assertSigueSiendoAdminActivo($this->admin);
    }

    public function test_no_puede_desmarcar_activo_al_editar_su_usuario(): void
    {
        $datos = $this->datosDeSuUsuario();
        unset($datos['activo']);

        $this->actingAs($this->admin)->from(route('admin.usuarios.edit', $this->admin->id))
            ->put(route('admin.usuarios.update', $this->admin->id), $datos)
            ->assertRedirect(route('admin.usuarios.edit', $this->admin->id))
            ->assertSessionHasErrors(['activo' => self::NO_DESACTIVAR]);

        $this->assertSigueSiendoAdminActivo($this->admin);
    }

    public function test_no_puede_cambiarse_el_rol_al_editar_su_usuario(): void
    {
        $this->actingAs($this->admin)->from(route('admin.usuarios.edit', $this->admin->id))
            ->put(route('admin.usuarios.update', $this->admin->id), $this->datosDeSuUsuario(['rol' => 'dentista']))
            ->assertRedirect(route('admin.usuarios.edit', $this->admin->id))
            ->assertSessionHasErrors(['rol' => self::NO_QUITAR_ROL]);

        $this->assertSigueSiendoAdminActivo($this->admin);
    }

    public function test_no_puede_desactivarse_desde_su_ficha_del_personal(): void
    {
        $this->actingAs($this->admin)->from(route('admin.personal.show', $this->fichaAdmin))
            ->patch(route('admin.personal.toggle', $this->fichaAdmin))
            ->assertRedirect(route('admin.personal.show', $this->fichaAdmin))
            ->assertSessionHas('error', self::NO_DESACTIVAR)
            ->assertSessionMissing('success');

        $this->assertSigueSiendoAdminActivo($this->admin);
    }

    public function test_no_puede_cambiarse_el_tipo_al_editar_su_ficha_del_personal(): void
    {
        $this->actingAs($this->admin)->from(route('admin.personal.edit', $this->fichaAdmin))
            ->put(route('admin.personal.update', $this->fichaAdmin), $this->datosDeSuFicha(['tipo' => 'dentista']))
            ->assertRedirect(route('admin.personal.edit', $this->fichaAdmin))
            ->assertSessionHasErrors(['tipo' => self::NO_QUITAR_ROL]);

        $this->assertSigueSiendoAdminActivo($this->admin);
        $this->assertSame('administrador', DB::table('personal')->where('id', $this->fichaAdmin)->value('tipo'));
    }

    /** Review ligera (M1): con ficha de paciente (p. ej. creada desde el perfil), tampoco desde Pacientes. */
    public function test_no_puede_desactivarse_desde_su_ficha_de_paciente(): void
    {
        $fichaPaciente = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $fichaPaciente, 'usuario_id' => $this->admin->id, 'dentista_id' => $this->d1]);

        $this->actingAs($this->admin)->from(route('admin.pacientes'))
            ->patch(route('admin.pacientes.toggle', $fichaPaciente))
            ->assertRedirect(route('admin.pacientes'))
            ->assertSessionHas('error', self::NO_DESACTIVAR)
            ->assertSessionMissing('success');

        $this->assertSigueSiendoAdminActivo($this->admin);
    }

    public function test_puede_editar_sus_datos_sin_tocar_el_rol_ni_activo(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.usuarios.update', $this->admin->id), $this->datosDeSuUsuario(['nombre' => 'Adelina']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Adelina', DB::table('usuarios')->where('id', $this->admin->id)->value('nombre'), 'La edición del usuario se guardó.');
        $this->actingAs($this->admin)
            ->put(route('admin.personal.update', $this->fichaAdmin), $this->datosDeSuFicha(['nombre' => 'Adelina', 'apellido' => 'Administradora']))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.personal.show', $this->fichaAdmin));

        $this->assertSigueSiendoAdminActivo($this->admin);
        $fila = DB::table('usuarios')->where('id', $this->admin->id)->first(['nombre', 'apellido']);
        $this->assertSame(['Adelina', 'Administradora'], [$fila->nombre, $fila->apellido]);
    }

    public function test_puede_desactivar_y_quitar_el_rol_a_otro_admin(): void
    {
        $otro = Usuario::create([
            'nombre' => 'Otra', 'apellido' => 'Administra', 'email' => 'otra.admin.'.Str::random(8).'@example.test',
            'rol' => 'admin', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);

        $this->actingAs($this->admin)->from(route('admin.usuarios.index'))
            ->patch(route('admin.usuarios.toggle', $otro->id))
            ->assertSessionHas('success', 'Usuario desactivado.');
        $this->assertSame(0, (int) DB::table('usuarios')->where('id', $otro->id)->value('activo'));

        $this->actingAs($this->admin)
            ->put(route('admin.usuarios.update', $otro->id), [
                'nombre' => 'Otra', 'apellido' => 'Administra', 'email' => $otro->email, 'rol' => 'dentista',
            ])->assertSessionHasNoErrors();
        $this->assertSame('dentista', DB::table('usuarios')->where('id', $otro->id)->value('rol'));
    }
}
