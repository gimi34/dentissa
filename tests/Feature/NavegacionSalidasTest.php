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
 * Salidas de las pantallas (spec 044): la salida del acceso y del registro (CA1–CA4; la recuperación de
 * contraseña sale al inicio de sesión, D11), desde el panel (CA5) y al cerrar sesión (CA6).
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

    /** Salida de cada pantalla (CA1, D11): inicio en tres; inicio de sesión en las cuatro de recuperación. */
    public static function salidasDeAcceso(): array
    {
        $inicio = ['/', 'Volver al inicio'];
        $login = ['/login', 'Volver a iniciar sesión'];

        return [
            'inicio de sesión' => ['login', ...$inicio],
            'registro' => ['registro', ...$inicio],
            'completar el registro con Google' => ['contacto', ...$inicio],
            'pedir el enlace' => ['solicitud', ...$login],
            'enlace enviado' => ['enviada', ...$login],
            'enlace caducado' => ['caducado', ...$login],
            'contraseña nueva' => ['nueva', ...$login],
        ];
    }

    #[DataProvider('salidasDeAcceso')]
    public function test_las_pantallas_de_acceso_empiezan_por_su_salida(string $pantalla, string $destino, string $texto): void
    {
        $xpath = $this->xpath($this->pantalla($pantalla));
        $primero = $xpath->query("(//*[contains(concat(' ', normalize-space(@class), ' '), ' form-container ')]//a)[1]")->item(0);

        $this->assertNotNull($primero, "{$pantalla}: el formulario tiene enlaces.");
        $this->assertSame($destino, $this->ruta($primero->getAttribute('href')), "{$pantalla}: el primer enlace del formulario lleva a {$destino}.");
        $this->assertSame($texto, trim($primero->textContent), "{$pantalla}: el primer enlace dice «{$texto}».");
        $this->assertSame(1, $this->enlacesA($xpath, $destino, $texto), "{$pantalla}: «{$texto}» no se repite más abajo.");
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

    // ── Historia 2: del panel a la página de inicio (CA5) ──────────────────────────────

    /** Cada rol del escenario con la ruta de su panel. */
    private function roles(): array
    {
        return [
            'admin' => [$this->admin, route('admin.dashboard')],
            'dentista' => [$this->dentista1, route('dentista.dashboard')],
            'asistente' => [$this->asistente, route('asistente.dashboard')],
            'cliente' => [$this->paciente1, route('cliente.dashboard')],
        ];
    }

    public function test_el_panel_de_cada_rol_enlaza_la_pagina_de_inicio(): void
    {
        foreach ($this->roles() as $rol => [$usuario, $panel]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($panel)->assertOk()->getContent());
            $entradas = $xpath->query("//nav[contains(concat(' ', normalize-space(@class), ' '), ' sidebar-nav ')]//a[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ')]");

            $hallada = 0;
            foreach ($entradas as $entrada) {
                if ($this->ruta($entrada->getAttribute('href')) === '/' && trim($entrada->textContent) === 'Ver sitio web') {
                    $hallada++;
                }
            }
            $this->assertSame(1, $hallada, "{$rol}: la barra lateral tiene una entrada «Ver sitio web» a la página de inicio.");
        }
    }

    public function test_la_pagina_de_inicio_con_sesion_ofrece_ir_a_mi_panel(): void
    {
        foreach ($this->roles() as $rol => [$usuario, $panel]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get('/')->assertOk()->getContent());
            $rutaPanel = $this->ruta($panel);

            foreach ($xpath->query('//a[@href]') as $enlace) {
                $this->assertNotContains($this->ruta($enlace->getAttribute('href')), ['/login', '/registro'], "{$rol}: con sesión, ningún enlace lleva al inicio de sesión ni al registro.");
            }
            $acceso = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' pub-acceso ')]//a");
            $this->assertSame(1, $acceso->length, "{$rol}: la portada ofrece un solo enlace de cuenta.");
            $this->assertSame($rutaPanel, $this->ruta($acceso->item(0)->getAttribute('href')), "{$rol}: el de la portada lleva a su panel.");
            $this->assertSame('Ir a mi panel', trim($acceso->item(0)->textContent));
            // Los botones grandes de cuenta («Agendar mi primera cita», «Crear mi cuenta»); «Ver servicios» no es de cuenta.
            $botones = array_filter(
                iterator_to_array($xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' c-btn--lg ')]")),
                fn ($boton) => $this->ruta($boton->getAttribute('href')) !== '/servicios'
            );
            $this->assertCount(3, $botones, "{$rol}: los tres botones de cuenta de la página.");
            foreach ($botones as $boton) {
                $this->assertSame($rutaPanel, $this->ruta($boton->getAttribute('href')), "{$rol}: cada botón de cuenta lleva a su panel.");
                $this->assertSame('Ir a mi panel', trim($boton->textContent), "{$rol}: y dice «Ir a mi panel».");
            }
        }
    }

    public function test_el_registro_con_sesion_lleva_al_panel(): void
    {
        foreach ($this->roles() as $rol => [$usuario, $panel]) {
            $this->actingAs($usuario)->get('/registro')->assertRedirect($panel);
            $this->actingAs($usuario)->get('/registro/contacto')->assertRedirect($panel);
            $this->actingAs($usuario)->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto')->assertRedirect($panel);
        }
    }

    // ── Historia 3: cerrar sesión (CA6) ──────────────────────────────────────────────

    public function test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez(): void
    {
        $this->actingAs($this->paciente1)->post('/logout')->assertRedirect(route('inicio'));
        $this->assertGuest();

        $xpath = $this->xpath($this->get('/')->assertOk()->getContent());
        $aviso = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' pub-aviso ')]");
        $this->assertSame(1, $aviso->length, 'Tras cerrar sesión, la página de inicio muestra el aviso.');
        $this->assertSame('status', $aviso->item(0)->getAttribute('role'), 'El aviso es un mensaje de estado.');
        $this->assertSame('Has cerrado sesión', trim($aviso->item(0)->textContent));
        $this->assertGreaterThan(0, $this->enlacesA($xpath, '/login', 'Iniciar sesión'), 'La cabecera ofrece «Iniciar sesión».');
        $this->assertGreaterThan(0, $this->enlacesA($xpath, '/registro', 'Crear cuenta'), 'La cabecera ofrece «Crear cuenta».');

        $otra = $this->xpath($this->get('/')->assertOk()->getContent());
        $this->assertSame(0, $otra->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' pub-aviso ')]")->length, 'Al recargar, el aviso ya no sale.');
    }

    /** Caracterización: el grupo web no deja guardar en caché ni el panel ni la página de inicio (TM5). */
    public function test_tras_cerrar_sesion_el_panel_no_se_sirve_de_cache(): void
    {
        $panel = $this->actingAs($this->paciente1)->get('/cliente')->assertOk();
        $this->assertStringContainsString('no-store', (string) $panel->headers->get('Cache-Control'), 'El panel no se guarda en caché.');

        $this->post('/logout');
        $inicio = $this->get('/')->assertOk();
        $this->assertStringContainsString('no-store', (string) $inicio->headers->get('Cache-Control'), 'La página con el aviso no se guarda en caché.');
        $this->get('/cliente')->assertRedirect(route('login'));
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
