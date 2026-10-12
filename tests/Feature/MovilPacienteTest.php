<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Menú común y pantallas del paciente en el móvil (spec 033): estructura del layout, reglas de
 * `movil.css` y de `app.css`, contrato de `menu.js` y bloques tocados de las vistas (P9).
 *
 * El comportamiento en un navegador real (anchos, cajas de toque, foco, Escape, `inert`) lo
 * comprueba `docs/design/capturar.mjs --medir` (plan D7).
 */
class MovilPacienteTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss;

    private const MOVIL = ['@media (max-width: 899px)'];

    private const CON_MOVIMIENTO = ['@media (max-width: 899px)', '@media (prefers-reduced-motion: no-preference)'];

    /** Tokens que añade la spec 033 (system.md 1.4.0, T001). */
    private const TOKENS_033 = [
        '--touch-min' => '44px', '--overlay' => 'rgba(0, 0, 0, 0.45)', '--menu-movil-w' => '300px',
        '--duration-fast' => '0.15s', '--topbar-h' => '60px', '--border-width-strong' => '2px',
        '--text-meta' => '13px', '--logo-max-w' => '160px', '--icon-md' => '18px', '--text-marca' => '16px',
    ];

    /** Enlaces del menú por rol, tal como están hoy (la 020 añadirá «Auditoría» al de admin). */
    private const SECCIONES = [
        'admin' => ['Home', 'Citas', 'Pacientes', 'Personal', 'Servicios', 'Reportes', 'Usuarios'],
        'dentista' => ['Home', 'Mis Citas', 'Pacientes', 'Expedientes'],
        'asistente' => ['Home', 'Mis Doctores', 'Citas', 'Pacientes'],
        'cliente' => ['Inicio', 'Mis Citas', 'Agendar Cita', 'Mi Perfil'],
    ];

    /** Panel de cada rol y el enlace del menú que debe quedar como sección actual. */
    private const PANELES = [
        'admin' => ['/admin', '/admin'],
        'dentista' => ['/dentista', '/dentista'],
        'asistente' => ['/asistente', '/asistente'],
        'cliente' => ['/cliente', '/cliente'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_cada_rol_tiene_el_boton_menu(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get(self::PANELES[$rol][0])->assertOk()->getContent());

            $botones = $xpath->query("//header[contains(@class,'topbar')]//button[contains(@class,'menu-btn')]");
            $this->assertSame(1, $botones->length, "{$rol}: un botón «Menú» en la barra superior.");
            $boton = $botones->item(0);
            $this->assertSame('button', $boton->getAttribute('type'), "{$rol}: type=button.");
            $this->assertSame('Menú', trim($boton->textContent), "{$rol}: su nombre es «Menú».");
            $this->assertSame('false', $boton->getAttribute('aria-expanded'), "{$rol}: empieza cerrado.");
            $this->assertSame('menu-principal', $boton->getAttribute('aria-controls'), "{$rol}: controla el menú.");

            $aside = $xpath->query("//aside[@id='menu-principal']");
            $this->assertSame(1, $aside->length, "{$rol}: el <aside> es el menú principal.");
            $this->assertSame('Menú', $aside->item(0)->getAttribute('aria-label'), "{$rol}: el <aside> tiene nombre.");
            $this->assertSame(1, $xpath->query("//aside[@id='menu-principal']//*[contains(@class,'sidebar-marca')][normalize-space()='Melissa López N.']")->length,
                "{$rol}: marca de texto en el panel.");
            $this->assertSame(1, $xpath->query("//aside[@id='menu-principal']//button[contains(@class,'menu-cerrar')][@type='button'][@aria-label='Cerrar menú']")->length,
                "{$rol}: botón «Cerrar menú» en el panel.");

            // El velo va entre </aside> y <main>, fuera de <main> (A19), y empieza oculto.
            $velo = $xpath->query("//aside[@id='menu-principal']/following-sibling::*[1]");
            $this->assertSame(1, $velo->length, "{$rol}: hay un elemento tras el <aside>.");
            $this->assertStringContainsString('menu-velo', $velo->item(0)->getAttribute('class'), "{$rol}: el velo sigue al <aside>.");
            $this->assertTrue($velo->item(0)->hasAttribute('hidden'), "{$rol}: el velo empieza oculto.");
            $this->assertSame('main', $xpath->query("//aside[@id='menu-principal']/following-sibling::*[2]")->item(0)?->nodeName, "{$rol}: <main> sigue al velo.");
        }
    }

    public function test_la_pagina_carga_la_hoja_y_el_script_del_movil(): void
    {
        $html = $this->actingAs($this->paciente1)->get('/cliente')->assertOk()->getContent();
        $cabecera = (string) strstr((string) strstr($html, '<head>'), '</head>', true);

        $app = strpos($cabecera, 'css/app.css?v=');
        $movil = strpos($cabecera, 'css/movil.css?v=');
        $this->assertNotFalse($app, 'app.css en <head>.');
        $this->assertNotFalse($movil, 'movil.css en <head>, con ?v=.');
        $this->assertGreaterThan($app, $movil, 'movil.css se enlaza después de app.css.');

        $this->assertMatchesRegularExpression('#<script src="[^"]*js/menu\.js\?v=\d+"></script>#', $cabecera,
            'menu.js en <head>, con ?v= y sin defer ni async.');
    }

    public function test_la_seccion_actual_lleva_aria_current(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            [$url, $actual] = self::PANELES[$rol];
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());

            // Solo dentro del <aside> (A37): otras partes de la página pueden tener su propio aria-current.
            $marcados = $xpath->query("//aside[@id='menu-principal']//a[@aria-current]");
            $this->assertSame(1, $marcados->length, "{$rol}: un solo enlace actual en el menú.");
            $this->assertSame('page', $marcados->item(0)->getAttribute('aria-current'));
            $this->assertSame($actual, $marcados->item(0)->getAttribute('href'), "{$rol}: es la sección actual.");
        }
    }

    public function test_el_bloque_del_menu_no_tiene_estilos_en_linea(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get(self::PANELES[$rol][0])->assertOk()->getContent());

            // Sobre el <aside> aunque aún no tenga id, para que el test no pase por no encontrarlo.
            $this->assertSame(1, $xpath->query('//aside')->length, "{$rol}: hay un <aside>.");
            $this->assertSame(0, $xpath->query('//aside/descendant-or-self::*[@style]')->length, "{$rol}: el <aside> sin style=.");
            $this->assertSame(0, $xpath->query("//*[contains(@class,'menu-btn') or contains(@class,'menu-velo')][@style]")->length, "{$rol}: «Menú» y el velo sin style= (A32).");
        }
    }

    /** Caracterización: el viewport ya existe y la adaptación depende de él (CA1). */
    public function test_la_pagina_tiene_viewport(): void
    {
        $xpath = $this->xpath($this->actingAs($this->paciente1)->get('/cliente')->assertOk()->getContent());

        $this->assertSame(1, $xpath->query("//head/meta[@name='viewport'][@content='width=device-width, initial-scale=1.0']")->length);
    }

    /** Caracterización: el menú del móvil es el mismo <aside>, con las mismas secciones (CA2). */
    public function test_el_menu_del_movil_tiene_las_mismas_secciones(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get(self::PANELES[$rol][0])->assertOk()->getContent());

            $enlaces = [];
            foreach ($xpath->query('//aside//nav//a') as $enlace) {
                $enlaces[] = trim(preg_replace('/\s+/', ' ', $enlace->textContent));
            }
            $this->assertSame(self::SECCIONES[$rol], $enlaces, "{$rol}: las secciones de hoy.");
            $this->assertSame(1, $xpath->query('//nav')->length, "{$rol}: un solo <nav> en la página.");
            $this->assertSame(1, $xpath->query("//aside//button[normalize-space()='Cerrar sesión']")->length, "{$rol}: «Cerrar sesión».");
            $this->assertStringContainsString($usuario->nombre.' '.$usuario->apellido, $xpath->query("//aside//*[contains(@class,'user-name')]")->item(0)?->textContent ?? '', "{$rol}: su nombre.");
        }
    }

    public function test_movil_css_adapta_el_marco_y_las_rejillas(): void
    {
        $this->assertDeclara('.main', 'margin-left: 0');
        foreach (['.grid-auto', '.grid-2', '.grid-3'] as $rejilla) {
            $this->assertDeclara($rejilla, 'grid-template-columns: 1fr');
        }
        $this->assertDeclara('.topbar', 'height: auto');
        $this->assertDeclara('.topbar', 'min-height: var(--topbar-h)');
        $this->assertDeclara('.topbar', 'flex-wrap: wrap');
        $this->assertDeclara('.topbar', 'padding: 0 var(--space-6)');
        $this->assertDeclara('.content', 'padding: var(--space-6)');
        // El banner del panel recorta con overflow: hidden; sus botones bajan de línea (A34).
        $this->assertDeclara('.welcome-banner', 'flex-wrap: wrap');
        $this->assertDeclara('.welcome-actions', 'flex-wrap: wrap');
    }

    public function test_el_menu_cerrado_no_ocupa_espacio(): void
    {
        $this->assertDeclara('html.menu-js .sidebar', 'position: fixed');
        $this->assertDeclara('html.menu-js .sidebar', 'width: min(var(--menu-movil-w), 85vw)');
        $this->assertDeclara('html.menu-js .sidebar', 'transform: translateX(-100%)');
        $this->assertDeclara('html.menu-js .sidebar', 'visibility: hidden');
        $this->assertDeclara('html.menu-js .sidebar', 'z-index: 110');
        // Anula el min-height: 100vh de app.css, más alto que la pantalla visible en el móvil (A29).
        $this->assertDeclara('html.menu-js .sidebar', 'min-height: 0');
        $this->assertDeclara('html.menu-abierto .sidebar', 'transform: none');
        $this->assertDeclara('html.menu-abierto .sidebar', 'visibility: visible');
        $this->assertDeclara('html.menu-abierto .menu-velo', 'display: block');
        $this->assertDeclara('html.menu-abierto .menu-velo', 'position: fixed');
        $this->assertDeclara('html.menu-abierto .menu-velo', 'background: var(--overlay)');
        $this->assertDeclara('html.menu-abierto .menu-velo', 'z-index: 100');
        $this->assertDeclara('html.menu-abierto body', 'overflow: hidden');
        // «Menú» y «Cerrar menú» solo se muestran con JS (A28, A40).
        $this->assertDeclara('html.menu-js .menu-btn', 'display: inline-flex');
        $this->assertDeclara('html.menu-js .menu-cerrar', 'display: inline-flex');
    }

    public function test_sin_js_el_menu_queda_en_el_flujo(): void
    {
        $this->assertDeclara('html:not(.menu-js) body', 'flex-direction: column');
        $this->assertDeclara('html:not(.menu-js) .sidebar', 'position: static');
        $this->assertDeclara('html:not(.menu-js) .sidebar', 'width: auto');
        $this->assertDeclara('html:not(.menu-js) .sidebar', 'min-height: 0');
        $this->assertDeclara('.sidebar-marca', 'display: block');

        // Ninguna regla sin html.menu-js muestra los botones (A40).
        foreach ($this->reglasCss('css/movil.css') as [$selectores, $cuerpo, $condiciones]) {
            if ($condiciones === [] || ! preg_match('/(^|;)\s*display\s*:/', $cuerpo)) {
                continue;
            }
            foreach (explode(',', $selectores) as $selector) {
                if (preg_match('/\.menu-(btn|cerrar)$/', trim($selector))) {
                    $this->assertStringStartsWith('html.menu-js ', trim($selector), "{$selector}: solo con JS.");
                }
            }
        }

        // Las dos reglas globales van antes de la media query, para que las de dentro ganen por orden.
        $css = file_get_contents(public_path('css/movil.css'));
        $this->assertLessThan(strpos($css, '@media'), strpos($css, '.sidebar-marca'), 'Las reglas globales, al principio.');
    }

    public function test_los_controles_tienen_toque_minimo(): void
    {
        foreach (['.btn', '.btn-sm', '.nav-item', '.btn-logout', '.menu-btn', '.menu-cerrar', '.field', 'select', 'textarea',
            'input:not([type=checkbox]):not([type=radio]):not([type=hidden])', '.pestanas-citas a', '.migas a'] as $control) {
            $this->assertDeclara($control, 'min-height: var(--touch-min)');
        }
        $this->assertSame('', $this->movil('input'), 'Las casillas no se estiran: sin regla para input a secas (A20).');
        foreach (['.menu-cerrar', '.pestanas-citas a', '.migas a'] as $control) {
            $this->assertDeclara($control, 'min-width: var(--touch-min)');
        }
        foreach (['.pestanas-citas a', '.migas a'] as $enlace) {
            $this->assertDeclara($enlace, 'display: inline-flex');
        }
        // La zona de la casilla la da su etiqueta, que gana a .formulario-accesible .field-label (A33, A41).
        $this->assertDeclara('.formulario-accesible .campo-casilla label', 'display: flex');
        $this->assertDeclara('.formulario-accesible .campo-casilla label', 'align-items: center');
        $this->assertDeclara('.formulario-accesible .campo-casilla label', 'min-height: var(--touch-min)');
    }

    public function test_los_textos_largos_parten_de_linea(): void
    {
        // anywhere y no break-word: una palabra sin espacios también reduce el ancho mínimo de un hijo
        // de fila o rejilla flexible (/review 033 R1).
        $this->assertDeclara('.content', 'overflow-wrap: anywhere');
        $this->assertDeclara('.user-name', 'white-space: normal');
        $this->assertDeclara('.user-name', 'overflow-wrap: anywhere');
    }

    public function test_el_menu_se_recorre_con_poca_altura(): void
    {
        $this->assertDeclara('html.menu-js .sidebar', 'height: 100dvh');
        $this->assertDeclara('.sidebar', 'overflow-y: auto');
    }

    public function test_el_menu_se_abre_visible_y_se_desplaza_entero(): void
    {
        // Al abrir solo se anima el desplazamiento: el panel es visible desde el primer instante y
        // el foco puede entrar (T029; --medir en T023).
        $this->assertDeclara('html.menu-abierto .sidebar', 'transition: transform var(--duration-fast)', self::CON_MOVIMIENTO);
        $this->assertStringNotContainsString('visibility', $this->movil('html.menu-abierto .sidebar', self::CON_MOVIMIENTO));
        // El panel se desplaza entero, no solo el <nav>, para alcanzar el último enlace y «Cerrar sesión».
        $this->assertDeclara('.sidebar-nav', 'flex: none');
    }

    public function test_fuera_del_movil_solo_hay_las_reglas_previstas(): void
    {
        $this->assertFileExists(public_path('css/movil.css'));
        $globales = [];
        foreach ($this->reglasCss('css/movil.css') as [$selector, $cuerpo, $condiciones]) {
            $this->assertContains($condiciones, [[], self::MOVIL, self::CON_MOVIMIENTO], "{$selector}: condición inesperada.");
            if ($condiciones === []) {
                $globales[$selector] = $this->normalizar($cuerpo);
            }
            if (str_contains($cuerpo, 'transition')) {
                $this->assertSame(self::CON_MOVIMIENTO, $condiciones, "{$selector}: la transición, solo con movimiento permitido.");
            }
        }

        $this->assertSame([
            '.menu-btn, .menu-cerrar, .menu-velo, .sidebar-marca' => 'display: none;',
            '.main, .grid-auto > *, .grid-2 > *, .grid-3 > *' => 'min-width: 0;',
        ], $globales, 'Fuera de la media query, solo ocultar lo nuevo y min-width: 0 (CA8).');
        $this->assertDeclara('html.menu-js .sidebar', 'transition: transform var(--duration-fast), visibility var(--duration-fast)', self::CON_MOVIMIENTO);
    }

    public function test_movil_css_solo_usa_tokens(): void
    {
        // Control positivo: literales() y el detector de duraciones encuentran lo que deben.
        $this->assertCount(4, $this->literales('padding: 6px; color: #fff; background: rgb(0,0,0); font-weight: 600;'));
        $this->assertSame(['0.15s', '150ms'], $this->duraciones('transition: all 0.15s; animation-delay: 150ms;'));

        $this->assertFileExists(public_path('css/movil.css'));
        foreach ($this->reglasCss('css/movil.css') as [$selector, $cuerpo]) {
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
            $this->assertSame([], $this->duraciones($cuerpo), "{$selector}: duraciones con token.");
        }

        // La única cifra en px es la de la condición de la media query.
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path('css/movil.css')));
        preg_match_all('/\d+(\.\d+)?px/', $css, $px);
        $this->assertSame(array_fill(0, count($px[0]), '899px'), $px[0]);
    }

    public function test_los_pares_del_menu_cumplen_aa(): void
    {
        $tokens = $this->tokensDeColor();
        // [selector, color, fondo]: el fondo es el de la propia regla o el del panel (--white).
        $pares = [
            ['.nav-item', '--text', '--white'],
            ['.nav-item.active', '--p600', '--p100'],
            ['.nav-label', '--p600', '--white'],
            ['.sidebar-marca', '--p600', '--white'],
            ['.btn-logout:hover', '--p600', '--p100'],
            ['.menu-btn', '--p600', '--white'],
            ['.menu-cerrar', '--p600', '--white'],
        ];
        foreach ($pares as [$selector, $color, $fondo]) {
            $this->assertDeclara($selector, "color: var({$color})");
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens[$color], $tokens[$fondo]), "{$selector}: {$color} sobre {$fondo}.");
        }
        $this->assertDeclara('.sidebar', 'background: var(--white)');
        $this->assertDeclara('.nav-item.active', 'background: var(--p100)');
        $this->assertDeclara('.btn-logout:hover', 'background: var(--p100)');

        // Bordes de los controles del panel: --border-control, ≥ 3:1 sobre blanco (D13).
        foreach (['.menu-btn', '.menu-cerrar', '.btn-logout'] as $control) {
            $this->assertDeclara($control, 'border: var(--border-width) solid var(--border-control)');
        }
        $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--border-control'], $tokens['--white']));
    }

    public function test_el_aspecto_de_los_botones_nuevos_usa_tokens(): void
    {
        foreach (['.menu-btn', '.menu-cerrar'] as $boton) {
            foreach (['align-items: center', 'justify-content: center', 'gap: var(--space-2)', 'padding: 0 var(--space-4)',
                'background: var(--white)', 'border-radius: var(--radius-sm)', 'font: inherit', 'font-size: var(--text-body)',
                'font-weight: var(--weight-strong)'] as $declaracion) {
                $this->assertDeclara($boton, $declaracion);
            }
            $this->assertDeclara("{$boton} svg", 'width: var(--icon-md)');
            $this->assertDeclara("{$boton} svg", 'height: var(--icon-md)');
        }
        $this->assertDeclara('.sidebar-marca', 'color: var(--p600)');
        $this->assertDeclara('.sidebar-marca', 'font-size: var(--text-marca)');
        $this->assertDeclara('.sidebar-marca', 'font-weight: var(--weight-strong)');
        $this->assertDeclara('.sidebar-logo', 'justify-content: space-between');
        $this->assertDeclara('.sidebar-logo', 'min-height: var(--topbar-h)');
        $this->assertDeclara('.sidebar-logo', 'padding: var(--space-3) var(--space-5)');
        // El logo con mix-blend-mode: screen desaparece sobre blanco: en el móvil, la marca de texto (A9).
        $this->assertDeclara('.sidebar-logo img', 'display: none');
    }

    public function test_la_seleccion_no_depende_solo_del_color(): void
    {
        $this->assertDeclara('.nav-item.active', 'box-shadow: inset var(--border-width-strong) 0 0 var(--p600)');
    }

    public function test_los_controles_del_menu_tienen_foco_visible(): void
    {
        foreach (['.menu-btn', '.menu-cerrar', '.nav-item', '.btn-logout'] as $control) {
            $this->assertDeclara("{$control}:focus-visible", 'outline: var(--focus-ring-width) solid var(--focus-ring)');
            $this->assertDeclara("{$control}:focus-visible", 'outline-offset: var(--focus-ring-offset)');
        }
        $tokens = $this->tokensDeColor();
        $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--focus-ring'], $tokens['--white']));
    }

    public function test_menu_js_cumple_su_contrato(): void
    {
        $archivo = public_path('js/menu.js');
        $this->assertFileExists($archivo);
        $js = file_get_contents($archivo);

        // Comprobación de texto del contrato del plan; el comportamiento lo mide capturar.mjs --medir.
        $this->assertMatchesRegularExpression('/document\.documentElement\.classList\.add\(\s*[\'"]menu-js[\'"]\s*\)/', $js, 'Marca html.menu-js al cargar.');
        foreach (["'Escape'", 'inert', 'aria-expanded', 'aria-current', 'getComputedStyle', '.menu-btn', '.menu-cerrar', '.menu-velo'] as $pieza) {
            $this->assertStringContainsString($pieza, $js, "menu.js usa {$pieza}.");
        }
        $this->assertMatchesRegularExpression('/\.focus\(/', $js, 'Mueve el foco al abrir y al cerrar.');
        // Al abrir, el foco se mueve cuando ya se ha pintado un fotograma con el panel visible: ni en
        // el mismo instante ni en el fotograma siguiente surte efecto (T030, medido con Playwright).
        $this->assertMatchesRegularExpression('/requestAnimationFrame\(\s*\(\)\s*=>\s*requestAnimationFrame\(\s*\(\)\s*=>\s*destino\.focus\(\)\s*\)\s*\)/', $js,
            'El foco al abrir va en un doble requestAnimationFrame.');
        foreach (['innerHTML', 'outerHTML', 'insertAdjacentHTML', 'eval(', 'new Function', 'max-width'] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $js, "menu.js no usa {$prohibido}.");
        }
    }

    public function test_los_bloques_tocados_no_tienen_estilos_en_linea(): void
    {
        $cita = $this->citaFuturaDelPaciente();

        foreach (['/cliente/citas' => 'Próximas', '/cliente/citas?filtro=pasadas' => 'Pasadas'] as $url => $activa) {
            $xpath = $this->xpath($this->actingAs($this->paciente1)->get($url)->assertOk()->getContent());
            $pestanas = $xpath->query("//*[@id='pestanas-citas']");
            $this->assertSame(1, $pestanas->length, "{$url}: bloque #pestanas-citas.");
            $this->assertStringContainsString('pestanas-citas', $pestanas->item(0)->getAttribute('class'), "{$url}: usa .pestanas-citas.");
            $this->assertSame(0, $xpath->query("//*[@id='pestanas-citas']/descendant-or-self::*[@style]")->length, "{$url}: pestañas sin style=.");
            $actuales = $xpath->query("//*[@id='pestanas-citas']//a[@aria-current='page']");
            $this->assertSame(1, $actuales->length, "{$url}: una sola pestaña actual.");
            $this->assertSame($activa, trim($actuales->item(0)->textContent), "{$url}: la actual es «{$activa}».");
            $this->assertStringContainsString('activa', $actuales->item(0)->getAttribute('class'), "{$url}: la actual lleva .activa.");
            $this->assertSame(1, $xpath->query("//*[@id='pestanas-citas']//a[contains(concat(' ', normalize-space(@class), ' '), ' activa ')]")->length, "{$url}: una sola .activa.");
        }

        foreach (["/cliente/citas/{$cita}", '/cliente/agendar'] as $url) {
            $xpath = $this->xpath($this->actingAs($this->paciente1)->get($url)->assertOk()->getContent());
            $migas = $xpath->query("//*[@id='migas']");
            $this->assertSame(1, $migas->length, "{$url}: bloque #migas.");
            $this->assertStringContainsString('migas', $migas->item(0)->getAttribute('class'), "{$url}: usa .migas.");
            $this->assertSame(0, $xpath->query("//*[@id='migas']/descendant-or-self::*[@style]")->length, "{$url}: migas sin style=.");
            $this->assertSame(1, $xpath->query("//*[@id='migas']//*[contains(@class,'migas-actual')]")->length, "{$url}: página actual en .migas-actual.");
        }
    }

    public function test_las_reglas_nuevas_de_app_css_solo_usan_tokens(): void
    {
        // Control positivo de literales() sobre un cuerpo de ejemplo.
        $this->assertCount(2, $this->literales('margin-bottom: 20px; color: #000;'));

        $reglas = [];
        foreach ($this->analizarCss($this->bloque033()) as [$selector, $cuerpo]) {
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
            $reglas[$selector] = $this->normalizar($cuerpo);
        }

        // Cada clase reproduce todas las declaraciones de su style="" de hoy (plan, «Bloques tocados»; A27).
        $esperadas = [
            '.sidebar-logo img' => ['width: 100%', 'max-width: var(--logo-max-w)', 'object-fit: contain', 'display: block', 'margin: 0 auto', 'mix-blend-mode: screen'],
            '.user-card-texto' => ['min-width: 0'],
            '.pestanas-citas' => ['display: flex', 'gap: var(--space-2)', 'margin-bottom: var(--space-7)', 'border-bottom: var(--border-width) solid var(--border)'],
            '.pestanas-citas a' => ['padding: var(--space-4) var(--space-1)', 'font-size: var(--text-body)', 'font-weight: var(--weight-strong)', 'text-decoration: none', 'color: var(--text-muted)', 'border-bottom: var(--border-width-strong) solid transparent'],
            '.pestanas-citas a:first-child' => ['margin-right: var(--space-7)'],
            '.pestanas-citas a.activa' => ['color: var(--p500)', 'border-bottom-color: var(--p400)'],
            '.migas' => ['display: flex', 'align-items: center', 'gap: var(--space-3)', 'margin-bottom: var(--space-7)', 'font-size: var(--text-meta)', 'color: var(--text-muted)'],
            '.migas a' => ['color: var(--text-muted)', 'text-decoration: none'],
            '.migas-actual' => ['color: var(--text)'],
        ];
        foreach ($esperadas as $selector => $declaraciones) {
            $this->assertArrayHasKey($selector, $reglas, "Falta {$selector} en el bloque /* Spec 033 */ de app.css.");
            $obtenidas = array_map('trim', array_filter(explode(';', $reglas[$selector])));
            sort($obtenidas);
            sort($declaraciones);
            $this->assertSame($declaraciones, $obtenidas, "{$selector}: exactamente las declaraciones de hoy.");
        }
    }

    public function test_los_colores_del_bloque_033_cumplen_aa(): void
    {
        $tokens = $this->tokensDeColor();
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--text-muted'], $tokens['--bg']), 'Pestaña y migas sobre el fondo.');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--p500'], $tokens['--bg']), 'Pestaña activa sobre el fondo.');
        $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--p400'], $tokens['--bg']), 'Indicador de la pestaña activa.');

        // La pestaña activa se distingue por su borde, no solo por el color (A32).
        $reglas = [];
        foreach ($this->analizarCss($this->bloque033()) as [$selector, $cuerpo]) {
            $reglas[$selector] = $this->normalizar($cuerpo);
        }
        $this->assertStringContainsString('border-bottom: var(--border-width-strong) solid transparent', $reglas['.pestanas-citas a'] ?? '');
        $this->assertStringContainsString('border-bottom-color: var(--p400)', $reglas['.pestanas-citas a.activa'] ?? '');
    }

    public function test_los_tokens_y_anchos_coinciden_con_el_sistema(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);

        foreach (self::TOKENS_033 as $token => $valor) {
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'`[^\n]{0,8}'.preg_quote($valor, '/').'/', $sistema, "system.md documenta {$token} = {$valor}.");
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/', $root[1] ?? '', "app.css define {$token}: {$valor}.");
        }

        $this->assertMatchesRegularExpression('/^widths: \[320, 390, 1440\]/m', $sistema, 'widths de system.md.');
        $this->assertMatchesRegularExpression('/^\s+widths: \[320, 390, 1440\]/m', file_get_contents(base_path('.ai/project.yaml')), 'design.widths de project.yaml.');
    }

    /** Caracterización: los campos ya abren el selector o teclado del móvil que les corresponde (CA5). */
    public function test_los_campos_conservan_su_tipo(): void
    {
        $cita = $this->citaFuturaDelPaciente();
        $campos = [
            '/cliente/agendar' => ['fecha' => 'date', 'hora' => 'time'],
            "/cliente/citas/{$cita}/reprogramar" => ['fecha' => 'date', 'hora' => 'time'],
            '/cliente/perfil' => ['telefono' => 'tel', 'email' => 'email', 'fecha_nacimiento' => 'date'],
        ];
        foreach ($campos as $url => $tipos) {
            $xpath = $this->xpath($this->actingAs($this->paciente1)->get($url)->assertOk()->getContent());
            foreach ($tipos as $nombre => $tipo) {
                $this->assertSame(1, $xpath->query("//input[@name='{$nombre}'][@type='{$tipo}']")->length, "{$url}: {$nombre} es type={$tipo}.");
            }
        }
    }

    /** @return array<string, Usuario> */
    private function usuarios(): array
    {
        return ['admin' => $this->admin, 'dentista' => $this->dentista1, 'asistente' => $this->asistente, 'cliente' => $this->paciente1];
    }

    /** Cuerpo de las reglas de `movil.css` con ese selector (dentro de una lista) y esas condiciones. */
    private function movil(string $selector, array $condiciones = self::MOVIL): string
    {
        $this->assertFileExists(public_path('css/movil.css'));
        $cuerpos = [];
        foreach ($this->reglasCss('css/movil.css') as [$selectores, $cuerpo, $suyas]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if ($suyas === $condiciones && in_array($selector, $lista, true)) {
                $cuerpos[] = $this->normalizar($cuerpo);
            }
        }

        return implode(' ', $cuerpos);
    }

    private function assertDeclara(string $selector, string $declaracion, array $condiciones = self::MOVIL): void
    {
        [$propiedad, $valor] = array_map('trim', explode(':', $declaracion, 2));
        $this->assertMatchesRegularExpression(
            '/(^|;)\s*'.preg_quote($propiedad, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/',
            $this->movil($selector, $condiciones),
            "{$selector} debe declarar «{$declaracion}» en movil.css."
        );
    }

    private function normalizar(string $cuerpo): string
    {
        return trim(preg_replace('/\s+/', ' ', $cuerpo));
    }

    /** Duraciones literales (s o ms), que P9 pide como token. */
    private function duraciones(string $cuerpo): array
    {
        preg_match_all('/(?<![\w-])\d+(\.\d+)?m?s\b/', $cuerpo, $m);

        return $m[0];
    }

    /** Bloque `/* Spec 033 *\/` de app.css, del comentario al final del archivo. */
    private function bloque033(): string
    {
        $css = file_get_contents(public_path('css/app.css'));
        $inicio = strpos($css, '/* Spec 033');
        $this->assertNotFalse($inicio, 'app.css debe tener el bloque /* Spec 033 */.');

        return substr($css, $inicio);
    }

    /** Cita agendada de P1 que empieza dentro de más de 24 h, con el reloj fijado. */
    private function citaFuturaDelPaciente(): string
    {
        $this->travelTo(Carbon::parse('2026-10-05 16:00:00', 'UTC'));

        return $this->cita($this->d1, $this->p1, 'agendada', '2026-10-07 10:00');
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
