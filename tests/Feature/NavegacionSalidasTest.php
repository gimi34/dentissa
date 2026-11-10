<?php

namespace Tests\Feature;

use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioDeGoogle;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Salidas de las pantallas (spec 044): volver a la página de inicio desde el acceso y el registro
 * (CA1–CA4), desde el panel (CA5) y al cerrar sesión (CA6).
 *
 * Que el enlace se vea sin desplazarse a 320, 390 y 1440 px lo comprueba el usuario en cada tarea de
 * interfaz (plan, Trazabilidad); aquí se comprueba que es lo primero del formulario.
 */
class NavegacionSalidasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const NOMBRE_LOGO = 'Ir a la página de inicio';

    private const GOOGLE = [
        'google_id' => 'google-044-anterior',
        'nombre' => 'Anterior',
        'apellido' => 'Registro',
        'email' => 'anterior-044@example.test',
        'foto_url' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Cache::flush();
    }

    // ── Historia 1: salidas del acceso y del registro (CA1–CA4) ─────────────────────

    public static function pantallasDeAcceso(): array
    {
        return [
            'inicio de sesión' => ['login'],
            'registro' => ['registro'],
            'completar el registro con Google' => ['contacto'],
            'pedir el enlace' => ['solicitud'],
            'enlace enviado' => ['enviada'],
            'enlace caducado' => ['caducado'],
            'contraseña nueva' => ['nueva'],
        ];
    }

    #[DataProvider('pantallasDeAcceso')]
    public function test_las_pantallas_de_acceso_empiezan_por_volver_al_inicio(string $pantalla): void
    {
        $xpath = $this->xpath($this->pantalla($pantalla));
        $primero = $xpath->query("(//*[contains(concat(' ', normalize-space(@class), ' '), ' form-container ')]//a)[1]")->item(0);

        $this->assertNotNull($primero, "{$pantalla}: el formulario tiene enlaces.");
        $this->assertSame('/', $this->ruta($primero->getAttribute('href')), "{$pantalla}: el primer enlace del formulario lleva a la página de inicio.");
        $this->assertStringContainsString('Volver al inicio', trim($primero->textContent), "{$pantalla}: el primer enlace dice «Volver al inicio».");
    }

    #[DataProvider('pantallasDeAcceso')]
    public function test_el_logo_del_acceso_lleva_al_inicio_con_nombre_accesible(string $pantalla): void
    {
        $xpath = $this->xpath($this->pantalla($pantalla));
        $enlaces = $xpath->query("//a[.//img[contains(@src, 'logo-main')]]");

        $this->assertSame(1, $enlaces->length, "{$pantalla}: el logo está dentro de un enlace.");
        $enlace = $enlaces->item(0);
        $this->assertSame('/', $this->ruta($enlace->getAttribute('href')), "{$pantalla}: el logo lleva a la página de inicio.");
        $this->assertSame(self::NOMBRE_LOGO, $enlace->getAttribute('aria-label'), "{$pantalla}: el nombre accesible es exactamente «".self::NOMBRE_LOGO.'».');
        foreach ($xpath->query('.//img', $enlace) as $imagen) {
            $this->assertTrue($imagen->hasAttribute('alt') && $imagen->getAttribute('alt') === '', "{$pantalla}: la imagen del logo-enlace lleva alt vacío.");
        }
    }

    public function test_contrasena_nueva_ofrece_volver_a_iniciar_sesion(): void
    {
        $xpath = $this->xpath($this->pantalla('nueva'));

        $this->assertSame(1, $this->enlacesA($xpath, '/login', 'Volver a iniciar sesión'), '«Contraseña nueva» ofrece «Volver a iniciar sesión».');
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Volver al inicio'), '«Contraseña nueva» ofrece «Volver al inicio».');
    }

    public function test_salir_de_contrasena_nueva_no_gasta_el_enlace(): void
    {
        $token = Password::broker()->createToken($this->paciente1);
        $clave = $this->paciente1->fresh()->password;
        $enlace = '/contrasena/nueva/'.$token.'?cuenta='.$this->paciente1->id;

        $this->get($enlace)->assertOk()->assertSee('Elige tu contraseña nueva');
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();

        Cache::flush();
        $this->get($enlace)->assertOk()->assertSee('Elige tu contraseña nueva');
        $this->assertSame($clave, $this->paciente1->fresh()->password, 'Salir no cambia la contraseña.');
    }

    public function test_contrasena_nueva_conserva_no_referrer_y_sus_salidas_no_llevan_el_token(): void
    {
        $token = Password::broker()->createToken($this->paciente1);
        $html = $this->get('/contrasena/nueva/'.$token.'?cuenta='.$this->paciente1->id)->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//meta[@name="referrer"][@content="no-referrer"]')->length, 'La página conserva referrer: no-referrer.');
        foreach ($xpath->query('//a[@href]') as $enlace) {
            $this->assertStringNotContainsString($token, $enlace->getAttribute('href'), 'Ningún enlace lleva el token.');
        }
    }

    public function test_salir_del_registro_con_google_no_crea_cuenta(): void
    {
        $usuarios = Usuario::count();

        $this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto')->assertOk();
        $this->get('/')->assertOk();

        $this->assertSame($usuarios, Usuario::count(), 'Salir del registro con Google no crea ninguna cuenta.');
        $this->assertFalse(Usuario::where('email', self::GOOGLE['email'])->exists());
    }

    public function test_volver_a_entrar_con_google_reemplaza_los_datos_anteriores(): void
    {
        $nuevo = (new UsuarioDeGoogle)->map([
            'id' => 'google-044-nuevo',
            'name' => 'Nueva Persona',
            'email' => 'nueva-044@example.test',
            'avatar' => null,
        ]);
        $nuevo->user = ['given_name' => 'Nueva', 'family_name' => 'Persona'];
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn($nuevo);
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);

        $this->withSession(['google_registro' => self::GOOGLE])
            ->get('/auth/google/callback?code=c&state=s')
            ->assertRedirect(route('register.contacto'));

        $this->get('/registro/contacto')->assertOk()
            ->assertSee('¿Cómo contactarte, Nueva?', false)
            ->assertDontSee(self::GOOGLE['nombre'].'?', false)
            ->assertDontSee(self::GOOGLE['email'], false);
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    /** HTML de cada pantalla de acceso, sin sesión. */
    private function pantalla(string $pantalla): string
    {
        return (match ($pantalla) {
            'login' => $this->get('/login'),
            'registro' => $this->get('/registro'),
            'contacto' => $this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto'),
            'solicitud' => $this->get('/contrasena/olvidada'),
            'enviada' => $this->withSession(['contrasena_enviada' => ['correo' => 'alguien@example.test', 'hora' => '10:42']])->get('/contrasena/enviada'),
            'caducado' => $this->get('/contrasena/nueva/'.Str::random(64).'?cuenta='.Str::uuid()),
            'nueva' => $this->get('/contrasena/nueva/'.Password::broker()->createToken($this->paciente1).'?cuenta='.$this->paciente1->id),
        })->assertOk()->getContent();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new DOMXPath($dom);
    }

    /** Ruta de un href: «/» solo para la raíz de una URL absoluta; un «#» o un href vacío se devuelven tal cual. */
    private function ruta(string $href): string
    {
        $ruta = parse_url($href, PHP_URL_PATH);
        if ($ruta === null || $ruta === '') {
            return parse_url($href, PHP_URL_HOST) !== null ? '/' : $href;
        }

        return $ruta;
    }

    /** Enlaces de la página a una ruta cuyo texto contiene el dado. */
    private function enlacesA(DOMXPath $xpath, string $ruta, string $texto): int
    {
        $total = 0;
        foreach ($xpath->query('//a[@href]') as $enlace) {
            if ($this->ruta($enlace->getAttribute('href')) === $ruta && str_contains(trim($enlace->textContent), $texto)) {
                $total++;
            }
        }

        return $total;
    }
}
