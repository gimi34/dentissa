<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 038 (CA9; P9): el CSS de las páginas públicas.
 *
 * Los tokens nuevos de `app.css` son los de `system.md` 1.7.0; `publico.css` usa solo tokens
 * (fuera de `@font-face` y de las condiciones de `@media`); las vistas no llevan `style=""`; los
 * pares nuevos cumplen AA; enlaces, botones y el menú tienen el toque mínimo; el foco es el del
 * sistema; los cortes son los de 900 y 560 px y las transiciones solo corren sin
 * `prefers-reduced-motion: reduce`.
 *
 * El comportamiento en un navegador real (anchos, desbordamiento, toque medido) lo comprueba
 * `docs/design/capturar.mjs --medir` (T017, T031).
 */
class PaginasPublicasCssTest extends TestCase
{
    use ReglasCss;

    private const HOJA = 'css/publico.css';

    private const SIN_MOVIMIENTO = '@media (prefers-reduced-motion: no-preference)';

    /** Tokens de la 038 (system.md 1.7.0, plan D14) con su valor en `app.css`. */
    private const TOKENS = [
        '--font-publica' => '"Lexend", system-ui, sans-serif',
        '--text-publico-h1' => 'clamp(30px, calc(30px + 18 * (100vw - 320px) / 1120), 48px)',
        '--text-publico-h2' => 'clamp(22px, calc(22px + 8 * (100vw - 320px) / 1120), 30px)',
        '--text-publico-h3' => '19px',
        '--text-publico' => '17px',
        '--text-publico-small' => '15px',
        '--space-seccion' => 'clamp(40px, calc(40px + 56 * (100vw - 320px) / 1120), 96px)',
        '--measure' => '62ch',
        '--page-max' => '1180px',
        '--duration-base' => '0.25s',
        '--ease-puerta' => 'cubic-bezier(0.2, 0, 0, 1)',
        '--green-texto' => '#137A5A',
        '--focus-ring-inverso' => 'var(--white)',
        // system.md 1.7.2 (/review R1).
        '--leading-publico' => '1.5',
        '--leading-publico-h1' => '1.15',
        '--leading-publico-h2' => '1.25',
        '--logo-ratio' => '454 / 135',
    ];

    /** Lo que `system.md` dice de cada token (los fluidos, sus extremos). */
    private const EN_EL_SISTEMA = [
        '--font-publica' => ['"Lexend", system-ui, sans-serif'],
        '--text-publico-h1' => ['30px', '48px'],
        '--text-publico-h2' => ['22px', '30px'],
        '--text-publico-h3' => ['19px'],
        '--text-publico' => ['17px'],
        '--text-publico-small' => ['15px'],
        '--space-seccion' => ['40px', '96px'],
        '--measure' => ['62ch'],
        '--page-max' => ['1180px'],
        '--duration-base' => ['0.25s'],
        '--ease-puerta' => ['cubic-bezier(0.2, 0, 0, 1)'],
        '--green-texto' => ['#137A5A'],
        '--focus-ring-inverso' => ['var(--white)'],
        '--leading-publico' => ['1.5'],
        '--leading-publico-h1' => ['1.15'],
        '--leading-publico-h2' => ['1.25'],
        '--logo-ratio' => ['454 / 135'],
    ];

    /** Llevan `--touch-min` como alto mínimo (plan, «Clases de los componentes»; /analyze A6). */
    private const TOQUE = [
        '.pub-nav-d a:not(.c-btn)', '.pub-menu summary', '.pub-menu li a',
        '.lb-puertas a', '.lb-indice a', '.c-btn', '.c-link',
    ];

    private const VISTAS = [
        'resources/views/layouts/publico.blade.php',
        'resources/views/publico/inicio.blade.php',
        'resources/views/publico/servicios.blade.php',
    ];

    public function test_los_tokens_038_coinciden_con_el_sistema(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);

        foreach (self::TOKENS as $token => $valor) {
            foreach (self::EN_EL_SISTEMA[$token] as $texto) {
                $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'`[\s\S]{0,60}?'.preg_quote($texto, '/').'/', $sistema, "system.md documenta {$token} con {$texto}.");
            }
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/', $root[1] ?? '', "app.css define {$token}: {$valor}.");
        }
    }

    public function test_publico_css_solo_usa_tokens(): void
    {
        // Control positivo: cada patrón de literalesPublicos() encuentra lo que debe (/analyze A5).
        $this->assertSame(
            ['6px', '#fff', 'rgb(', '600', 'Arial', '0.3s', '150ms', '1rem', '2em', 'cubic-bezier('],
            array_values(array_map('trim', $this->literalesPublicos('padding: 6px; color: #fff; background: rgb(0,0,0); font-weight: 600; font-family: Arial; transition: opacity 0.3s cubic-bezier(0.1, 0, 0, 1); animation-delay: 150ms; margin: 1rem; gap: 2em;')))
        );
        $this->assertSame([], $this->literalesPublicos('font-family: var(--font-publica); transition: opacity var(--duration-base) var(--ease-puerta); padding: var(--space-4);'), 'Los tokens no son literales.');

        $this->assertFileExists(public_path(self::HOJA));
        foreach ($this->reglasCss(self::HOJA) as [$selector, $cuerpo]) {
            $this->assertSame([], $this->literalesPublicos($cuerpo), "{$selector}: solo tokens.");
        }

        // Fuera de @font-face, las únicas cifras en px son las de las condiciones de los cortes.
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path(self::HOJA)));
        $css = preg_replace('/@font-face\s*\{[^}]*\}/', '', $css);
        preg_match_all('/\d+(\.\d+)?px/', $css, $px);
        $this->assertSame([], array_values(array_diff($px[0], ['899px', '559px'])), 'Solo 899px y 559px, en las @media.');
        $this->assertDoesNotMatchRegularExpression('/!important/', $css);
    }

    public function test_font_face_declara_lexend_autoalojada(): void
    {
        $css = file_get_contents(public_path(self::HOJA));
        preg_match_all('/@font-face\s*\{([^}]*)\}/', $css, $caras);
        $this->assertCount(2, $caras[1], 'Lexend 400 y 600.');
        foreach ($caras[1] as $cara) {
            $this->assertMatchesRegularExpression('/font-family\s*:\s*"Lexend"/', $cara);
            $this->assertMatchesRegularExpression('/src\s*:\s*url\("?\.\.\/fonts\/lexend\/lexend-latin-(400|600)-normal\.woff2"?\)\s*format\("woff2"\)/', $cara, 'Archivo propio, en woff2.');
            $this->assertMatchesRegularExpression('/font-display\s*:\s*swap/', $cara);
        }
    }

    public function test_body_publico_usa_la_familia_publica(): void
    {
        $cuerpo = $this->declaraciones('body.publico', self::HOJA);
        $this->assertStringContainsString('font-family: var(--font-publica)', $cuerpo);
        $this->assertStringContainsString('display: block', $cuerpo, 'Anula el display: flex de app.css (plan D3).');
    }

    public function test_las_vistas_publicas_no_llevan_estilos_en_linea(): void
    {
        foreach (self::VISTAS as $vista) {
            $this->assertFileExists(base_path($vista));
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', file_get_contents(base_path($vista)), "{$vista} sin style=\"\".");
            $this->assertDoesNotMatchRegularExpression('/<style\b/i', file_get_contents(base_path($vista)), "{$vista} sin <style>.");
        }
        foreach (['/', '/servicios'] as $ruta) {
            $documento = new DOMDocument;
            libxml_use_internal_errors(true);
            $documento->loadHTML('<?xml encoding="utf-8"?>'.$this->get($ruta)->assertOk()->getContent());
            libxml_clear_errors();
            $this->assertSame(0, (new DOMXPath($documento))->query('//*[@style]|//style')->length, "{$ruta}: sin estilos en línea.");
        }
    }

    public function test_contraste_de_los_pares_nuevos(): void
    {
        $t = $this->tokensDeColor();
        foreach ([
            ['--green-texto', '--white', 'Éxito en texto sobre blanco'],
            ['--green-texto', '--bg', 'Éxito en texto sobre el fondo'],
            ['--p600', '--bg', 'Texto secundario público sobre el fondo'],
            // Refinado D15 (system.md 1.7.1): cabecera --p500 y pie --p600.
            ['--white', '--p500', 'Logo, enlaces e icono de la cabecera'],
            ['--white', '--p600', 'Texto y enlaces del pie'],
        ] as [$texto, $fondo, $uso]) {
            $this->assertArrayHasKey($texto, $t, "{$texto} es un token de color.");
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($t[$texto], $t[$fondo]), "{$uso}: AA.");
        }
    }

    public function test_enlaces_botones_y_menu_tienen_el_toque_minimo(): void
    {
        foreach (self::TOQUE as $selector) {
            $cuerpo = $this->declaraciones($selector, self::HOJA);
            $this->assertNotSame('', $cuerpo, "publico.css tiene reglas para {$selector}.");
            $this->assertStringContainsString('min-height: var(--touch-min)', $cuerpo, "{$selector}: toque mínimo.");
        }
    }

    /** Selector que se pinta sobre el rosa (cabecera o pie) y no dentro del panel blanco del menú (/analyze A21). */
    private function sobreElRosa(string $selector): bool
    {
        return (str_contains($selector, '.pub-cabecera') || str_contains($selector, '.lb-pie')) && ! str_contains($selector, '.pub-menu li');
    }

    public function test_el_foco_es_el_del_sistema(): void
    {
        $focos = array_filter($this->reglasCss(self::HOJA), fn ($r) => str_contains($r[0], ':focus'));
        $this->assertNotEmpty($focos);
        $selectores = [];
        foreach ($focos as [$selector, $cuerpo]) {
            $lista = array_map('trim', explode(',', $selector));
            $rosa = array_map(fn ($s) => $this->sobreElRosa($s), $lista);
            $this->assertCount(1, array_unique($rosa), "{$selector}: no mezcla selectores sobre el rosa con otros.");
            // Refinado D15: sobre --p500 y --p600, el anillo inverso; en el resto (también el panel del menú), el del sistema.
            $anillo = $rosa[0] ? '--focus-ring-inverso' : '--focus-ring';
            $this->assertStringContainsString("outline: var(--focus-ring-width) solid var({$anillo});", $cuerpo, "{$selector}: anillo {$anillo}.");
            $this->assertStringContainsString('outline-offset: var(--focus-ring-offset);', $cuerpo, "{$selector}: separación del sistema.");
            $selectores = array_merge($selectores, $lista);
        }
        $this->assertNotEmpty(array_filter($selectores, fn ($s) => str_ends_with($s, 'a:focus-visible')), 'Los enlaces tienen foco visible.');
        $this->assertNotEmpty(array_filter($selectores, fn ($s) => str_ends_with($s, 'summary:focus-visible')), 'El menú tiene foco visible.');
        foreach (['.pub-cabecera a:focus-visible', '.pub-cabecera summary:focus-visible', '.lb-pie a:focus-visible'] as $inverso) {
            $this->assertContains($inverso, $selectores, "{$inverso} lleva el anillo inverso.");
        }
        $this->assertContains('.pub-menu li a:focus-visible', $selectores, 'Los enlaces del panel blanco del menú vuelven al anillo del sistema (A21).');
    }

    public function test_cabecera_y_pie_en_rosa_con_el_menu_sin_marco(): void
    {
        // Refinado D15 (system.md 1.7.1), siguiendo la landing del colaborador.
        $this->assertStringContainsString('background: var(--p500)', $this->declaraciones('.pub-cabecera', self::HOJA), 'Cabecera en --p500.');
        $this->assertStringContainsString('background: var(--p600)', $this->declaraciones('.lb-pie', self::HOJA), 'Pie en --p600.');
        foreach (['.pub-cabecera .logo', '.lb-pie .logo'] as $logo) {
            $this->assertStringContainsString('background: var(--white)', $this->declaraciones($logo, self::HOJA), "{$logo}: logo en blanco.");
        }
        $this->assertStringContainsString('color: var(--white)', $this->declaraciones('.pub-nav-d a:not(.c-btn)', self::HOJA), 'Enlaces de la cabecera en blanco.');

        $summary = $this->declaraciones('.pub-menu summary', self::HOJA);
        $this->assertStringContainsString('border: 0', $summary, 'El icono del menú va sin contorno.');
        $this->assertStringContainsString('background: none', $summary, 'El icono del menú va sin recuadro.');
        $this->assertStringContainsString('color: var(--white)', $summary, 'Icono en blanco.');
        $this->assertStringContainsString('min-width: var(--touch-min)', $summary, 'Caja de toque de 44 × 44.');
    }

    public function test_los_cortes_son_900_y_560(): void
    {
        $condiciones = [];
        foreach ($this->reglasCss(self::HOJA) as [, , $envolventes]) {
            foreach ($envolventes as $condicion) {
                $condiciones[$condicion] = true;
            }
        }
        $condiciones = array_keys($condiciones);

        $this->assertContains('@media (max-width: 899px)', $condiciones, 'Corte del menú y la placa.');
        $this->assertContains('@media (max-width: 559px)', $condiciones, 'Corte de puertas, índice y botones.');
        $this->assertSame([], array_values(array_diff($condiciones, ['@media (max-width: 899px)', '@media (max-width: 559px)', self::SIN_MOVIMIENTO])), 'Sin otros cortes.');
    }

    public function test_interlineado_y_proporciones_van_con_token(): void
    {
        // /review R1 (P9): control positivo y negativo de los patrones nuevos de literalesPublicos().
        $this->assertSame(['line-height: 1.5', 'aspect-ratio: 454 / 135'], array_map('trim', $this->literalesPublicos('line-height: 1.5; aspect-ratio: 454 / 135;')));
        $this->assertSame([], $this->literalesPublicos('line-height: var(--leading-publico); aspect-ratio: var(--logo-ratio);'));

        $this->assertStringContainsString('line-height: var(--leading-publico)', $this->declaraciones('body.publico', self::HOJA));
        $this->assertStringContainsString('line-height: var(--leading-publico-h1)', $this->declaraciones('.t-h1', self::HOJA));
        $this->assertStringContainsString('line-height: var(--leading-publico-h2)', $this->declaraciones('.t-h2', self::HOJA));
        $this->assertStringContainsString('aspect-ratio: var(--logo-ratio)', $this->declaraciones('.logo', self::HOJA));
    }

    public function test_la_placa_de_la_direccion_queda_encima_del_mapa_en_el_movil(): void
    {
        // /review R2: por debajo de 900 px la placa deja de ser absoluta y debe pintarse sobre el iframe.
        $movil = array_filter($this->reglasCss(self::HOJA), fn ($r) => in_array('@media (max-width: 899px)', $r[2], true)
            && in_array('.lb-placa-dir', array_map('trim', explode(',', $r[0])), true));
        $this->assertNotEmpty($movil, 'Hay una regla de .lb-placa-dir para el móvil.');
        $this->assertStringContainsString('position: relative', implode("\n", array_column($movil, 1)), 'La placa posicionada se pinta encima del mapa.');
    }

    public function test_una_descripcion_vacia_no_deja_hueco(): void
    {
        // /review R24: un servicio sin descripción no muestra un <dd> vacío.
        $this->assertStringContainsString('display: none', $this->declaraciones('.lb-lista dd:empty', self::HOJA));
    }

    public function test_las_transiciones_solo_corren_sin_movimiento_reducido(): void
    {
        $con = 0;
        foreach ($this->reglasCss(self::HOJA) as [$selector, $cuerpo, $envolventes]) {
            if (preg_match('/\b(transition|animation)(-[\w-]+)?\s*:|scroll-behavior\s*:\s*smooth/', $cuerpo)) {
                $this->assertContains(self::SIN_MOVIMIENTO, $envolventes, "{$selector}: el movimiento solo sin prefers-reduced-motion.");
                $this->assertMatchesRegularExpression('/var\(--duration-(base|fast)\)/', $cuerpo, "{$selector}: duración con token.");
                $con++;
            }
        }
        $this->assertGreaterThan(0, $con, 'Las puertas tienen su transición (design.md).');
    }
}
