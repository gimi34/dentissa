<?php

namespace Tests\Feature;

use App\Models\Usuario;
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

    /** @return array<string, Usuario> */
    private function usuarios(): array
    {
        return ['admin' => $this->admin, 'dentista' => $this->dentista1, 'asistente' => $this->asistente, 'cliente' => $this->paciente1];
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
