<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Comprobaciones automáticas de P9 para las salidas de la spec 044 (/analyze A6, T014): cada enlace
 * nuevo declara el alto de toque del sistema, las reglas nuevas solo usan tokens y las vistas que
 * toca la spec no ganan estilos en línea.
 *
 * El aspecto en el navegador (anchos, desbordes, foco) lo comprueban el usuario y
 * `docs/design/capturar.mjs --medir` (plan, Estrategia de pruebas).
 */
class NavegacionSalidasCssTest extends TestCase
{
    use ReglasCss;

    /** Estilos en línea de cada vista tocada antes de la spec (impl_base b7173e9): no pueden crecer. */
    private const ESTILOS_PREVIOS = [
        'layouts/app' => 14,
        'layouts/acceso' => 0,
        'layouts/publico' => 0,
        'publico/inicio' => 0,
        // Spec 045 (T033, D7): sin los style="" de los dos enlaces de la casilla (antes 17 y 6).
        'auth/register' => 15,
        'auth/registro-contacto' => 4,
        'auth/recuperar/nueva' => 0,
        'auth/recuperar/solicitud' => 0,
        'auth/recuperar/enviada' => 0,
        'auth/recuperar/caducado' => 0,
    ];

    /** Vistas nuevas de la spec: sin ningún estilo en línea. */
    private const VISTAS_NUEVAS = [
        'errors/plantilla',
        'errors/404',
        'errors/403',
        'errors/419',
        'errors/429',
        'errors/500',
    ];

    public static function enlacesConToque(): array
    {
        return [
            'volver al inicio del acceso' => ['.enlace-inicio', 'css/acceso.css'],
            'volver al inicio del registro' => ['.enlace-inicio', 'css/auth.css'],
            'enlace secundario del error' => ['.enlace-secundario', 'css/errores.css'],
        ];
    }

    #[DataProvider('enlacesConToque')]
    public function test_cada_enlace_nuevo_declara_el_alto_de_toque(string $selector, string $hoja): void
    {
        $cuerpo = $this->declaraciones($selector, $hoja);

        $this->assertNotSame('', $cuerpo, "{$hoja} define {$selector}.");
        $this->assertMatchesRegularExpression('/(^|;)\s*min-height\s*:\s*var\(--touch-min\)\s*;/', $cuerpo, "{$selector} en {$hoja} declara min-height: var(--touch-min).");
    }

    public static function reglasNuevas(): array
    {
        return [
            'volver al inicio del acceso' => ['.enlace-inicio', 'css/acceso.css'],
            'volver al inicio del registro' => ['.enlace-inicio', 'css/auth.css'],
            'enlace secundario del error' => ['.enlace-secundario', 'css/errores.css'],
            'aviso del sitio público' => ['.pub-aviso', 'css/publico.css'],
            // /review R14: el resto de reglas nuevas de la spec.
            'icono del enlace del acceso' => ['.enlace-inicio svg', 'css/acceso.css'],
            'icono del enlace del registro' => ['.enlace-inicio svg', 'css/auth.css'],
            'enlace del acceso con el puntero' => ['.enlace-inicio:hover', 'css/acceso.css'],
            'enlace del registro con el puntero' => ['.enlace-inicio:hover', 'css/auth.css'],
            'logo-enlace del acceso' => ['.logo-enlace', 'css/acceso.css'],
            'logo-enlace del registro' => ['.logo-enlace', 'css/auth.css'],
            'logo-enlace del error' => ['.logo-enlace', 'css/errores.css'],
            'enlaces secundarios del error' => ['.enlaces-secundarios', 'css/errores.css'],
            'enlace secundario con el puntero' => ['.enlace-secundario:hover', 'css/errores.css'],
            'icono del aviso' => ['.pub-aviso svg', 'css/publico.css'],
        ];
    }

    /** /review R1: sobre el panel de marca oscuro, el foco del logo-enlace es el inverso (blanco), no --p600. */
    public static function focosDelLogo(): array
    {
        return [
            'acceso' => ['css/acceso.css'],
            'registro' => ['css/auth.css'],
            'páginas de error' => ['css/errores.css'],
        ];
    }

    #[DataProvider('focosDelLogo')]
    public function test_el_foco_del_logo_enlace_es_el_inverso_sobre_el_panel_oscuro(string $hoja): void
    {
        $cuerpo = $this->declaraciones('.logo-enlace:focus-visible', $hoja);

        $this->assertNotSame('', $cuerpo, "{$hoja} define el foco de .logo-enlace.");
        $this->assertStringContainsString('var(--focus-ring-inverso)', $cuerpo, "{$hoja}: el foco del logo-enlace usa --focus-ring-inverso.");
        $this->assertStringNotContainsString('var(--focus-ring)', $cuerpo, "{$hoja}: y no el --focus-ring (--p600) sobre el panel --p600.");

        $raiz = $this->declaraciones(':root', $hoja === 'css/errores.css' ? 'css/app.css' : $hoja);
        $this->assertMatchesRegularExpression('/--focus-ring-inverso\s*:\s*var\(--white\)\s*;/', $raiz, "{$hoja}: --focus-ring-inverso vale var(--white), como en app.css.");
    }

    #[DataProvider('reglasNuevas')]
    public function test_las_reglas_nuevas_solo_usan_tokens(string $selector, string $hoja): void
    {
        $cuerpo = $this->declaraciones($selector, $hoja);

        $this->assertNotSame('', $cuerpo, "{$hoja} define {$selector}.");
        $this->assertSame([], $this->literales($cuerpo), "{$selector} en {$hoja} sin medidas, colores, pesos ni familias literales.");
        $this->assertSame([], $this->duraciones($cuerpo), "{$selector} en {$hoja} sin duraciones literales.");
    }

    /** Duraciones literales (s o ms), que P9 pide como token (como en `MovilPacienteTest`). */
    private function duraciones(string $cuerpo): array
    {
        preg_match_all('/(?<![\w-])\d+(\.\d+)?m?s\b/', $cuerpo, $m);

        return $m[0];
    }

    public function test_auth_css_toma_los_tokens_del_enlace_con_los_valores_de_app_css(): void
    {
        $raiz = $this->declaraciones(':root', 'css/auth.css');
        $sistema = $this->declaraciones(':root', 'css/app.css');

        // /review R15: todos los tokens que auth.css copia de app.css (spec 044, D8; R1 añade el foco inverso).
        $tokens = ['--touch-min', '--space-2', '--space-4', '--icon-md', '--text-meta', '--weight-strong',
            '--radius-sm', '--focus-ring', '--focus-ring-inverso', '--focus-ring-width', '--focus-ring-offset'];
        foreach ($tokens as $token) {
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:\s*([^;]+);/', $raiz, "auth.css define {$token}.");
            preg_match('/'.preg_quote($token, '/').'\s*:\s*([^;]+);/', $raiz, $suyo);
            preg_match('/'.preg_quote($token, '/').'\s*:\s*([^;]+);/', $sistema, $original);
            $this->assertSame(trim($original[1] ?? ''), trim($suyo[1] ?? ''), "{$token} vale lo mismo en auth.css que en app.css.");
        }
    }

    public function test_la_entrada_pagina_de_inicio_es_un_elemento_del_menu_con_toque_en_el_movil(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertMatchesRegularExpression('/<a\s[^>]*href="\{\{\s*route\(\'inicio\'\)\s*\}\}"[^>]*class="nav-item[^"]*"|<a\s[^>]*class="nav-item[^"]*"[^>]*href="\{\{\s*route\(\'inicio\'\)\s*\}\}"/', $layout, 'La entrada «Ver sitio web» es un .nav-item que lleva a route(\'inicio\').');

        $movil = '';
        foreach ($this->reglasCss('css/movil.css') as [$selectores, $cuerpo]) {
            if (in_array('.nav-item', array_map('trim', explode(',', $selectores)), true)) {
                $movil .= $cuerpo;
            }
        }
        $this->assertMatchesRegularExpression('/min-height\s*:\s*var\(--touch-min\)/', $movil, 'En el móvil, .nav-item declara el alto de toque.');
    }

    public function test_las_vistas_tocadas_no_ganan_estilos_en_linea(): void
    {
        foreach (self::ESTILOS_PREVIOS as $vista => $previos) {
            $html = file_get_contents(resource_path("views/{$vista}.blade.php"));
            $this->assertSame($previos, substr_count($html, 'style="'), "{$vista}: ningún style=\"\" nuevo.");
        }

        foreach (self::VISTAS_NUEVAS as $vista) {
            $ruta = resource_path("views/{$vista}.blade.php");
            $this->assertFileExists($ruta, "{$vista} existe.");
            $this->assertSame(0, substr_count(file_get_contents($ruta), 'style="'), "{$vista}: sin estilos en línea.");
        }
    }
}
