<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 038 (CA9; P9): el CSS de las páginas públicas.
 *
 * Los tokens de la 038 en `app.css` son los de `system.md`; `publico.css` usa solo tokens
 * (fuera de `@font-face` y de las condiciones de `@media`); las vistas no llevan `style=""`; los
 * pares cumplen AA; enlaces, botones y el menú tienen el toque mínimo; el foco es el del
 * sistema; los cortes son los de 900 y 560 px y el movimiento solo corre sin
 * `prefers-reduced-motion: reduce`.
 *
 * Ronda 5 (system.md 1.9.0, plan D19–D21, «La ventana del diente»): superficie «Sitio
 * público» con Cormorant Garamond y Montserrat autoalojadas, tokens nuevos y sin los de la
 * ronda 3 que se retiran (salvo `--sombra-tarjeta-alta` y `--leading-publico`, que usa la
 * página de error de la 020, /analyze A35); la escena de la ventana sin velo cuando no está
 * viva; el movimiento bajo la mano solo sin movimiento reducido. Los casos de la portada sticky,
 * el velo, la franja y su pausa se retiran con ellas (D21).
 *
 * El comportamiento en un navegador real (anchos, desbordamiento, toque medido) lo comprueba
 * `docs/design/capturar.mjs --medir` (T089).
 */
class PaginasPublicasCssTest extends TestCase
{
    use ReglasCss;

    private const HOJA = 'css/publico.css';

    private const SIN_MOVIMIENTO = '@media (prefers-reduced-motion: no-preference)';

    /** Tokens de la 038 con su valor en `app.css` (system.md 1.7.0–1.9.0). */
    private const TOKENS = [
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
        // system.md 1.8.0 que se quedan en 1.9.0.
        '--radius-pill' => '999px',
        '--sombra-tarjeta-alta' => '0 14px 32px rgba(114, 36, 62, 0.16)',
        '--ease-out' => 'cubic-bezier(0.23, 1, 0.32, 1)',
        '--ease-expo' => 'cubic-bezier(0.16, 1, 0.3, 1)',
        '--duration-reveal' => '800ms',
        '--stagger' => '80ms',
        // system.md 1.9.0 (ronda 5, plan D19).
        '--font-publica' => '"Montserrat", ui-sans-serif, system-ui, sans-serif',
        '--font-display' => '"Cormorant Garamond", ui-serif, Georgia, serif',
        '--text-display' => 'clamp(52px, calc(52px + 68 * (100vw - 320px) / 1120), 120px)',
        '--text-statement' => 'clamp(28px, calc(28px + 16 * (100vw - 320px) / 1120), 44px)',
        '--text-seccion' => 'clamp(40px, calc(40px + 40 * (100vw - 320px) / 1120), 80px)',
        '--text-indice' => 'clamp(28px, calc(28px + 28 * (100vw - 320px) / 1120), 56px)',
        '--text-firma' => 'clamp(76px, calc(76px + 284 * (100vw - 320px) / 1120), 360px)',
        '--text-lead' => 'clamp(18px, calc(18px + 3 * (100vw - 320px) / 1120), 21px)',
        '--shadow-soft' => '0 1px 2px rgba(114, 36, 62, 0.05), 0 12px 32px -12px rgba(114, 36, 62, 0.24)',
        '--ease-in-out' => 'cubic-bezier(0.77, 0, 0.175, 1)',
        '--page-ancho' => '1440px',
        '--gutter' => 'clamp(16px, calc(16px + 40 * (100vw - 320px) / 1120), 56px)',
        '--space-seccion-publica' => 'clamp(80px, calc(80px + 88 * (100vw - 320px) / 1120), 168px)',
    ];

    /** Lo que `system.md` dice de cada token (los fluidos, sus extremos). */
    private const EN_EL_SISTEMA = [
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
        '--radius-pill' => ['999px'],
        '--sombra-tarjeta-alta' => ['0 14px 32px rgba(114, 36, 62, 0.16)'],
        '--ease-out' => ['cubic-bezier(0.23, 1, 0.32, 1)'],
        '--ease-expo' => ['cubic-bezier(0.16, 1, 0.3, 1)'],
        '--duration-reveal' => ['800ms'],
        '--stagger' => ['80ms'],
        '--font-publica' => ['"Montserrat", ui-sans-serif, system-ui, sans-serif'],
        '--font-display' => ['"Cormorant Garamond", ui-serif, Georgia, serif'],
        '--text-display' => ['52px', '120px'],
        '--text-statement' => ['28px', '44px'],
        '--text-seccion' => ['40px', '80px'],
        '--text-indice' => ['28px', '56px'],
        '--text-firma' => ['76px', '360px'],
        '--text-lead' => ['18px', '21px'],
        '--shadow-soft' => ['0 1px 2px rgba(114, 36, 62, 0.05), 0 12px 32px -12px rgba(114, 36, 62, 0.24)'],
        '--ease-in-out' => ['cubic-bezier(0.77, 0, 0.175, 1)'],
        '--page-ancho' => ['1440px'],
        '--gutter' => ['16px', '56px'],
        '--space-seccion-publica' => ['80px', '168px'],
    ];

    /** Tokens 1.8.0 de la ronda 3 que la ronda 5 retira (D21; A35 deja fuera los que usa la 020). */
    private const RETIRADOS = [
        '--text-portada', '--text-marquesina', '--leading-portada', '--tracking-portada', '--velo-portada',
        '--velo-portada-suave', '--sombra-tarjeta', '--icono-tarjeta', '--duration-marquesina',
    ];

    /** Fuentes del sitio público (D19): familia → [peso => archivo]. */
    private const FUENTES = [
        'Cormorant Garamond' => [500 => 'cormorant-garamond/cormorant-garamond-latin-500-normal.woff2', 600 => 'cormorant-garamond/cormorant-garamond-latin-600-normal.woff2'],
        'Montserrat' => [400 => 'montserrat/montserrat-latin-400-normal.woff2', 500 => 'montserrat/montserrat-latin-500-normal.woff2', 600 => 'montserrat/montserrat-latin-600-normal.woff2', 700 => 'montserrat/montserrat-latin-700-normal.woff2'],
    ];

    /** Llevan `--touch-min` como alto mínimo (plan, «Clases de los componentes»; /analyze A6). */
    private const TOQUE = [
        '.pub-nav a', '.pub-menu summary', '.pub-menu__panel a', '.pub-hero__facts a', '.pub-index a',
        '.pub-visit__tel', '.pub-foot__links a', '.pub-foot__info a', '.lb-indice a', '.c-btn',
    ];

    private const VISTAS = [
        'resources/views/layouts/publico.blade.php',
        'resources/views/publico/inicio.blade.php',
        'resources/views/publico/servicios.blade.php',
    ];

    /** El bloque `:root` de `app.css`. */
    private function raiz(): string
    {
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);

        return $root[1] ?? '';
    }

    public function test_los_tokens_038_coinciden_con_el_sistema(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        $raiz = $this->raiz();

        foreach (self::TOKENS as $token => $valor) {
            foreach (self::EN_EL_SISTEMA[$token] as $texto) {
                $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'`[\s\S]{0,60}?'.preg_quote($texto, '/').'/', $sistema, "system.md documenta {$token} con {$texto}.");
            }
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/', $raiz, "app.css define {$token}: {$valor}.");
            $this->assertSame(1, preg_match_all('/'.preg_quote($token, '/').'\s*:/', $raiz), "app.css declara {$token} una sola vez.");
        }
    }

    public function test_los_tokens_retirados_no_siguen(): void
    {
        // D21: la ronda 5 retira los tokens de la portada, la franja y la tarjeta de la ronda 3.
        $raiz = $this->raiz();
        $publico = file_get_contents(public_path(self::HOJA));
        foreach (self::RETIRADOS as $token) {
            $this->assertDoesNotMatchRegularExpression('/'.preg_quote($token, '/').'\s*:/', $raiz, "app.css ya no define {$token}.");
            $this->assertStringNotContainsString("var({$token})", $publico, "publico.css ya no usa {$token}.");
        }
    }

    public function test_el_error_generico_de_la_020_tiene_todas_sus_variables(): void
    {
        // /analyze A35: tras fusionar main, errores.css usa tokens que la 038 también tocaba.
        preg_match_all('/var\((--[\w-]+)/', file_get_contents(public_path('css/errores.css')), $usadas);
        $this->assertContains('--sombra-tarjeta-alta', $usadas[1], 'Control: errores.css usa la sombra de la tarjeta.');
        $this->assertContains('--leading-publico', $usadas[1], 'Control: y el interlineado público.');
        $raiz = $this->raiz();
        foreach (array_unique($usadas[1]) as $token) {
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:/', $raiz, "app.css declara {$token}, que usa errores.css.");
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

    public function test_font_face_declara_las_dos_familias_autoalojadas(): void
    {
        // Sustituye a «font-face declara Lexend autoalojada» (D19): seis caras, cada una con su woff2.
        preg_match_all('/@font-face\s*\{([^}]*)\}/', file_get_contents(public_path(self::HOJA)), $caras);
        $this->assertCount(6, $caras[1], 'Cormorant Garamond 500 y 600; Montserrat 400, 500, 600 y 700.');

        $vistas = [];
        foreach ($caras[1] as $cara) {
            $this->assertMatchesRegularExpression('/font-family\s*:\s*"([^"]+)"/', $cara);
            preg_match('/font-family\s*:\s*"([^"]+)"/', $cara, $familia);
            preg_match('/font-weight\s*:\s*(\d+)/', $cara, $peso);
            $this->assertArrayHasKey($familia[1], self::FUENTES, "{$familia[1]} es una familia del sitio público.");
            $archivo = self::FUENTES[$familia[1]][(int) ($peso[1] ?? 0)] ?? null;
            $this->assertNotNull($archivo, "{$familia[1]} {$peso[1]}: peso previsto.");
            $this->assertMatchesRegularExpression('~src\s*:\s*url\("?\.\./fonts/'.preg_quote($archivo, '~').'"?\)\s*format\("woff2"\)~', $cara, 'Archivo propio, en woff2.');
            $this->assertFileExists(public_path("fonts/{$archivo}"));
            $this->assertMatchesRegularExpression('/font-display\s*:\s*swap/', $cara);
            $vistas[] = "{$familia[1]} {$peso[1]}";
        }
        $this->assertCount(6, array_unique($vistas), 'Ninguna cara repetida.');
        $this->assertStringNotContainsString('Lexend', file_get_contents(public_path(self::HOJA)), 'Lexend se retira (D21).');
    }

    public function test_body_publico_usa_la_familia_publica(): void
    {
        $cuerpo = $this->declaraciones('body.publico', self::HOJA);
        $this->assertStringContainsString('font-family: var(--font-publica)', $cuerpo);
        $this->assertStringContainsString('display: block', $cuerpo, 'Anula el display: flex de app.css (plan D3).');
        $this->assertStringContainsString('background: var(--bg)', $cuerpo, 'El fondo del sitio público es --p50.');
        foreach (['.pub-hero h1', '.pub-sec h2'] as $titulo) {
            $this->assertStringContainsString('font-family: var(--font-display)', $this->declaraciones($titulo, self::HOJA), "{$titulo}: en Cormorant Garamond.");
        }
        $this->assertStringContainsString('font-size: var(--text-display)', $this->declaraciones('.pub-hero h1', self::HOJA));
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

    public function test_contraste_de_los_pares_de_la_ronda_5(): void
    {
        // design.md de la ronda 5, «Accesibilidad» (system.md 1.9.0, «Color»).
        $t = $this->tokensDeColor();
        foreach ([
            ['--text', '--bg', 'Texto sobre --p50'],
            ['--text', '--p400', '«Sobre nosotros» y botón principal'],
            ['--text', '--p200', 'Botón principal con el puntero'],
            ['--white', '--text', 'Botón de tinta'],
            ['--p600', '--bg', 'Texto secundario sobre --p50'],
            ['--p600', '--white', 'Texto de las promesas'],
            ['--text', '--p100', 'Índice con el puntero'],
            ['--p100', '--text', 'Tarjeta de la agenda'],
            ['--p600', '--p100', 'Pie'],
            ['--green-texto', '--white', 'Punto de «abierto» sobre la pastilla'],
        ] as [$texto, $fondo, $uso]) {
            $this->assertArrayHasKey($texto, $t, "{$texto} es un token de color.");
            $this->assertArrayHasKey($fondo, $t, "{$fondo} es un token de color.");
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($t[$texto], $t[$fondo]), "{$uso}: AA.");
        }
        $this->assertGreaterThanOrEqual(3, $this->contraste($t['--border-control'], $t['--bg']), 'Borde de «Menú» y del botón fantasma sobre --p50: 3:1.');
    }

    public function test_enlaces_botones_y_menu_tienen_el_toque_minimo(): void
    {
        foreach (self::TOQUE as $selector) {
            $cuerpo = $this->declaraciones($selector, self::HOJA);
            $this->assertNotSame('', $cuerpo, "publico.css tiene reglas para {$selector}.");
            $this->assertStringContainsString('min-height: var(--touch-min)', $cuerpo, "{$selector}: toque mínimo.");
        }
    }

    public function test_el_foco_es_el_del_sistema(): void
    {
        // design.md: el foco del sistema (--p600) y el inverso solo sobre la tinta (tarjeta de la agenda).
        $focos = array_filter($this->reglasCss(self::HOJA), fn ($r) => str_contains($r[0], ':focus'));
        $this->assertNotEmpty($focos);
        $selectores = [];
        foreach ($focos as [$selector, $cuerpo]) {
            $lista = array_map('trim', explode(',', $selector));
            $tinta = array_map(fn ($s) => str_contains($s, '.pub-book__card'), $lista);
            $this->assertCount(1, array_unique($tinta), "{$selector}: no mezcla selectores sobre la tinta con otros.");
            $anillo = $tinta[0] ? '--focus-ring-inverso' : '--focus-ring';
            $this->assertStringContainsString("outline: var(--focus-ring-width) solid var({$anillo});", $cuerpo, "{$selector}: anillo {$anillo}.");
            $this->assertStringContainsString('outline-offset: var(--focus-ring-offset);', $cuerpo, "{$selector}: separación del sistema.");
            $selectores = array_merge($selectores, $lista);
        }
        $this->assertNotEmpty(array_filter($selectores, fn ($s) => str_ends_with($s, 'a:focus-visible')), 'Los enlaces tienen foco visible.');
        $this->assertNotEmpty(array_filter($selectores, fn ($s) => str_ends_with($s, 'summary:focus-visible')), 'El menú tiene foco visible.');
        $this->assertContains('.pub-book__card a:focus-visible', $selectores, 'Sobre la tinta, el anillo inverso.');
    }

    public function test_cabecera_fija_menu_en_pildora_y_pie_en_rosa_claro(): void
    {
        // design.md, «Cabecera pública» y pie (ronda 5).
        $barra = $this->declaraciones('.pub-bar', self::HOJA);
        $this->assertStringContainsString('position: sticky', $barra, 'Fija arriba.');
        $this->assertStringContainsString('top: 0', $barra);
        $this->assertStringContainsString('background: var(--bg)', $barra);
        $this->assertStringContainsString('border-bottom-color: var(--border)', $this->declaraciones('.pub-bar.is-scrolled', self::HOJA), 'El filete aparece al bajar.');

        $summary = $this->declaraciones('.pub-menu summary', self::HOJA);
        $this->assertStringContainsString('border-radius: var(--radius-pill)', $summary, '«Menú» en píldora.');
        $this->assertStringContainsString('border: var(--border-width) solid var(--border-control)', $summary);

        $pie = $this->declaraciones('.pub-foot', self::HOJA);
        $this->assertStringContainsString('background: var(--p100)', $pie);
        $this->assertStringContainsString('color: var(--p600)', $pie);
        $this->assertStringContainsString('font-size: var(--text-firma)', $this->declaraciones('.pub-foot__word', self::HOJA), 'El nombre a todo el ancho.');
    }

    public function test_los_cortes_son_900_y_560(): void
    {
        $condiciones = [];
        foreach ($this->reglasCss(self::HOJA) as [, , $envolventes]) {
            foreach ($envolventes as $condicion) {
                // Los fotogramas (el trazo del diente) no son cortes.
                if (! str_starts_with($condicion, '@keyframes')) {
                    $condiciones[$condicion] = true;
                }
            }
        }
        $condiciones = array_keys($condiciones);

        $this->assertContains('@media (max-width: 899px)', $condiciones, 'Corte de la navegación, las columnas y el título fijo.');
        $this->assertContains('@media (max-width: 559px)', $condiciones, 'Corte de los botones y la foto de la escena.');
        $this->assertSame([], array_values(array_diff($condiciones, ['@media (max-width: 899px)', '@media (max-width: 559px)', self::SIN_MOVIMIENTO])), 'Sin otros cortes.');
    }

    public function test_interlineado_y_proporciones_van_con_token(): void
    {
        // /review R1 (P9): control positivo y negativo de los patrones nuevos de literalesPublicos().
        $this->assertSame(['line-height: 1.5', 'aspect-ratio: 454 / 135'], array_map('trim', $this->literalesPublicos('line-height: 1.5; aspect-ratio: 454 / 135;')));
        $this->assertSame([], $this->literalesPublicos('line-height: var(--leading-publico); aspect-ratio: var(--logo-ratio);'));

        $this->assertStringContainsString('line-height: var(--leading-publico)', $this->declaraciones('body.publico', self::HOJA));
        $this->assertStringContainsString('line-height: var(--leading-publico-h1)', $this->declaraciones('.pub-hero h1', self::HOJA));
        $this->assertStringContainsString('line-height: var(--leading-publico-h2)', $this->declaraciones('.pub-sec h2', self::HOJA));
        $this->assertStringContainsString('aspect-ratio: var(--logo-ratio)', $this->declaraciones('.pub-brand .logo', self::HOJA));
    }

    /** Declaraciones de un selector solo dentro de un corte. */
    private function enElCorte(string $selector, string $corte = '@media (max-width: 899px)'): string
    {
        $reglas = array_filter($this->reglasCss(self::HOJA), fn ($r) => in_array($corte, $r[2], true)
            && in_array($selector, array_map('trim', explode(',', $r[0])), true));

        return implode("\n", array_column($reglas, 1));
    }

    public function test_las_columnas_se_apilan_en_el_movil(): void
    {
        // design.md, «Proporciones»: presentación 1.25fr : 1fr y la ubicación 5fr : 7fr; por debajo de
        // 900 px, una columna sin solaparse.
        $this->assertStringContainsString('grid-template-columns: 1.25fr 1fr', $this->declaraciones('.pub-hero__grid', self::HOJA));
        $this->assertStringContainsString('grid-template-columns: 5fr 7fr', $this->declaraciones('.pub-visit__grid', self::HOJA));
        foreach (['.pub-hero__grid', '.pub-visit__grid'] as $rejilla) {
            $this->assertStringContainsString('grid-template-columns: 1fr', $this->enElCorte($rejilla), "{$rejilla}: en el móvil, una columna.");
        }
        $this->assertStringContainsString('max-width: var(--page-ancho)', $this->declaraciones('.pub-wrap', self::HOJA), 'Ancho de cartel.');
        $this->assertStringContainsString('padding-inline: var(--gutter)', $this->declaraciones('.pub-wrap', self::HOJA));
    }

    public function test_la_escena_solo_tapa_la_foto_cuando_esta_viva(): void
    {
        // M-1: sin JavaScript o con movimiento reducido, la foto entera y sin velo (design.md).
        $this->assertStringContainsString('display: none', $this->declaraciones('.pub-open:not(.is-live) .pub-open__veil', self::HOJA));
        $foto = $this->declaraciones('.pub-open__photo', self::HOJA);
        $this->assertStringContainsString('object-fit: cover', $foto);
        $this->assertStringContainsString('width: 100%', $foto);
        $vivas = array_filter($this->reglasCss(self::HOJA), fn ($r) => str_contains($r[0], '.is-live') && ! str_contains($r[0], ':not(.is-live)'));
        $this->assertNotEmpty($vivas, 'La escena viva tiene sus reglas.');
        foreach ($vivas as [$selector, $cuerpo]) {
            if (str_contains($cuerpo, 'transform')) {
                $this->assertStringContainsString('var(--p', $cuerpo, "{$selector}: el hueco crece con el progreso --p.");
            }
        }
        $this->assertStringContainsString('object-fit: contain', $this->enElCorte('.pub-open__photo', '@media (max-width: 559px)'), 'Por debajo de 560 px, la foto contenida.');
    }

    public function test_las_entradas_solo_ocultan_con_javascript(): void
    {
        // M-3 y M-4: sin JavaScript (sin la clase js) y con movimiento reducido, todo está visible. Se
        // buscan las reglas que ocultan o desplazan su contenido (fuera de los fotogramas).
        $oculta = '/opacity\s*:\s*0\s*;|transform\s*:\s*translateY\(|clip-path\s*:\s*inset\(0 0 100%/';
        $this->assertMatchesRegularExpression($oculta, '.js x { opacity: 0; }', 'Control: detecta la opacidad 0.');
        $this->assertDoesNotMatchRegularExpression($oculta, '.x { opacity: 0.14; }', 'Control: no confunde una opacidad parcial.');
        $ocultas = array_filter($this->reglasCss(self::HOJA), fn ($r) => preg_match($oculta, $r[1])
            && ! str_contains($r[0], '.is-in') && ! array_filter($r[2], fn ($c) => str_starts_with($c, '@keyframes')));
        $this->assertNotEmpty($ocultas, 'Hay entradas por sección.');
        $this->assertNotEmpty(array_filter($ocultas, fn ($r) => str_contains($r[0], '.pub-hero h1')), 'El titular entra por líneas.');
        foreach ($ocultas as [$selector, , $envolventes]) {
            foreach (array_map('trim', explode(',', $selector)) as $uno) {
                $this->assertStringStartsWith('.js ', $uno, "{$uno}: solo oculta con JavaScript.");
            }
            $this->assertContains(self::SIN_MOVIMIENTO, $envolventes, "{$selector}: y sin movimiento reducido.");
        }
        $this->assertStringContainsString('opacity: 1', $this->declaraciones('.js [data-reveal].is-in', self::HOJA), 'Al entrar, se ve.');
    }

    public function test_el_movimiento_bajo_la_mano_solo_sin_movimiento_reducido(): void
    {
        // Sustituye a «nada se levanta al pasar el puntero» (1.8.0): en la ronda 5 los botones ceden y
        // las filas del índice responden (M-5), pero solo sin movimiento reducido; con él, solo el color.
        foreach ($this->reglasCss(self::HOJA) as [$selector, $cuerpo, $envolventes]) {
            if (preg_match('/:hover|:focus|:active/', $selector) && preg_match('/transform\s*:/', $cuerpo)) {
                $this->assertContains(self::SIN_MOVIMIENTO, $envolventes, "{$selector}: se mueve solo sin movimiento reducido.");
            }
        }
        $this->assertStringContainsString('border-radius: var(--radius-pill)', $this->declaraciones('.c-btn', self::HOJA), 'Botones en píldora.');
        $principal = $this->declaraciones('.c-btn--primary', self::HOJA);
        $this->assertStringContainsString('background: var(--p400)', $principal);
        $this->assertStringContainsString('color: var(--text)', $principal);
        $this->assertStringContainsString('background: var(--p200)', $this->declaraciones('.c-btn--primary:hover', self::HOJA));
        $tinta = $this->declaraciones('.c-btn--ink', self::HOJA);
        $this->assertStringContainsString('background: var(--text)', $tinta);
        $this->assertStringContainsString('color: var(--white)', $tinta);
        $this->assertStringContainsString('background: var(--p100)', $this->declaraciones('.pub-index a:hover', self::HOJA), 'La fila del índice se llena de --p100.');
    }

    public function test_una_descripcion_vacia_no_deja_hueco(): void
    {
        // /review R24: un servicio sin descripción no muestra un <dd> vacío.
        $this->assertStringContainsString('display: none', $this->declaraciones('.lb-lista dd:empty', self::HOJA));
        // /review R30: con el <dd> oculto, el <dt> siguiente no puede caer en la columna de las descripciones.
        $this->assertStringContainsString('grid-column: 1', $this->declaraciones('.lb-lista dt', self::HOJA), 'Cada nombre empieza en la primera columna.');
    }

    public function test_las_transiciones_solo_corren_sin_movimiento_reducido(): void
    {
        $con = 0;
        foreach ($this->reglasCss(self::HOJA) as [$selector, $cuerpo, $envolventes]) {
            if (preg_match('/\b(transition|animation)(-[\w-]+)?\s*:|scroll-behavior\s*:\s*smooth/', $cuerpo)) {
                $this->assertContains(self::SIN_MOVIMIENTO, $envolventes, "{$selector}: el movimiento solo sin prefers-reduced-motion.");
                $this->assertMatchesRegularExpression('/var\(--(duration-(base|fast|reveal)|stagger)\)/', $cuerpo, "{$selector}: duración con token.");
                $con++;
            }
        }
        $this->assertGreaterThan(0, $con, 'Los botones, el índice, el trazo y las entradas tienen su transición (design.md).');
    }
}
