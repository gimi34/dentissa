<?php

namespace Tests\Feature;

use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 038 (CA7, CA8, CA10; P5, P9, P10; TM1): lo que comparten las páginas
 * públicas `/` y `/servicios`.
 *
 * Metadatos propios y una sola canónica absoluta en el dominio de producción,
 * sin `noindex`; un solo `h1` y títulos sin saltos; nombres accesibles;
 * ningún script; ningún recurso de otros sitios salvo el mapa de Google en
 * `/`; todo el contenido en el HTML; peso de `/` por debajo de 1 MB; la fuente
 * con su licencia; ninguna salida sin escapar; la página del colaborador y sus
 * imágenes retiradas; y todos los enlaces internos resuelven.
 */
class PaginasPublicasTest extends TestCase
{
    use DatabaseTransactions;

    private const DOMINIO = 'https://dentissa.app';

    private const METADATOS = [
        '/' => [
            'titulo' => 'Dentissa · Tu sonrisa, nuestra prioridad',
            'descripcion' => 'Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.',
            'canonica' => self::DOMINIO.'/',
        ],
        '/servicios' => [
            'titulo' => 'Servicios · Dentissa',
            'descripcion' => 'Todos los tratamientos de la consulta Dentissa en Lázaro Cárdenas, de la revisión general a la ortodoncia y las prótesis.',
            'canonica' => self::DOMINIO.'/servicios',
        ],
    ];

    private const VISTAS = [
        'resources/views/layouts/publico.blade.php',
        'resources/views/publico/inicio.blade.php',
        'resources/views/publico/servicios.blade.php',
    ];

    /** Host de las peticiones del test (el de `APP_URL` de `phpunit.xml`): también es propio. */
    private string $hostDePruebas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hostDePruebas = (string) parse_url(config('app.url'), PHP_URL_HOST);
        // La canónica sale del dominio de producción (/analyze A19).
        config(['app.url' => self::DOMINIO]);
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function meta(DOMXPath $xpath, string $nombre): ?string
    {
        $nodo = $xpath->query('//meta[@name="'.$nombre.'"]')->item(0);

        return $nodo?->getAttribute('content');
    }

    public function test_metadatos_de_cada_ruta_publica(): void
    {
        $registro = $this->xpath($this->get('/registro')->getContent());
        $vistos = [];

        foreach (self::METADATOS as $ruta => $esperado) {
            $respuesta = $this->withHeaders(['X-Forwarded-Host' => 'atacante.example'])->get($ruta);
            $respuesta->assertOk();
            $xpath = $this->xpath($respuesta->getContent());

            $this->assertSame($esperado['titulo'], trim($xpath->query('//title')->item(0)?->textContent ?? ''), "{$ruta}: título.");
            $this->assertSame($esperado['descripcion'], $this->meta($xpath, 'description'), "{$ruta}: descripción.");
            $canonicas = $xpath->query('//link[@rel="canonical"]');
            $this->assertSame(1, $canonicas->length, "{$ruta}: una sola canónica.");
            $this->assertSame($esperado['canonica'], $canonicas->item(0)->getAttribute('href'), "{$ruta}: canónica absoluta en el dominio de producción, aunque llegue otro host.");
            $this->assertStringNotContainsString('noindex', strtolower((string) $this->meta($xpath, 'robots')), "{$ruta}: indexable.");
            $this->assertStringNotContainsStringIgnoringCase('noindex', (string) $respuesta->headers->get('X-Robots-Tag'));
            // /review R27: idioma y viewport en cada ruta indexable.
            $this->assertSame('es', $xpath->query('//html')->item(0)?->getAttribute('lang'), "{$ruta}: lang=\"es\".");
            $this->assertStringContainsString('width=device-width', (string) $this->meta($xpath, 'viewport'), "{$ruta}: viewport.");

            $vistos[] = $esperado['titulo'];
            $vistos[] = $esperado['descripcion'];
        }

        $this->assertNotContains(trim($registro->query('//title')->item(0)?->textContent ?? ''), $vistos, 'Distintos de /registro.');
        // /review R5: /registro aún no tiene descripción (SE1.a); en cuanto la tenga, tampoco se repite.
        $descripcionRegistro = $this->meta($registro, 'description');
        if ($descripcionRegistro !== null) {
            $this->assertNotContains($descripcionRegistro, $vistos, 'Descripción distinta de la de /registro.');
        }
        $this->assertSame(count($vistos), count(array_unique($vistos)), 'Títulos y descripciones distintos entre sí.');
    }

    public function test_un_solo_h1(): void
    {
        foreach (array_keys(self::METADATOS) as $ruta) {
            $xpath = $this->xpath($this->get($ruta)->getContent());
            $this->assertSame(1, $xpath->query('//h1')->length, "{$ruta}: un solo h1.");
        }
    }

    public function test_titulos_en_orden(): void
    {
        // /analyze A11: ningún título salta de nivel.
        foreach (array_keys(self::METADATOS) as $ruta) {
            $xpath = $this->xpath($this->get($ruta)->getContent());
            $niveles = array_map(fn ($n) => (int) substr($n->nodeName, 1), iterator_to_array($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6')));
            $this->assertNotEmpty($niveles, "{$ruta}: tiene títulos.");
            $this->assertSame(1, $niveles[0], "{$ruta}: empieza por el h1.");
            for ($i = 1; $i < count($niveles); $i++) {
                $this->assertLessThanOrEqual($niveles[$i - 1] + 1, $niveles[$i], "{$ruta}: un h{$niveles[$i]} sigue a un h{$niveles[$i - 1]}.");
            }
        }
    }

    public function test_nombres_accesibles(): void
    {
        // /analyze A11: logos con nombre, mapa con título, imágenes con alt y decorativos ocultos.
        foreach (array_keys(self::METADATOS) as $ruta) {
            $xpath = $this->xpath($this->get($ruta)->getContent());
            $this->assertGreaterThan(0, $xpath->query('//*[@role="img"]')->length, "{$ruta}: el logo está como imagen con nombre.");
            foreach ($xpath->query('//*[@role="img"]') as $nodo) {
                $this->assertNotSame('', trim($nodo->getAttribute('aria-label')), "{$ruta}: cada role=img tiene nombre.");
            }
            foreach ($xpath->query('//iframe') as $marco) {
                $this->assertNotSame('', trim($marco->getAttribute('title')), "{$ruta}: el iframe tiene título.");
            }
            foreach ($xpath->query('//img') as $imagen) {
                $this->assertTrue($imagen->hasAttribute('alt'), "{$ruta}: cada img tiene alt (vacío si es decorativa).");
            }
            foreach ($xpath->query('//svg') as $svg) {
                $this->assertTrue($svg->getAttribute('aria-hidden') === 'true' || $svg->getAttribute('role') === 'img', "{$ruta}: cada svg es decorativo o tiene nombre.");
            }
        }
    }

    public function test_ninguna_pagina_publica_carga_scripts(): void
    {
        foreach (array_keys(self::METADATOS) as $ruta) {
            $xpath = $this->xpath($this->get($ruta)->assertOk()->getContent());
            $this->assertSame(0, $xpath->query('//script')->length, "{$ruta}: sin JavaScript (CA8, 0 kB de JS propio).");
            $this->assertSame(0, $xpath->query('//*[@onclick or @onload or @onerror or @onchange or @onsubmit]')->length, "{$ruta}: sin manejadores en línea.");
        }
    }

    public function test_el_contenido_va_en_el_html_del_servidor(): void
    {
        // /review R6: un servicio propio del test, para no depender del volcado.
        DB::table('servicios')->insert([
            'id' => (string) Str::uuid(), 'nombre' => 'Servicio038 Html', 'descripcion' => 'Descripción038 Html',
            'categoria' => 'Categoria038 Html', 'duracion_min' => 30, 'precio' => 100, 'activo' => 1,
        ]);
        $inicio = $this->get('/')->getContent();
        foreach (['Tu sonrisa, nuestra prioridad.', 'Sobre nosotros', 'Nuestros servicios', 'Categoria038 Html', '753 000 0000', 'Lunes a viernes', 'Cómo llegar'] as $texto) {
            $this->assertStringContainsString($texto, $inicio, "/ trae «{$texto}» en el HTML.");
        }
        $servicios = $this->get('/servicios')->getContent();
        $this->assertStringContainsString('Nuestros servicios', $servicios);
        $this->assertStringContainsString('Descripción038 Html', $servicios, '/servicios trae el catálogo en el HTML.');
    }

    /** Recursos que pide la página: src de img/iframe/source y hojas o íconos enlazados. */
    private function recursos(DOMXPath $xpath): array
    {
        $urls = [];
        foreach ($xpath->query('//*[@src]') as $n) {
            $urls[] = [$n->nodeName, $n->getAttribute('src')];
        }
        foreach ($xpath->query('//link[@href][@rel!="canonical"]') as $n) {
            $urls[] = ['link', $n->getAttribute('href')];
        }

        return $urls;
    }

    private function esExterno(string $url): bool
    {
        if (! preg_match('#^(https?:)?//#i', $url)) {
            return false;
        }

        $host = parse_url(str_starts_with($url, '//') ? 'https:'.$url : $url, PHP_URL_HOST);

        return ! in_array($host, [parse_url(self::DOMINIO, PHP_URL_HOST), $this->hostDePruebas], true);
    }

    public function test_sin_recursos_externos_salvo_el_mapa(): void
    {
        foreach (array_keys(self::METADATOS) as $ruta) {
            $externos = array_values(array_filter($this->recursos($this->xpath($this->get($ruta)->getContent())), fn ($r) => $this->esExterno($r[1])));
            if ($ruta === '/') {
                $this->assertCount(1, $externos, '/: solo el mapa es externo.');
                $this->assertSame('iframe', $externos[0][0]);
                $this->assertStringStartsWith('https://www.google.com/maps?', $externos[0][1]);
            } else {
                $this->assertSame([], $externos, "{$ruta}: ningún recurso externo.");
            }
        }

        // /review R13: todas las hojas que enlaza el layout (app.css y publico.css), no solo una.
        foreach ($this->hojas() as $ruta => $css) {
            $this->assertDoesNotMatchRegularExpression('/@import/i', $css, "{$ruta} no importa hojas.");
            preg_match_all('/url\(\s*[\'"]?([^\'")]+)/i', $css, $m);
            foreach ($m[1] as $url) {
                $this->assertFalse($this->esExterno($url), "{$ruta} no pide {$url}.");
            }
        }
    }

    /** Hojas de estilo que enlaza `/`, como `[ruta pública => contenido]`. */
    private function hojas(): array
    {
        $hojas = [];
        foreach ($this->xpath($this->get('/')->getContent())->query('//link[@rel="stylesheet"]') as $enlace) {
            $ruta = (string) parse_url($enlace->getAttribute('href'), PHP_URL_PATH);
            $this->assertFileExists(public_path(ltrim($ruta, '/')), "Existe {$ruta}.");
            $hojas[$ruta] = file_get_contents(public_path(ltrim($ruta, '/')));
        }
        $this->assertCount(2, $hojas, 'El layout público enlaza app.css y publico.css.');

        return $hojas;
    }

    public function test_los_recursos_propios_de_la_raiz_pesan_menos_de_un_mega(): void
    {
        $html = $this->get('/')->getContent();
        $total = strlen($html);
        $propios = array_filter($this->recursos($this->xpath($html)), fn ($r) => ! $this->esExterno($r[1]) && ! str_starts_with($r[1], 'data:'));
        $rutas = array_map(fn ($r) => parse_url($r[1], PHP_URL_PATH), $propios);
        foreach ($this->hojas() as $hoja => $css) {
            preg_match_all('/url\(\s*[\'"]?([^\'")]+)/i', $css, $m);
            foreach ($m[1] as $url) {
                $rutas[] = dirname($hoja).'/'.$url;
            }
        }
        foreach (array_unique($rutas) as $ruta) {
            $archivo = realpath(public_path(ltrim($ruta, '/')));
            $this->assertNotFalse($archivo, "Existe {$ruta}.");
            $total += filesize($archivo);
        }

        $this->assertLessThanOrEqual(1_000_000, $total, 'La primera visita a / pesa como mucho 1 MB (sin el mapa).');
    }

    public function test_la_fuente_va_con_su_licencia(): void
    {
        $this->assertFileExists(public_path('fonts/lexend/lexend-latin-400-normal.woff2'));
        $this->assertFileExists(public_path('fonts/lexend/lexend-latin-600-normal.woff2'));
        $this->assertStringContainsString('SIL Open Font License', file_get_contents(public_path('fonts/lexend/OFL.txt')));
    }

    public function test_las_vistas_publicas_no_usan_html_sin_escapar(): void
    {
        // TM1: el texto del catálogo nunca se imprime sin escapar.
        foreach (self::VISTAS as $vista) {
            $this->assertFileExists(base_path($vista));
            $this->assertStringNotContainsString('{!!', file_get_contents(base_path($vista)), "{$vista} no usa salida sin escapar.");
        }
    }

    public function test_la_pagina_del_colaborador_y_sus_imagenes_estan_retiradas(): void
    {
        $retirados = ['resources/views/landing/index.blade.php', 'public/images/cover-bg.jpg', 'public/images/logo-rosa.png'];
        foreach ($retirados as $archivo) {
            $this->assertFileDoesNotExist(base_path($archivo));
        }

        // Solo en resources/ y public/ (los documentos sí las citan; /analyze A19).
        $buscados = ['landing.index', 'cover-bg.jpg', 'logo-rosa.png'];
        foreach (['resources', 'public'] as $carpeta) {
            $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($carpeta), \FilesystemIterator::SKIP_DOTS));
            foreach ($iterador as $archivo) {
                if (! preg_match('/\.(php|css|js|html)$/', $archivo->getFilename())) {
                    continue;
                }
                $contenido = file_get_contents($archivo->getPathname());
                foreach ($buscados as $texto) {
                    $this->assertStringNotContainsString($texto, $contenido, "{$archivo->getPathname()} cita {$texto}.");
                }
            }
        }
    }

    public function test_todos_los_enlaces_internos_resuelven(): void
    {
        // /analyze A3: con y sin servicios activos, ningún enlace apunta a algo que no existe.
        foreach ([true, false] as $conServicios) {
            if (! $conServicios) {
                DB::table('servicios')->update(['activo' => 0]);
            }
            foreach (array_keys(self::METADATOS) as $ruta) {
                $xpath = $this->xpath($this->get($ruta)->assertOk()->getContent());
                $this->assertGreaterThan(0, $xpath->query('//a[@href]')->length, "{$ruta}: tiene enlaces.");
                foreach ($xpath->query('//a[@href]') as $enlace) {
                    $href = $enlace->getAttribute('href');
                    if (preg_match('#^(tel:|mailto:)#', $href) || $this->esExterno($href)) {
                        continue;
                    }
                    if (str_starts_with($href, '#')) {
                        $this->assertSame(1, $xpath->query('//*[@id="'.substr($href, 1).'"]')->length, "{$ruta}: el ancla {$href} existe.");

                        continue;
                    }
                    $estado = $this->get(parse_url($href, PHP_URL_PATH) ?: '/')->getStatusCode();
                    $this->assertContains($estado, [200, 302], "{$ruta}: {$href} responde.");
                    if (($ancla = parse_url($href, PHP_URL_FRAGMENT)) !== null) {
                        $destino = $this->xpath($this->get(parse_url($href, PHP_URL_PATH) ?: '/')->getContent());
                        $this->assertSame(1, $destino->query('//*[@id="'.$ancla.'"]')->length, "{$ruta}: {$href} lleva a un ancla que existe.");
                    }
                }
            }
        }
    }

    public function test_el_layout_publico_con_y_sin_sesion(): void
    {
        // /analyze A7: el layout se prueba con una vista propia del test, sin las páginas reales.
        Route::middleware('web')->get('/_prueba/publico', fn () => Blade::render(
            "@extends('layouts.publico')\n@section('titulo', 'Prueba 038')\n@section('descripcion', 'Descripción de prueba 038')\n@section('canonica', 'https://dentissa.app/_prueba')\n@section('contenido')<h1>Contenido de prueba 038</h1>@endsection"
        ));

        $sinSesion = $this->get('/_prueba/publico');
        $sinSesion->assertOk();
        $xpath = $this->xpath($sinSesion->getContent());
        $this->assertSame('es', $xpath->query('//html')->item(0)->getAttribute('lang'));
        $this->assertSame('Prueba 038', trim($xpath->query('//title')->item(0)->textContent));
        $this->assertSame('Descripción de prueba 038', $this->meta($xpath, 'description'));
        $this->assertSame('https://dentissa.app/_prueba', $xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//script')->length);
        $this->assertGreaterThan(0, $xpath->query('//details/summary')->length, 'Menú del móvil con details y summary.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Servicios"][@href="'.url('/servicios').'"]')->length, 'Navegación de la 038: Servicios → /servicios.');
        $this->assertSame(0, $xpath->query('//a[normalize-space()="Promociones" or normalize-space()="Trabajos"]')->length, 'Sin enlaces de la 039 y la 040.');
        $this->assertSame('publico', $xpath->query('//body')->item(0)->getAttribute('class'));

        // Refinado D15: el menú del móvil es solo el icono de hamburguesa, con nombre y sin texto visible.
        $summary = $xpath->query('//details[contains(@class, "pub-menu")]/summary')->item(0);
        $this->assertNotNull($summary);
        $this->assertSame('', trim($summary->textContent), 'Sin la palabra «Menú» a la vista.');
        $this->assertSame('Menú', $summary->getAttribute('aria-label'));
        $this->assertSame(1, $xpath->query('svg[@aria-hidden="true"]', $summary)->length, 'El icono es un SVG oculto al lector.');

        // Refinado D15: pie en columnas con el horario y el contacto.
        $pie = $xpath->query('//footer')->item(0);
        $textoPie = preg_replace('/\s+/u', ' ', $pie->textContent);
        foreach (['Horarios', 'Contacto'] as $titulo) {
            $this->assertSame(1, $xpath->query('.//h2[normalize-space()="'.$titulo.'"]', $pie)->length, "El pie tiene la columna «{$titulo}».");
        }
        foreach (['Lunes a viernes 9:00–20:00', 'Sábados 9:00–14:00', 'Odontología integral para toda la familia en el centro de Lázaro Cárdenas.'] as $texto) {
            $this->assertStringContainsString($texto, $textoPie);
        }
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $pie)->length, 'El teléfono del pie se marca con un toque.');
        foreach (['Servicios', 'Crear cuenta', 'Iniciar sesión'] as $enlace) {
            $this->assertSame(1, $xpath->query('.//a[normalize-space()="'.$enlace.'"]', $pie)->length, "El pie enlaza «{$enlace}».");
        }

        $cliente = Usuario::create([
            'nombre' => 'Paciente', 'apellido' => 'De Pruebas', 'email' => 'layout.'.Str::random(8).'@example.test',
            'rol' => 'cliente', 'telefono' => '555-0000', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
        $conSesion = $this->xpath($this->actingAs($cliente)->get('/_prueba/publico')->getContent());
        $this->assertGreaterThan(0, $conSesion->query('//a[normalize-space()="Ir a mi panel"][@href="'.route('cliente.dashboard').'"]')->length);
        $this->assertSame(0, $conSesion->query('//a[normalize-space()="Iniciar sesión"]')->length);
    }
}
