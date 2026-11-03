<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use Tests\TestCase;

/**
 * Spec 041, CA7 (TM8, plan D4): restablecer la contraseña cierra todas las sesiones de la cuenta.
 *
 * Cada sesión guarda la versión con la que entró (`version_sesion`), también al entrar con Google.
 * El restablecimiento sube la de la cuenta y `SesionVigente` cierra en su siguiente petición toda
 * sesión con una versión menor. Una sesión sin versión, abierta antes del despliegue, cuenta como 0:
 * sigue si la cuenta no restableció desde entonces y se cierra si lo hizo.
 *
 * Las dos sesiones se simulan guardando los datos de una y volviéndolos a poner tras la otra petición,
 * con el guard olvidado entre medias: en los tests el almacén de sesión y el guard son los mismos.
 */
class SesionVigenteTest extends TestCase
{
    use DatabaseTransactions;

    private const PANEL = '/cliente';

    private const NUEVA = 'contrasena-nueva-segura';

    private function crearUsuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'sesion.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('contrasena-anterior'),
        ], $datos));
    }

    /** Deja el navegador de la prueba sin sesión ni usuario, como otro equipo. */
    private function otroEquipo(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    /** Entra con contraseña y devuelve los datos de esa sesión. */
    private function entrarConContrasena(Usuario $usuario, string $contrasena = 'contrasena-anterior'): array
    {
        $this->post('/login', ['email' => $usuario->email, 'password' => $contrasena])->assertRedirect(self::PANEL);
        $this->assertAuthenticatedAs($usuario);

        return session()->all();
    }

    /** Entra con Google y devuelve los datos de esa sesión. */
    private function entrarConGoogle(Usuario $usuario): array
    {
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn((new UsuarioGoogle)->setRaw([])->map([
            'id' => $usuario->google_id, 'name' => 'Persona De Pruebas', 'email' => $usuario->email, 'avatar' => null,
        ]));
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);

        $this->get('/auth/google/callback')->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($usuario);

        return session()->all();
    }

    /** El restablecimiento completo, por el enlace y desde otro equipo sin sesión. */
    /** /review R1: el mensaje de éxito no se pierde si ese mismo navegador tenía abierta la cuenta (CA3). */
    public function test_con_la_cuenta_abierta_en_ese_navegador_se_ve_el_exito(): void
    {
        $usuario = $this->crearUsuario();
        $this->entrarConContrasena($usuario);
        $token = Password::broker()->createToken($usuario);
        $this->app['auth']->forgetGuards();

        $this->post('/contrasena/nueva', [
            'token' => $token,
            'cuenta' => $usuario->id,
            'password' => self::NUEVA,
            'password_confirmation' => self::NUEVA,
        ])->assertRedirect(url('/login'));
        $this->assertGuest();
        $this->assertSame(1, (int) $usuario->fresh()->version_sesion, 'La versión de la cuenta subió.');

        // Como en un servidor real, la cuenta se lee de nuevo en cada petición.
        $this->app['auth']->forgetGuards();
        $this->get('/login')->assertOk()->assertSee('Tu contraseña cambió.');
    }

    private function restablecer(Usuario $usuario): void
    {
        $this->otroEquipo();
        Cache::flush();

        $this->post('/contrasena/nueva', [
            'token' => Password::broker()->createToken($usuario),
            'cuenta' => $usuario->id,
            'password' => self::NUEVA,
            'password_confirmation' => self::NUEVA,
        ])->assertRedirect(url('/login'));

        $this->assertSame(1, (int) $usuario->fresh()->version_sesion, 'La versión de la cuenta subió.');
    }

    /** Vuelve a esa sesión: la siguiente petición la cierra y lleva al inicio de sesión. */
    private function assertSesionCerrada(array $sesion): void
    {
        $this->otroEquipo();

        $this->withSession($sesion)->get(self::PANEL)->assertRedirect(url('/login'));
        $this->assertGuest();
        $this->assertArrayNotHasKey($this->claveDeLaSesion(), session()->all(), 'La sesión se invalidó.');
    }

    private function claveDeLaSesion(): string
    {
        return $this->app['auth']->guard()->getName();
    }

    public function test_guarda_la_version_al_entrar_con_contrasena_y_con_google(): void
    {
        $conContrasena = $this->crearUsuario();
        $conContrasena->forceFill(['version_sesion' => 3])->save();
        $this->assertSame(3, $this->entrarConContrasena($conContrasena)['version_sesion'] ?? null, 'Con contraseña.');

        $this->otroEquipo();
        $conGoogle = $this->crearUsuario(['google_id' => 'google-'.Str::random(12), 'password_hash' => null]);
        $conGoogle->forceFill(['version_sesion' => 2])->save();
        $this->assertSame(2, $this->entrarConGoogle($conGoogle)['version_sesion'] ?? null, 'Con Google (TM8).');
    }

    public function test_restablecer_cierra_las_otras_sesiones(): void
    {
        $usuario = $this->crearUsuario();
        $unaSesion = $this->entrarConContrasena($usuario);
        $this->otroEquipo();
        $otraSesion = $this->entrarConContrasena($usuario);

        // Antes de restablecer, las dos siguen abiertas.
        $this->otroEquipo();
        $this->withSession($unaSesion)->get(self::PANEL)->assertOk();

        $this->restablecer($usuario);

        $this->assertSesionCerrada($unaSesion);
        $this->assertSesionCerrada($otraSesion);
    }

    public function test_entrar_tras_restablecer_abre_una_sesion_que_sigue(): void
    {
        $usuario = $this->crearUsuario();
        $this->restablecer($usuario);

        $this->entrarConContrasena($usuario, self::NUEVA);

        $this->get(self::PANEL)->assertOk();
        $this->assertAuthenticatedAs($usuario);
        $this->assertSame(1, session('version_sesion'));
    }

    public function test_tambien_en_cuentas_de_google(): void
    {
        // Sin contraseña: AuthenticateSession no las vigila y CA7 fallaría con él (D4).
        $usuario = $this->crearUsuario(['google_id' => 'google-'.Str::random(12), 'password_hash' => null]);
        $sesion = $this->entrarConGoogle($usuario);

        $this->restablecer($usuario);

        $this->assertSesionCerrada($sesion);
    }

    public function test_una_sesion_anterior_al_despliegue_se_cierra_si_hubo_restablecimiento(): void
    {
        $usuario = $this->crearUsuario();
        $sesion = $this->entrarConContrasena($usuario);
        unset($sesion['version_sesion']);

        $this->restablecer($usuario);

        $this->assertSesionCerrada($sesion);
    }

    public function test_una_sesion_anterior_al_despliegue_sigue_si_no_lo_hubo(): void
    {
        $usuario = $this->crearUsuario();
        $sesion = $this->entrarConContrasena($usuario);
        unset($sesion['version_sesion']);
        $this->otroEquipo();

        $this->withSession($sesion)->get(self::PANEL)->assertOk();

        $this->assertAuthenticatedAs($usuario);
        $this->assertSame(0, session('version_sesion'), 'Guarda la versión y sigue (D4).');
    }
}
