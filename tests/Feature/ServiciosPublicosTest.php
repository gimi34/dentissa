<?php

namespace Tests\Feature;

use App\Models\Servicio;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 038 (CA2, CA6; TM1, TM2, TM6): la página pública `/servicios`.
 *
 * Lista los servicios activos del catálogo agrupados por categoría en orden
 * alfabético (los que no tienen categoría, al final en «Otros servicios»), con
 * su nombre y su descripción, sin precio, duración ni servicios inactivos.
 * El texto del catálogo lo escribe el administrador: sale siempre escapado.
 *
 * Los servicios de prueba usan categorías propias del test (sufijo 038); los
 * casos «sin activos» desactivan en la transacción todos los servicios,
 * también los del volcado (/analyze A12).
 */
class ServiciosPublicosTest extends TestCase
{
    use DatabaseTransactions;

    private function crearServicio(string $nombre, ?string $categoria, bool $activo = true, string $descripcion = ''): void
    {
        DB::table('servicios')->insert([
            'id' => (string) Str::uuid(),
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : "Descripción de {$nombre}",
            'categoria' => $categoria,
            'duracion_min' => 173,
            'precio' => 777.77,
            'activo' => $activo ? 1 : 0,
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

    /** Títulos de categoría (h2) de la lista, en el orden de la página. */
    private function categorias(DOMXPath $xpath): array
    {
        return array_map(fn ($n) => trim($n->textContent), iterator_to_array($xpath->query('//main//section/h2')));
    }

    public function test_activos_agrupados_por_categoria_en_orden(): void
    {
        $this->crearServicio('Delta038', 'Alfa038');
        $this->crearServicio('Charlie038', 'Alfa038');
        $this->crearServicio('Bravo038', 'Zeta038');
        $this->crearServicio('Eco038', null);
        $this->crearServicio('Foxtrot038', '');

        $respuesta = $this->get('/servicios');
        $respuesta->assertOk();
        $xpath = $this->xpath($respuesta->getContent());
        $categorias = $this->categorias($xpath);

        $this->assertLessThan(array_search('Zeta038', $categorias), array_search('Alfa038', $categorias), 'Categorías en orden alfabético.');
        $this->assertSame('Otros servicios', end($categorias), 'Los servicios sin categoría van al final.');
        $respuesta->assertSeeInOrder(['Alfa038', 'Charlie038', 'Delta038', 'Zeta038', 'Bravo038', 'Otros servicios', 'Eco038', 'Foxtrot038']);
        $respuesta->assertSee('Descripción de Charlie038');

        // A14: el índice de puertas dice cuántos servicios tiene cada categoría.
        $contador = $xpath->query('//nav[@aria-label="Categorías"]//a[contains(normalize-space(), "Alfa038")]//small');
        $this->assertSame(1, $contador->length);
        $this->assertSame('2', trim($contador->item(0)->textContent));
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Volver al inicio"][@href="'.url('/').'"]')->length);
    }

    public function test_solo_activos_sin_precio_ni_duracion(): void
    {
        $this->crearServicio('Visible038', 'Activa038');
        $this->crearServicio('Oculto038', 'Inactiva038', activo: false);

        $respuesta = $this->get('/servicios');

        $respuesta->assertSee('Visible038');
        $respuesta->assertDontSee('Oculto038');
        $respuesta->assertDontSee('Inactiva038');
        // /review R4: precio y duración sobre el texto de <main>, no sobre el HTML entero (las hojas
        // llevan `?v=<filemtime>` y un número de 10 cifras puede contener «173»).
        $principal = preg_replace('/\s+/u', ' ', $this->xpath($respuesta->getContent())->query('//main')->item(0)->textContent);
        $this->assertStringContainsString('Visible038', $principal);
        foreach (['777.77', '777,77', '173', ' min'] as $dato) {
            $this->assertStringNotContainsString($dato, $principal, "Sin precio ni duración: «{$dato}».");
        }
    }

    public function test_sin_servicios_activos_muestra_el_mensaje(): void
    {
        DB::table('servicios')->update(['activo' => 0]);

        $respuesta = $this->get('/servicios');

        $respuesta->assertOk();
        $respuesta->assertSee('Pronto publicaremos nuestros servicios.');
        $this->assertSame([], $this->categorias($this->xpath($respuesta->getContent())));
    }

    public function test_mayusculas_y_otros_servicios_se_agrupan(): void
    {
        // A13: la agrupación sigue a la colación (sin distinguir mayúsculas ni acentos) y
        // una categoría real «Otros servicios» se funde con los servicios sin categoría.
        $this->crearServicio('Uno038', 'Ortodoncia038');
        $this->crearServicio('Dos038', 'ortodoncia038');
        $this->crearServicio('Tres038', 'Otros servicios');
        $this->crearServicio('Cuatro038', null);

        $categorias = $this->categorias($this->xpath($this->get('/servicios')->getContent()));

        $this->assertCount(1, array_filter($categorias, fn ($c) => mb_strtolower($c) === 'ortodoncia038'));
        $this->assertCount(1, array_filter($categorias, fn ($c) => $c === 'Otros servicios'));
    }

    public function test_los_acentos_no_separan_categorias(): void
    {
        // /review R8: «Prótesis038» y «Protesis038» son la misma categoría (A13).
        $this->crearServicio('Cinco038', 'Prótesis038');
        $this->crearServicio('Seis038', 'Protesis038');

        $categorias = $this->categorias($this->xpath($this->get('/servicios')->getContent()));

        $this->assertCount(1, array_filter($categorias, fn ($c) => in_array($c, ['Prótesis038', 'Protesis038'], true)));
    }

    public function test_porcategoria_devuelve_los_activos_agrupados(): void
    {
        // A7: contrato del modelo que usan las dos páginas públicas.
        DB::table('servicios')->update(['activo' => 0]);
        $this->crearServicio('Beta038', 'Alfa038');
        $this->crearServicio('Alfa038 servicio', 'Alfa038');
        $this->crearServicio('Sin038', null);
        $this->crearServicio('Apagado038', 'Alfa038', activo: false);

        $grupos = Servicio::porCategoria();

        $this->assertSame(['Alfa038', 'Otros servicios'], $grupos->keys()->all());
        $this->assertSame(['Alfa038 servicio', 'Beta038'], $grupos['Alfa038']->pluck('nombre')->all());
        $primero = (array) $grupos['Alfa038']->first();
        $this->assertSame(['nombre', 'descripcion'], array_keys(array_diff_key($primero, ['categoria' => 1])), 'Solo nombre, descripción y categoría.');
    }

    public function test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel(): void
    {
        $this->crearServicio('ConSesion038', 'Sesion038');
        $cliente = Usuario::create([
            'nombre' => 'Paciente', 'apellido' => 'De Pruebas', 'email' => 'servicios.'.Str::random(8).'@example.test',
            'rol' => 'cliente', 'telefono' => '555-0000', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);

        $respuesta = $this->actingAs($cliente)->get('/servicios');

        $respuesta->assertOk();
        $respuesta->assertSee('ConSesion038');
        $xpath = $this->xpath($respuesta->getContent());
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Ir a mi panel"][@href="'.route('cliente.dashboard').'"]')->length);
        $this->assertSame(0, $xpath->query('//a[normalize-space()="Iniciar sesión"]')->length);
    }

    public function test_parametros_en_la_url_no_cambian_la_lista(): void
    {
        $this->crearServicio('Fijo038', 'Param038');
        $this->crearServicio('Apagado038', 'ParamInactiva038', activo: false);

        $base = $this->categorias($this->xpath($this->get('/servicios')->assertOk()->getContent()));
        $con = $this->get('/servicios?categoria=ParamInactiva038&activo=0&orden=precio');

        $con->assertOk();
        $this->assertContains('Param038', $base);
        $this->assertSame($base, $this->categorias($this->xpath($con->getContent())));
        $con->assertSee('Fijo038');
        $con->assertDontSee('Apagado038');
    }

    public function test_el_texto_del_catalogo_sale_escapado(): void
    {
        // TM1: lo que escribe el administrador no se ejecuta en las páginas públicas.
        $this->crearServicio('<img src=x onerror=alert(1)>Nombre038', '<b>Cat038</b>', descripcion: '<script>alert("038")</script>');

        foreach (['/servicios', '/'] as $ruta) {
            $html = $this->get($ruta)->getContent();
            $this->assertStringNotContainsString('<b>Cat038</b>', $html, "{$ruta}: la categoría sale escapada.");
            $this->assertStringContainsString('&lt;b&gt;Cat038&lt;/b&gt;', $html, "{$ruta}: la categoría se ve como texto.");
        }
        $html = $this->get('/servicios')->getContent();
        $this->assertStringNotContainsString('<script>alert("038")</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }

    public function test_una_sola_consulta_al_catalogo(): void
    {
        // TM6: cada página pública consulta el catálogo una sola vez.
        foreach (['/servicios', '/'] as $ruta) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get($ruta)->assertOk();
            $consultas = array_filter(DB::getQueryLog(), fn ($q) => preg_match('/from\s+`?servicios`?/i', $q['query']));
            DB::disableQueryLog();

            $this->assertCount(1, $consultas, "{$ruta} consulta el catálogo una vez.");
        }
    }
}
