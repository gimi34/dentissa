<?php

namespace Tests\Feature;

use App\Mail\ContrasenaCambiada;
use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\ReglasCss;
use Tests\Feature\Concerns\TokensAcceso;
use Tests\TestCase;

/**
 * Spec 041: el HTML que devuelve el servidor en el acceso y lo que usa del sistema de diseño.
 *
 *  - Descubribilidad (P10, SE2.a): `noindex` en las pantallas nuevas y en el login; la página del
 *    token no envía `Referer` (TM7) ni se guarda en caché (TM11); sin recursos de otros sitios.
 *  - Accesibilidad y estados (CA1, CA7, CA9): roles de los avisos, errores enlazados, un solo `h1`,
 *    el botón desactivado en espera y el teléfono de `clinica.contacto` en el talón (D16).
 *  - P9 (D9, D13, D17): sin estilos en línea, tokens de `acceso.css` iguales a los del sistema, todo
 *    `var()` con su token, sin literales fuera de `:root`, y los correos solo con valores de tokens.
 *  - La vista previa del diseño solo existe en local (D11).
 *
 * El comportamiento en un navegador real (contraste, toque, desbordamiento) lo mide `design/medir.mjs`.
 */
class RecuperarContrasenaVistasTest extends TestCase
{
    use DatabaseTransactions, ReglasCss, TokensAcceso;

    private const SOLICITUD = '/contrasena/olvidada';

    private const ENVIADA = '/contrasena/enviada';

    private const RESTABLECER = '/contrasena/nueva';

    private const HOJA = 'css/acceso.css';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function crearUsuario(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'vistas.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('contrasena-anterior'),
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function solicitud(): TestResponse
    {
        return $this->get(self::SOLICITUD)->assertOk();
    }

    private function enviada(): TestResponse
    {
        Mail::fake();
        $this->post(self::SOLICITUD, ['email' => 'alguien@example.test'])->assertRedirect(self::ENVIADA);

        return $this->get(self::ENVIADA)->assertOk();
    }

    private function nueva(): TestResponse
    {
        $usuario = $this->crearUsuario();

        return $this->get(self::RESTABLECER.'/'.Password::broker()->createToken($usuario).'?cuenta='.$usuario->id)->assertOk();
    }

    private function caducado(): TestResponse
    {
        return $this->get(self::RESTABLECER.'/'.Str::random(64).'?cuenta='.$this->crearUsuario()->id)->assertOk();
    }

    private function solicitudEnEspera(): TestResponse
    {
        Mail::fake();
        for ($i = 0; $i < 6; $i++) {
            $this->post(self::SOLICITUD, ['email' => 'no-es-un-correo']);
        }

        return $this->get(self::SOLICITUD)->assertOk();
    }

    private function nuevaEnEspera(): TestResponse
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        for ($i = 0; $i < 6; $i++) {
            $this->post(self::RESTABLECER, ['token' => $token, 'cuenta' => $usuario->id, 'password' => 'corta', 'password_confirmation' => 'corta']);
        }

        return $this->get(self::RESTABLECER.'/'.$token.'?cuenta='.$usuario->id)->assertOk();
    }

    /** @return array<string, string> método de esta clase de cada pantalla nueva */
    private function pantallasNuevas(): array
    {
        return [
            'pedir' => 'solicitud',
            'revisa tu correo' => 'enviada',
            'contraseña nueva' => 'nueva',
            'enlace que ya no sirve' => 'caducado',
            'pedir en espera' => 'solicitudEnEspera',
            'contraseña nueva en espera' => 'nuevaEnEspera',
        ];
    }

    /**
     * Cada pantalla, empezando con la sesión y los limitadores limpios.
     *
     * @return array<string, string> nombre → HTML
     */
    private function html(array $pantallas): array
    {
        $paginas = [];
        foreach ($pantallas as $nombre => $metodo) {
            $this->flushSession();
            Cache::flush();
            $paginas[$nombre] = $this->{$metodo}()->getContent();
        }

        return $paginas;
    }

    /** @return array<string, string> nombre → HTML, con el login */
    private function todas(): array
    {
        $paginas = $this->html($this->pantallasNuevas());
        $this->flushSession();
        $paginas['inicio de sesión'] = $this->get('/login')->assertOk()->getContent();

        return $paginas;
    }

    private function assertNoSeIndexa(string $html, string $nombre): void
    {
        $meta = $this->xpath($html)->query('//head/meta[@name="robots"]')->item(0);
        $this->assertNotNull($meta, "{$nombre}: lleva meta robots.");
        $this->assertStringContainsString('noindex', $meta->getAttribute('content'), "{$nombre}: no se indexa.");
    }

    public function test_las_pantallas_nuevas_no_se_indexan(): void
    {
        foreach ($this->html($this->pantallasNuevas()) as $nombre => $html) {
            $this->assertNoSeIndexa($html, $nombre);
        }
    }

    public function test_el_login_no_se_indexa(): void
    {
        $this->assertNoSeIndexa($this->get('/login')->assertOk()->getContent(), 'inicio de sesión');
    }

    public function test_la_pagina_del_token_no_envia_referer(): void
    {
        foreach ($this->html(['contraseña nueva' => 'nueva', 'enlace que ya no sirve' => 'caducado', 'en espera' => 'nuevaEnEspera']) as $nombre => $html) {
            $this->assertSame(1, preg_match('/<meta\s+name="referrer"\s+content="no-referrer"\s*\/?>/', $html, $meta, PREG_OFFSET_CAPTURE), "{$nombre}: lleva no-referrer (TM7).");
            $posicion = $meta[0][1];
            foreach (['<link', '<script', '<img'] as $recurso) {
                $primero = strpos($html, $recurso);
                $this->assertTrue($primero === false || $posicion < $primero, "{$nombre}: no-referrer va antes de cualquier {$recurso}.");
            }
        }
    }

    public function test_sin_recursos_externos(): void
    {
        $propio = parse_url(url('/'), PHP_URL_HOST);

        foreach ($this->todas() as $nombre => $html) {
            $this->assertStringNotContainsString('fonts.googleapis.com', $html, "{$nombre}: sin Google Fonts.");
            $xpath = $this->xpath($html);
            foreach (['//link/@href', '//script/@src', '//img/@src', '//iframe/@src'] as $consulta) {
                foreach ($xpath->query($consulta) as $atributo) {
                    $host = parse_url($atributo->value, PHP_URL_HOST);
                    $this->assertTrue($host === null || $host === $propio, "{$nombre}: {$atributo->value} es de otro sitio.");
                }
            }
        }

        $this->assertFileExists(public_path(self::HOJA));
        $this->assertDoesNotMatchRegularExpression('#url\(\s*[\'"]?(https?:)?//#', file_get_contents(public_path(self::HOJA)), 'acceso.css no carga nada de otro sitio.');
    }

    public function test_sin_estilos_en_linea(): void
    {
        foreach ($this->todas() as $nombre => $html) {
            $xpath = $this->xpath($html);
            $this->assertSame(0, $xpath->query('//*[@style]')->length, "{$nombre}: sin style=\"\".");
            $this->assertSame(0, $xpath->query('//style')->length, "{$nombre}: sin <style>.");
        }
    }

    public function test_sin_javascript(): void
    {
        foreach ($this->todas() as $nombre => $html) {
            $xpath = $this->xpath($html);
            $this->assertSame(0, $xpath->query('//script')->length, "{$nombre}: sin <script> (G-sin-js).");
            $this->assertSame(0, $xpath->query('//*[@onclick or @onsubmit or @onchange]')->length, "{$nombre}: sin manejadores en línea.");
        }
    }

    public function test_roles_de_los_avisos_y_errores(): void
    {
        foreach ($this->todas() as $nombre => $html) {
            $this->assertSame(1, $this->xpath($html)->query('//h1')->length, "{$nombre}: un solo h1.");
        }

        $paginas = $this->html($this->pantallasNuevas());
        $this->assertSame(1, $this->xpath($paginas['revisa tu correo'])->query('//*[@role="status"]')->length, '«Revisa tu correo» con role="status".');
        foreach (['enlace que ya no sirve', 'pedir en espera', 'contraseña nueva en espera'] as $nombre) {
            $this->assertSame(1, $this->xpath($paginas[$nombre])->query('//*[@role="alert"]')->length, "{$nombre}: role=\"alert\".");
        }

        // Errores junto a su campo: el del campo se enlaza con aria-describedby.
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        Cache::flush();
        $this->post(self::RESTABLECER, ['token' => $token, 'cuenta' => $usuario->id, 'password' => '1234567', 'password_confirmation' => 'otra-distinta']);
        $xpath = $this->xpath($this->get(self::RESTABLECER.'/'.$token.'?cuenta='.$usuario->id)->assertOk()->getContent());
        $entrada = $xpath->query('//input[@name="password"]')->item(0);
        $this->assertSame('true', $entrada->getAttribute('aria-invalid'));
        $ids = preg_split('/\s+/', trim($entrada->getAttribute('aria-describedby')));
        $errores = array_filter($ids, fn ($id) => $xpath->query('//*[@id="'.$id.'" and contains(@class, "error")]')->length === 1);
        $this->assertNotEmpty($errores, 'El error del campo está enlazado.');
        $ayudas = array_filter($ids, fn ($id) => str_contains((string) $xpath->query('//*[@id="'.$id.'"]')->item(0)?->textContent, 'Al menos 8 caracteres'));
        $this->assertNotEmpty($ayudas, 'Y la ayuda «Al menos 8 caracteres».');
        // Decorativos ocultos: el troquel, para el lector de pantalla.
        $this->assertSame('true', $xpath->query('//*[contains(@class, "troquel")]')->item(0)?->getAttribute('aria-hidden'));
    }

    public function test_la_espera_desactiva_el_boton(): void
    {
        foreach ($this->html(['pedir' => 'solicitudEnEspera', 'contraseña nueva' => 'nuevaEnEspera']) as $nombre => $html) {
            $xpath = $this->xpath($html);
            $botones = $xpath->query('//form//button[@type="submit"]');
            $this->assertSame(1, $botones->length, "{$nombre}: un botón de enviar.");
            $this->assertTrue($botones->item(0)->hasAttribute('disabled'), "{$nombre}: el botón queda desactivado (CA9).");
            $this->assertStringContainsString('Disponible en un minuto', $botones->item(0)->textContent);
            $this->assertStringContainsString('Espera un minuto', (string) $xpath->query('//*[@role="alert"]')->item(0)?->textContent);
        }
    }

    public function test_el_talon_muestra_el_telefono_configurado(): void
    {
        config(['clinica.contacto.telefono' => '999 111 2222']);

        foreach ($this->html($this->pantallasNuevas()) as $nombre => $html) {
            if (str_contains($nombre, 'contraseña nueva')) {
                continue; // Su talón habla del enlace: el diseño no pone ahí el teléfono.
            }
            $talon = (string) $this->xpath($html)->query('//dl[contains(@class, "talon")]')->item(0)?->textContent;
            $this->assertStringContainsString('999 111 2222', $talon, "{$nombre}: el teléfono sale de clinica.contacto (D16).");
            $this->assertStringNotContainsString('753 000 0000', $html, "{$nombre}: sin el teléfono escrito en la vista.");
        }
    }

    public function test_la_pagina_del_token_no_se_guarda_en_cache(): void
    {
        foreach (['nueva', 'caducado', 'nuevaEnEspera'] as $metodo) {
            $this->flushSession();
            Cache::flush();
            $this->assertStringContainsString('no-store', (string) $this->{$metodo}()->headers->get('Cache-Control'), "{$metodo}: no-store (TM11).");
        }
    }

    /** @return array<string, string> tokens declarados en `:root` de `acceso.css`, resueltos */
    private function tokensDeLaHoja(): array
    {
        $this->assertFileExists(public_path(self::HOJA));
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path(self::HOJA)));
        $this->assertSame(1, preg_match('/:root\s*\{(.*?)\}/s', $css, $root), 'acceso.css declara sus tokens en :root.');
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $root[1], $pares, PREG_SET_ORDER);

        $crudos = [];
        foreach ($pares as [, $nombre, $valor]) {
            $crudos[$nombre] = trim(preg_replace('/\s+/', ' ', $valor));
        }

        return array_map(fn (string $valor) => preg_match('/^var\((--[\w-]+)\)$/', $valor, $m) ? ($crudos[$m[1]] ?? $valor) : $valor, $crudos);
    }

    public function test_los_tokens_del_acceso_son_los_de_system_md(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        $delSistema = $this->tokensDelSistema();
        $tokens = $this->tokensDeLaHoja();

        foreach (array_keys(self::TOKENS_SOLO_DEL_ACCESO) as $nuevo) {
            $this->assertArrayHasKey($nuevo, $tokens, "acceso.css declara {$nuevo}.");
        }
        foreach (self::TOKENS_SOLO_DEL_ACCESO as $nombre => $valor) {
            $this->assertStringContainsString($nombre, $sistema, "system.md recoge {$nombre}.");
            $this->assertStringContainsString($valor, $sistema, "system.md da a {$nombre} el valor {$valor}.");
        }
        foreach ($tokens as $nombre => $valor) {
            $this->assertArrayHasKey($nombre, $delSistema, "{$nombre} no es un token del sistema.");
            $this->assertSame($delSistema[$nombre], $valor, "{$nombre} tiene otro valor que en el sistema.");
            $enElSistema = str_contains($sistema, $nombre) || (str_starts_with($nombre, '--space-') && str_contains($sistema, '--space-1'));
            $this->assertTrue($enElSistema, "system.md no recoge {$nombre}.");
        }
    }

    public function test_cada_var_del_acceso_tiene_su_token(): void
    {
        $tokens = $this->tokensDeLaHoja();
        $this->assertNotEmpty($tokens);
        $fuentes = [public_path(self::HOJA), resource_path('views/layouts/acceso.blade.php'), resource_path('views/auth/login.blade.php')];
        foreach (glob(resource_path('views/auth/recuperar/*.blade.php')) as $vista) {
            $fuentes[] = $vista;
        }

        foreach ($fuentes as $fuente) {
            $this->assertFileExists($fuente);
            preg_match_all('/var\((--[\w-]+)\)/', file_get_contents($fuente), $usados);
            foreach (array_unique($usados[1]) as $usado) {
                $this->assertArrayHasKey($usado, $tokens, basename($fuente).": var({$usado}) sin token en acceso.css (D13).");
            }
        }
    }

    public function test_acceso_css_sin_literales_fuera_de_root(): void
    {
        // El comprobador: falla con un literal fuera de las exenciones y pasa con ellas (D17, A29).
        foreach (['15.5px', '#123456', 'rgba(0, 0, 0, 0.1)', '1px solid var(--border)', 'calc(var(--space-6) + 3px)'] as $literal) {
            $this->assertFalse($this->usaSoloTokens($literal), "{$literal} es un literal.");
        }
        foreach (['var(--p600)', '0', 'auto', 'none', 'transparent', '100%', '1fr', 'calc(var(--space-8) * 2.5)', 'var(--border-width) dashed var(--p200)', 'linear-gradient(145deg, var(--p600), var(--p800))'] as $valido) {
            $this->assertTrue($this->usaSoloTokens($valido), "{$valido} usa solo tokens.");
        }

        $this->assertFileExists(public_path(self::HOJA));
        $revisadas = 0;
        // analizarCss deja fuera las @font-face (A35) y las condiciones de @media (D17).
        foreach ($this->reglasCss(self::HOJA) as [$selector, $cuerpo]) {
            if ($selector === ':root') {
                continue;
            }
            foreach ($this->declaracionesRevisadas($cuerpo) as [$propiedad, $valor]) {
                $revisadas++;
                $this->assertTrue($this->usaSoloTokens($valor), "{$selector} { {$propiedad}: {$valor} } tiene un literal.");
            }
        }
        $this->assertGreaterThan(20, $revisadas, 'La hoja declara colores, tamaños y espacios.');
    }

    public function test_los_valores_de_los_correos_son_tokens(): void
    {
        $usuario = $this->crearUsuario();

        foreach ([
            'enlace' => [new EnlaceRestablecimiento($usuario, Str::random(64)), 'correos/enlace-restablecimiento.blade.php'],
            'aviso' => [new ContrasenaCambiada($usuario), 'correos/contrasena-cambiada.blade.php'],
        ] as $nombre => [$correo, $vista]) {
            // Las plantillas no llevan style=""; el HTML enviado los lleva, de la hoja (D9).
            foreach ([$vista, 'correos/marco-acceso.blade.php'] as $plantilla) {
                $this->assertFileExists(resource_path('views/'.$plantilla));
                $this->assertStringNotContainsString('style=', file_get_contents(resource_path('views/'.$plantilla)), "{$plantilla}: sin style=\"\".");
            }

            $html = $correo->render();
            $estilos = $this->xpath($html)->query('//*[@style]');
            $this->assertGreaterThan(5, $estilos->length, "{$nombre}: la hoja se pasó a estilos en línea.");
            foreach ($estilos as $elemento) {
                foreach ($this->declaracionesRevisadas($elemento->getAttribute('style')) as [$propiedad, $valor]) {
                    $this->assertTrue($this->esValorDeToken($valor), "{$nombre}: {$propiedad}: {$valor} no es el valor de un token (D9).");
                }
            }
        }
    }

    public function test_la_vista_previa_no_existe_fuera_de_local(): void
    {
        $this->assertFalse($this->app->environment('local'));

        $this->get('/_disenio/041/pedir')->assertNotFound();
        $this->get('/_disenio/041/correo-enlace')->assertNotFound();
    }
}
