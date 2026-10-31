<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 020, CA11 (TM7): si el registro no puede auditar una acción crítica, la acción se detiene
 * con una página 503 genérica, sin detalles internos aunque `APP_DEBUG=true`, igual con y sin
 * sesión, sin el layout autenticado y sin recursos de otros sitios (`design.md`, `c4/error.html`).
 */
class ErrorAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const UUID = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->app->forgetInstance(Auditoria::class);

        // Una acción crítica a la que llega cualquiera: el 503 sin sesión (A52).
        Route::middleware('web')->get('/_pruebas/020/accion-critica', function (Auditoria $auditoria) {
            $auditoria->registrar('prueba_critica', 'exito', critico: true);

            return 'no debía llegar aquí';
        });
    }

    private function admin(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'Del Error', 'email' => 'error.'.Str::random(8).'@example.test',
            'rol' => 'admin', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    private function assertSinDetallesInternos(TestResponse $respuesta): void
    {
        $html = $respuesta->getContent();
        foreach ([
            'QueryException', 'AuditoriaNoDisponible', 'SQLSTATE', 'insert into', 'Stack trace', '/var/www', 'vendor/',
            AlmacenAuditoriaQueFalla::IP_DE_PRUEBA, AlmacenAuditoriaQueFalla::IDENTIFICADOR_DE_PRUEBA,
        ] as $interno) {
            $this->assertStringNotContainsString($interno, $html, "La página no muestra «{$interno}».");
        }
    }

    public function test_la_consulta_falla_cerrada_con_error_generico(): void
    {
        $respuesta = $this->actingAs($this->admin())->get('/admin/auditoria');

        $respuesta->assertStatus(503);
        $respuesta->assertSee('No se realizó la acción');
        $respuesta->assertSee('Identificador de la petición');
        $this->assertMatchesRegularExpression(self::UUID, $respuesta->getContent());
        $cabecera = $respuesta->headers->get('X-Request-Id');
        $this->assertNotNull($cabecera);
        $respuesta->assertSee($cabecera);
        $this->assertSinDetallesInternos($respuesta);
        $this->assertStringNotContainsString('No hay eventos', $respuesta->getContent(), 'No se muestra el registro.');
    }

    public function test_un_fallo_critico_sin_sesion_muestra_el_503(): void
    {
        $respuesta = $this->get('/_pruebas/020/accion-critica');

        $respuesta->assertStatus(503);
        $respuesta->assertSee('No se realizó la acción');
        $respuesta->assertDontSee('no debía llegar aquí');
        $this->assertSinDetallesInternos($respuesta);
        foreach (['class="sidebar"', 'nav-item', 'Cerrar sesión', 'menu-btn'] as $delLayout) {
            $this->assertStringNotContainsString($delLayout, $respuesta->getContent(), "Sin el layout autenticado («{$delLayout}»).");
        }

        $conSesion = $this->actingAs($this->admin())->get('/_pruebas/020/accion-critica');
        $conSesion->assertStatus(503);
        $this->assertStringNotContainsString('nav-item', $conSesion->getContent(), 'Igual con sesión.');
    }

    public function test_la_pagina_de_error_no_se_indexa(): void
    {
        $this->get('/_pruebas/020/accion-critica')
            ->assertStatus(503)
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    /** A77: el teléfono sale de `clinica.contacto`, la misma clave que la 041. */
    public function test_el_503_muestra_el_telefono_configurado(): void
    {
        config(['clinica.contacto.telefono' => '555 000 1234', 'clinica.contacto.telefono_enlace' => 'tel:+525550001234']);

        $respuesta = $this->get('/_pruebas/020/accion-critica');

        $respuesta->assertStatus(503);
        $respuesta->assertSee('555 000 1234');
        $respuesta->assertSee('href="tel:+525550001234"', false);
    }

    public function test_la_pagina_solo_pide_recursos_propios(): void
    {
        $html = $this->get('/_pruebas/020/accion-critica')->assertStatus(503)->getContent();

        $propio = parse_url((string) config('app.url'), PHP_URL_HOST);
        preg_match_all('/(?:src|href)\s*=\s*"([^"]+)"|url\(\s*[\'"]?([^\'")]+)/i', $html, $m);
        $direcciones = array_filter(array_merge($m[1], $m[2]));
        $this->assertNotEmpty($direcciones);
        foreach ($direcciones as $direccion) {
            if (str_starts_with($direccion, 'tel:')) {
                continue;
            }
            $host = parse_url($direccion, PHP_URL_HOST);
            $this->assertTrue($host === null || $host === $propio, "«{$direccion}» es de otro sitio.");
        }
        $this->assertStringNotContainsString('fonts.googleapis', $html);
    }
}
