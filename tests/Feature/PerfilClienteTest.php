<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
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

    // ── TM5 · sin cookie de «Recordarme» ──────────────────────────────────────────────

    /**
     * Caracterización (TM5, plan D5, /analyze A8): ningún camino de entrada emite la cookie de recuerdo,
     * así que una sesión cerrada por CA6 no se puede reabrir con ella. `usuarios` no tiene columna
     * `remember_token`: pedir el recuerdo en cualquiera de los tres caminos daría un 500, y el test falla en
     * su redirección.
     */
    public function test_recordarme_no_crea_una_cookie_de_recuerdo(): void
    {
        $recuerdo = $this->app['auth']->guard()->getRecallerName();

        // Inicio de sesión con la casilla «Recordarme» marcada.
        $this->post('/login', ['email' => $this->paciente1->email, 'password' => 'secreto-de-pruebas', 'remember' => 'on'])
            ->assertRedirect('/cliente')
            ->assertCookieMissing($recuerdo);
        $this->assertAuthenticatedAs($this->paciente1);
        $this->otroEquipo();

        // Registro, que también deja la sesión abierta.
        $correo = 'registro.'.Str::lower(Str::random(10)).'@example.test';
        $this->post('/registro', [
            'nombre' => 'Rita', 'apellido' => 'Registro', 'email' => $correo,
            'password' => 'registro-de-pruebas', 'password_confirmation' => 'registro-de-pruebas',
            'terminos' => '1', 'remember' => 'on',
        ])->assertRedirect(route('cliente.dashboard'))->assertCookieMissing($recuerdo);
        $registrada = Usuario::where('email', $correo)->sole();
        $this->assertAuthenticatedAs($registrada);
        $this->otroEquipo();

        // Retorno de Google de una cuenta ya vinculada.
        $conGoogle = $this->crearUsuarioDePrueba('cliente', 'Gala', 'Google');
        $conGoogle->forceFill(['google_id' => 'google-'.Str::random(12), 'password_hash' => null])->save();
        $this->simularGoogle($conGoogle);
        $this->get('/auth/google/callback')->assertRedirect(route('cliente.dashboard'))->assertCookieMissing($recuerdo);
        $this->assertAuthenticatedAs($conGoogle);
    }

    // ── CA7 · el personal no guarda el perfil de paciente ─────────────────────────────

    /** CA7, TM1, P3: admin, dentista y asistente reciben 403, sin ficha nueva ni cambios, y queda el acceso denegado (020). */
    public function test_el_personal_no_puede_guardar_el_perfil_de_paciente(): void
    {
        foreach ([$this->admin, $this->dentista1, $this->asistente] as $personal) {
            $antes = DB::table('usuarios')->where('id', $personal->id)->first();

            $this->actingAs($personal)->from(self::PERFIL)
                ->put(self::PERFIL, $this->datosDelPerfil($personal, ['nombre' => 'Cambiado', 'apellido' => 'Por El Perfil']))
                ->assertForbidden();

            $this->assertSame(0, DB::table('pacientes')->where('usuario_id', $personal->id)->count(), "{$personal->rol}: sin ficha de paciente.");
            $this->assertEquals($antes, DB::table('usuarios')->where('id', $personal->id)->first(), "{$personal->rol}: su cuenta no cambia.");

            $evento = DB::table('auditoria')->where('accion', 'acceso_denegado')->where('actor_id', $personal->id)->sole();
            $this->assertSame('denegado', $evento->resultado);
            $this->assertSame(['accion_solicitada' => 'cliente.perfil.update'], json_decode($evento->detalle, true));
        }
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    /** Deja el navegador de la prueba sin sesión ni usuario, como otro equipo (patrón de SesionVigenteTest). */
    private function otroEquipo(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    /** El retorno de Google devuelve esta cuenta (patrón de SesionVigenteTest). */
    private function simularGoogle(Usuario $usuario): void
    {
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn((new UsuarioGoogle)->setRaw([])->map([
            'id' => $usuario->google_id, 'name' => $usuario->nombre.' '.$usuario->apellido, 'email' => $usuario->email, 'avatar' => null,
        ]));
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);
    }
}
