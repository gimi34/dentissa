<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Ficha docs/changes/2026-11-17-fix-sesion-cuenta-desactivada.md (RS3.a): desactivar una cuenta
 * cierra su sesión abierta en la siguiente petición. Casos de abuso 001 CA11, 006 CA8, 008 CA6 y
 * 010 CA8, por las tres pantallas que desactivan: usuarios, personal y pacientes.
 *
 * Las dos sesiones (la de la cuenta y la del admin) se simulan como en SesionVigenteTest: se guardan
 * los datos de una y se vuelven a poner tras la otra petición, con el guard olvidado entre medias.
 */
class CuentaDesactivadaSesionTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;

    private const CONTRASENA = 'secreto-de-pruebas';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    /** Deja el navegador de la prueba sin sesión ni usuario, como otro equipo. */
    private function otroEquipo(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    /** Entra con contraseña, comprueba que el panel abre y devuelve los datos de esa sesión. */
    private function entrar(Usuario $usuario, string $panel): array
    {
        Cache::flush();
        $this->otroEquipo();
        $this->post('/login', ['email' => $usuario->email, 'password' => self::CONTRASENA])->assertRedirect();
        $this->assertAuthenticatedAs($usuario);
        $sesion = session()->all();

        $this->otroEquipo();
        $this->withSession($sesion)->get($panel)->assertOk();

        return $sesion;
    }

    /** El admin pulsa «Desactivar» (o «Reactivar») desde su propio navegador. */
    private function conmutarComoAdmin(string $ruta): void
    {
        $this->otroEquipo();
        $this->actingAs($this->admin)->from('/admin')->patch($ruta)->assertRedirect('/admin');
    }

    /** Vuelve a esa sesión: la siguiente petición la cierra y lleva al inicio de sesión. */
    private function assertSesionCerrada(array $sesion, string $panel): void
    {
        $this->otroEquipo();
        $this->withSession($sesion)->get($panel)->assertRedirect(url('/login'));
        $this->assertGuest();
        $this->assertArrayNotHasKey($this->app['auth']->guard()->getName(), session()->all(), 'La sesión se invalidó.');
    }

    public function test_desactivar_un_usuario_cierra_su_sesion(): void
    {
        $sesion = $this->entrar($this->asistente, '/asistente');

        $this->conmutarComoAdmin(route('admin.usuarios.toggle', $this->asistente->id));

        $this->assertSesionCerrada($sesion, '/asistente');
    }

    /** Review ligera (menor 1): desmarcar «Activo» al editar el usuario también la cierra. */
    public function test_desmarcar_activo_al_editar_un_usuario_cierra_su_sesion(): void
    {
        $sesion = $this->entrar($this->asistente, '/asistente');

        $this->otroEquipo();
        $this->actingAs($this->admin)->put(route('admin.usuarios.update', $this->asistente->id), [
            'nombre' => $this->asistente->nombre,
            'apellido' => $this->asistente->apellido,
            'email' => $this->asistente->email,
            'rol' => 'asistente',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, (int) DB::table('usuarios')->where('id', $this->asistente->id)->value('activo'));

        $this->assertSesionCerrada($sesion, '/asistente');
    }

    public function test_desactivar_a_un_miembro_del_personal_cierra_su_sesion(): void
    {
        $sesion = $this->entrar($this->dentista1, '/dentista');

        $this->conmutarComoAdmin(route('admin.personal.toggle', $this->d1));

        $this->assertSesionCerrada($sesion, '/dentista');
    }

    public function test_desactivar_a_un_paciente_cierra_su_sesion(): void
    {
        $sesion = $this->entrar($this->paciente1, '/cliente');

        $this->conmutarComoAdmin(route('admin.pacientes.toggle', $this->p1));

        $this->assertSesionCerrada($sesion, '/cliente');
    }

    public function test_desactivar_a_otro_admin_cierra_su_sesion(): void
    {
        $otroAdmin = Usuario::create([
            'nombre' => 'Otra', 'apellido' => 'Administra', 'email' => 'otra.admin.'.uniqid().'@example.test',
            'rol' => 'admin', 'activo' => 1, 'password_hash' => bcrypt(self::CONTRASENA),
        ]);
        $sesion = $this->entrar($otroAdmin, '/admin');

        $this->conmutarComoAdmin(route('admin.usuarios.toggle', $otroAdmin->id));

        $this->assertSesionCerrada($sesion, '/admin');
    }

    public function test_las_demas_cuentas_siguen_con_su_sesion(): void
    {
        $sesion = $this->entrar($this->dentista2, '/dentista');

        $this->conmutarComoAdmin(route('admin.personal.toggle', $this->d1));

        $this->otroEquipo();
        $this->withSession($sesion)->get('/dentista')->assertOk();
        $this->assertAuthenticatedAs($this->dentista2);
    }

    public function test_una_cuenta_reactivada_vuelve_a_entrar_y_sigue(): void
    {
        $this->conmutarComoAdmin(route('admin.usuarios.toggle', $this->asistente->id));
        $this->assertSame(0, (int) DB::table('usuarios')->where('id', $this->asistente->id)->value('activo'));
        $this->conmutarComoAdmin(route('admin.usuarios.toggle', $this->asistente->id));

        $sesion = $this->entrar($this->asistente, '/asistente');

        $this->otroEquipo();
        $this->withSession($sesion)->get('/asistente')->assertOk();
        $this->assertAuthenticatedAs($this->asistente);
    }
}
