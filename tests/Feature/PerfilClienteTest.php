<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
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

    // ── CA6 · cambiar la contraseña cierra las otras sesiones ─────────────────────────

    /** CA6, TM2: la otra sesión abierta de la cuenta se cierra en su siguiente petición. */
    public function test_cambiar_la_contrasena_cierra_las_otras_sesiones(): void
    {
        $otra = $this->entrarConContrasena($this->paciente1);
        $this->otroEquipo();
        $this->entrarConContrasena($this->paciente1);

        $this->cambiarContrasena(['password_actual' => self::ACTUAL, 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA])
            ->assertRedirect(self::PERFIL)
            ->assertSessionHas('success', 'Tu contraseña fue actualizada.');
        $this->assertSame(1, (int) $this->paciente1->fresh()->version_sesion, 'La versión de la cuenta subió.');

        $this->assertSesionCerrada($otra);
    }

    /** CA6, plan D6: crear la primera contraseña de una cuenta de Google también cierra las otras sesiones. */
    public function test_crear_la_contrasena_de_una_cuenta_de_google_tambien_cierra_las_otras(): void
    {
        $conGoogle = $this->crearUsuarioDePrueba('cliente', 'Gala', 'Google');
        $conGoogle->forceFill(['google_id' => 'google-'.Str::random(12), 'password_hash' => null])->save();
        $this->simularGoogle($conGoogle);

        $otra = $this->entrarConGoogle($conGoogle);
        $this->otroEquipo();
        $this->entrarConGoogle($conGoogle);

        $this->cambiarContrasena(['password' => self::NUEVA, 'password_confirmation' => self::NUEVA])
            ->assertRedirect(self::PERFIL)
            ->assertSessionHas('success', 'Tu contraseña fue actualizada.');
        $this->assertSame(1, (int) $conGoogle->fresh()->version_sesion, 'La versión de la cuenta subió.');

        $this->assertSesionCerrada($otra);
    }

    /**
     * CA6, TM3 (/analyze A2): la sesión desde la que se cambia sigue abierta, con otro identificador. El cambio
     * parte de un identificador conocido en la cookie, porque sin ella el arnés asigna uno nuevo en cada petición.
     */
    public function test_la_sesion_actual_sigue_abierta_con_otro_identificador(): void
    {
        $this->entrarConContrasena($this->paciente1);
        $conocido = Str::random(40);

        $respuesta = $this->withCookie(config('session.cookie'), $conocido)
            ->cambiarContrasena(['password_actual' => self::ACTUAL, 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA])
            ->assertRedirect(self::PERFIL)
            ->assertSessionHas('success');
        $this->assertSame(1, (int) $this->paciente1->fresh()->version_sesion, 'La versión de la cuenta subió.');

        $nuevo = $respuesta->getCookie(config('session.cookie'))?->getValue();
        $this->assertNotNull($nuevo, 'La respuesta lleva la cookie de sesión.');
        $this->assertNotSame($conocido, $nuevo, 'La sesión cambió de identificador.');

        // Como en un servidor real, la cuenta se lee de nuevo: la sesión guarda la versión nueva y sigue dentro.
        $actual = session()->all();
        $this->app['auth']->forgetGuards();
        $this->withSession($actual)->get(self::PERFIL)->assertOk();
        $this->assertAuthenticatedAs($this->paciente1);
    }

    /** CA6, TM4 (/analyze A9): ningún rechazo sube la versión, cambia la contraseña ni cierra la otra sesión. */
    public function test_un_cambio_rechazado_no_cierra_ninguna_sesion(): void
    {
        $otra = $this->entrarConContrasena($this->paciente1);
        $this->otroEquipo();
        $this->entrarConContrasena($this->paciente1);
        $hash = $this->paciente1->fresh()->password_hash;

        $rechazos = [
            'nueva corta' => [['password_actual' => self::ACTUAL, 'password' => 'corta', 'password_confirmation' => 'corta'],
                'password', 'La contraseña debe tener al menos 8 caracteres.'],
            'sin la actual' => [['password' => self::NUEVA, 'password_confirmation' => self::NUEVA],
                'password_actual', 'Ingresa tu contraseña actual.'],
            'actual incorrecta' => [['password_actual' => 'no-es-la-actual', 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA],
                'password_actual', null],
            'la misma' => [['password_actual' => self::ACTUAL, 'password' => self::ACTUAL, 'password_confirmation' => self::ACTUAL],
                'password', 'Elige una contraseña distinta de la actual'],
        ];

        foreach ($rechazos as $caso => [$datos, $campo, $mensaje]) {
            $this->cambiarContrasena($datos)->assertRedirect(self::PERFIL)
                ->assertSessionHasErrors($mensaje === null ? $campo : [$campo => $mensaje]);
            $this->assertRechazoSinEfecto($hash, $caso);
        }

        // Límite agotado: cinco fallos y después la actual correcta, que ya no se comprueba.
        Cache::flush();
        for ($i = 0; $i < 5; $i++) {
            $this->cambiarContrasena(['password_actual' => 'no-es-la-actual', 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA]);
        }
        $this->cambiarContrasena(['password_actual' => self::ACTUAL, 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA])
            ->assertSessionHasErrors(['password_actual' => 'Demasiados intentos. Espera un minuto y vuelve a intentarlo.']);
        $this->assertRechazoSinEfecto($hash, 'límite agotado');

        $this->assertSesionSigue($otra);
    }

    /**
     * CA6, TM2 (/review R1): si otro cambio de la misma cuenta sube la versión justo después de este, la sesión
     * actual guarda la versión que escribió su propio cambio, no la del otro, y el otro cambio también la cierra.
     */
    public function test_un_cambio_simultaneo_cierra_tambien_la_sesion_actual(): void
    {
        $this->entrarConContrasena($this->paciente1);

        $hecho = false;
        DB::listen(function ($consulta) use (&$hecho) {
            if (! $hecho && str_starts_with($consulta->sql, 'update `usuarios`') && str_contains($consulta->sql, '`password_hash`')) {
                $hecho = true;
                DB::table('usuarios')->where('id', $this->paciente1->id)->increment('version_sesion');
            }
        });

        $this->cambiarContrasena(['password_actual' => self::ACTUAL, 'password' => self::NUEVA, 'password_confirmation' => self::NUEVA])
            ->assertRedirect(self::PERFIL);
        $this->assertTrue($hecho, 'El otro cambio se simuló.');
        $this->assertSame(2, (int) $this->paciente1->fresh()->version_sesion, 'Los dos cambios subieron la versión.');
        $this->assertSame(1, session('version_sesion'), 'La sesión guarda la versión de su propio cambio.');

        $actual = session()->all();
        $this->app['auth']->forgetGuards();
        $this->withSession($actual)->get(self::PERFIL)->assertRedirect(url('/login'));
        $this->assertGuest();
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    private const ACTUAL = 'secreto-de-pruebas';

    private const NUEVA = 'contrasena-nueva-segura';

    private function cambiarContrasena(array $datos): TestResponse
    {
        return $this->from(self::PERFIL)->put(self::PERFIL.'/password', $datos);
    }

    /** Entra con contraseña y devuelve los datos de esa sesión (patrón de SesionVigenteTest). */
    private function entrarConContrasena(Usuario $usuario): array
    {
        $this->post('/login', ['email' => $usuario->email, 'password' => self::ACTUAL])->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($usuario);

        return session()->all();
    }

    /** Entra por el retorno de Google ya simulado y devuelve los datos de esa sesión. */
    private function entrarConGoogle(Usuario $usuario): array
    {
        $this->get('/auth/google/callback')->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($usuario);

        return session()->all();
    }

    /** Vuelve a esa sesión: su siguiente petición la cierra y lleva al inicio de sesión. */
    private function assertSesionCerrada(array $sesion): void
    {
        $this->otroEquipo();
        $this->withSession($sesion)->get(self::PERFIL)->assertRedirect(url('/login'));
        $this->assertGuest();
    }

    /** Vuelve a esa sesión: sigue dentro. */
    private function assertSesionSigue(array $sesion): void
    {
        $this->otroEquipo();
        $this->withSession($sesion)->get(self::PERFIL)->assertOk();
        $this->assertAuthenticatedAs($this->paciente1);
    }

    private function assertRechazoSinEfecto(string $hash, string $caso): void
    {
        $cuenta = $this->paciente1->fresh();
        $this->assertSame(0, (int) $cuenta->version_sesion, "{$caso}: la versión no sube.");
        $this->assertSame($hash, $cuenta->password_hash, "{$caso}: la contraseña no cambia.");
    }

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
