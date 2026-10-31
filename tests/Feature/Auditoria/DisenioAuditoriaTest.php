<?php

namespace Tests\Feature\Auditoria;

use DOMDocument;
use DOMXPath;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Spec 020, D19 (TM15; A70, A73): la vista previa del diseño `/_disenio/020/{pantalla}`, para
 * medir las pantallas reales con `design/medir.mjs` sin sesión. Solo existe en local, usa datos
 * inventados y no consulta la base (exención de P3 aceptada por el usuario, 2026-10-28).
 *
 * Las rutas se registran como en cada entorno: se cambia el entorno, se vacía la colección y se
 * vuelve a cargar `routes/web.php`.
 */
class DisenioAuditoriaTest extends TestCase
{
    private const PANTALLAS = ['consulta', 'vacia', 'filtro', 'menu', 'error'];

    private function rutasComo(string $entorno): void
    {
        $this->app['env'] = $entorno;
        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function test_la_vista_previa_no_existe_fuera_de_local(): void
    {
        foreach (['production', 'testing'] as $entorno) {
            $this->rutasComo($entorno);

            foreach (self::PANTALLAS as $pantalla) {
                $this->get("/_disenio/020/{$pantalla}")->assertNotFound();
            }
            $this->assertFalse(collect($this->app['router']->getRoutes()->getRoutes())->contains(fn ($ruta) => str_starts_with($ruta->uri(), '_disenio')), "Sin la ruta en {$entorno}.");
        }
    }

    public function test_la_vista_previa_no_consulta_la_base(): void
    {
        $this->rutasComo('local');
        $consultas = [];
        DB::listen(function ($consulta) use (&$consultas) {
            $consultas[] = $consulta->sql;
        });

        foreach (self::PANTALLAS as $pantalla) {
            $this->get("/_disenio/020/{$pantalla}")->assertOk();
        }

        $this->assertSame([], $consultas, 'La vista previa no lee la base.');
    }

    public function test_la_vista_previa_marca_auditoria_en_el_menu(): void
    {
        $this->rutasComo('local');

        $xpath = $this->xpath($this->get('/_disenio/020/consulta')->assertOk()->getContent());
        $enlace = $xpath->query('//a[@href="/admin/auditoria"][contains(concat(" ", @class, " "), " nav-item ")]')->item(0);
        $this->assertNotNull($enlace);
        $this->assertContains('active', explode(' ', $enlace->getAttribute('class')));
        $this->assertSame('page', $enlace->getAttribute('aria-current'));
        $this->assertSame(1, $xpath->query('//*[@aria-current="page"]')->length, 'Ninguna otra entrada marcada.');
        $this->assertSame(0, $xpath->query('//aside[@aria-hidden="true"]')->length);

        $sinBarra = $this->get('/_disenio/020/consulta?sin-barra=1')->assertOk()->getContent();
        $this->assertStringContainsString('<aside aria-hidden="true" class="sidebar"', $sinBarra);
    }

    public function test_la_vista_previa_del_menu_lo_abre(): void
    {
        $this->rutasComo('local');

        $xpath = $this->xpath($this->get('/_disenio/020/menu')->assertOk()->getContent());

        $this->assertContains('menu-abierto', explode(' ', $xpath->query('//html')->item(0)->getAttribute('class')));
        $this->assertSame('true', $xpath->query('//*[contains(concat(" ", @class, " "), " menu-btn ")]')->item(0)?->getAttribute('aria-expanded'));

        $cerrado = $this->xpath($this->get('/_disenio/020/consulta')->getContent());
        $this->assertNotContains('menu-abierto', explode(' ', (string) $cerrado->query('//html')->item(0)->getAttribute('class')));
    }
}
