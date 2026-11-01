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
 * un solo script propio (`publico.js` con `defer`, rediseño D16); ningún
 * recurso de otros sitios salvo el mapa de Google en `/`; todo el contenido en
 * el HTML; peso de `/` por debajo de 1 MB; las fuentes con su licencia y su
 * huella, y la foto de la marca como propia; ninguna salida sin escapar ni
 * sumideros de HTML en el script (TM1); la página del colaborador, Lexend, la
 * foto de Unsplash y los iconos de categoría retirados (D11, D21); y todos los
 * enlaces internos resuelven.
 *
 * Ronda 5 (D19–D21): `test_la_foto_de_portada_va_acreditada` pasa a
 * `test_la_foto_de_la_marca_es_la_propia`, `test_la_fuente_va_con_su_licencia`
 * a `test_las_fuentes_van_con_su_licencia` (con el SHA-256 de cada woff2,
 * /analyze A38, y un `OFL.txt` en cada carpeta, A37), los adornos son los de la
 * ronda 5 y el layout lleva «Menú» visible, «Crear cuenta» y el pie nuevo.
 *
 * Ronda 6 (D23, `c5`): la foto de la marca va en la arcada y en la escena «consulta» (el recorte deja de
 * usarse); las fuentes son Newsreader (500, 600 y cursiva 500) y Montserrat (400, 500 y 600), y cada
 * carpeta lleva exactamente los woff2 de la tabla de `system.md` (A43); Cormorant Garamond se retira; el
 * menú del móvil es solo el icono, con el nombre «Menú»; el pie ya no lleva el nombre a todo el ancho;
 * `/servicios` lleva la etiqueta y «Volver al inicio» en `.c-btn` (A41).
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

    private const SCRIPT = 'js/publico.js';

    /** Sumideros de HTML y de código que `publico.js` no puede usar (TM1, /analyze A27). */
    private const SUMIDEROS = '/\binnerHTML\b|\bouterHTML\b|\binsertAdjacentHTML\b|document\.write|\beval\s*\(|\bnew\s+Function\b/';

    /** La foto de la marca (D20, D23): la arcada y la escena «consulta» usan la foto entera. */
    private const FOTOS = ['images/brand/access.webp'];

    /** Las fuentes que carga el sitio público (D19), por carpeta. */
    private const FUENTES = [
        'newsreader' => ['newsreader-latin-500-normal.woff2', 'newsreader-latin-600-normal.woff2', 'newsreader-latin-500-italic.woff2'],
        'montserrat' => ['montserrat-latin-400-normal.woff2', 'montserrat-latin-500-normal.woff2', 'montserrat-latin-600-normal.woff2'],
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

    /** Comprueba que la página solo carga `publico.js`, con `defer` y sin código en línea. */
    private function soloElScriptPublico(DOMXPath $xpath, string $ruta): void
    {
        $scripts = $xpath->query('//script');
        $this->assertSame(1, $scripts->length, "{$ruta}: un solo script (D16).");
        $script = $scripts->item(0);
        $this->assertSame('/'.self::SCRIPT, parse_url($script->getAttribute('src'), PHP_URL_PATH), "{$ruta}: el script es el propio.");
        $this->assertTrue($script->hasAttribute('defer'), "{$ruta}: con defer, sin bloquear el render.");
        $this->assertSame('', trim($script->textContent), "{$ruta}: sin código en línea.");
    }

    public function test_solo_carga_el_script_publico_con_defer(): void
    {
        // Sustituye a «ninguna página pública carga scripts»: con el rediseño (D16) hay un solo
        // script propio; sin él la página está completa (CA8) y P10 sigue por debajo de 100 kB.
        foreach (array_keys(self::METADATOS) as $ruta) {
            $xpath = $this->xpath($this->get($ruta)->assertOk()->getContent());
            $this->soloElScriptPublico($xpath, $ruta);
            $this->assertSame(0, $xpath->query('//*[@onclick or @onload or @onerror or @onchange or @onsubmit]')->length, "{$ruta}: sin manejadores en línea.");
        }

        $this->assertFileExists(public_path(self::SCRIPT));
        $this->assertLessThanOrEqual(100_000, filesize(public_path(self::SCRIPT)), 'El JavaScript propio pesa como mucho 100 kB (P10).');
    }

    public function test_el_script_publico_no_usa_innerhtml(): void
    {
        // TM1 (/analyze A27): el script escribe solo con textContent; ningún sumidero de HTML ni de código.
        foreach (['el.innerHTML = x', 'el.outerHTML = x', 'el.insertAdjacentHTML("a", x)', 'document.write(x)', 'eval(x)', 'new Function(x)'] as $control) {
            $this->assertMatchesRegularExpression(self::SUMIDEROS, $control, "El patrón detecta «{$control}».");
        }
        $this->assertDoesNotMatchRegularExpression(self::SUMIDEROS, 'el.textContent = x; evaluar(x)', 'Y no confunde textContent.');

        $this->assertFileExists(public_path(self::SCRIPT));
        $codigo = file_get_contents(public_path(self::SCRIPT));
        $this->assertDoesNotMatchRegularExpression(self::SUMIDEROS, $codigo, 'publico.js no usa sumideros de HTML ni de código.');
        $this->assertStringContainsString('textContent', $codigo, 'publico.js escribe con textContent.');
    }

    /** La sección de `system.md` que empieza por ese título, hasta el siguiente `## `. */
    private function seccionDelSistema(string $titulo): string
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        $this->assertMatchesRegularExpression('/^## '.preg_quote($titulo, '/').'$/m', $sistema);

        return preg_split('/^## /m', substr($sistema, strpos($sistema, "\n## {$titulo}\n") + 1))[1];
    }

    public function test_la_foto_de_la_marca_es_la_propia(): void
    {
        // Sustituye a «la foto de portada va acreditada» (D17, A28): la foto de Unsplash se retira (D21)
        // y la de la marca es propia de la clínica (D20), con el SHA-256 en system.md. Ronda 6 (D23): la
        // arcada de la presentación y la escena «consulta» usan la foto entera.
        $xpath = $this->xpath($this->get('/')->getContent());
        $arcada = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " pub-arcada ")]//img');
        $this->assertSame(1, $arcada->length, 'La arcada lleva una sola foto.');
        $this->assertSame('/'.self::FOTOS[0], parse_url($arcada->item(0)->getAttribute('src'), PHP_URL_PATH));
        $escena = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " pub-consulta ")]//img');
        $this->assertSame(1, $escena->length, 'La escena lleva una sola foto.');
        $this->assertSame('/'.self::FOTOS[0], parse_url($escena->item(0)->getAttribute('src'), PHP_URL_PATH), 'La escena usa la misma foto (una sola descarga).');
        $this->assertSame('lazy', $escena->item(0)->getAttribute('loading'), 'La escena queda por debajo de la primera pantalla.');

        $seccion = $this->seccionDelSistema('Imágenes');
        foreach (self::FOTOS as $foto) {
            $this->assertFileExists(public_path($foto));
            $this->assertStringContainsString(hash_file('sha256', public_path($foto)), $seccion, "system.md lleva el SHA-256 de {$foto}.");
        }
        $this->assertMatchesRegularExpression('/Foto de la marca[^|]*\|[^|]*\|\s*Propia de la clínica/u', $seccion, 'system.md la da como propia de la clínica.');
    }

    public function test_los_adornos_del_rediseno_estan_ocultos(): void
    {
        // Ronda 6 (D23): los arcos de fondo de la presentación y de la agenda, los números de los pasos y
        // las flechas del índice no se anuncian.
        $xpath = $this->xpath($this->get('/')->getContent());
        foreach (['pub-arco', 'pub-book__arco', 'pub-paso__n', 'pub-index__go'] as $clase) {
            $nodos = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " '.$clase.' ")]');
            $this->assertGreaterThan(0, $nodos->length, "/: hay .{$clase}.");
            foreach ($nodos as $nodo) {
                $this->assertSame(1, $xpath->query('ancestor-or-self::*[@aria-hidden="true"]', $nodo)->length, "/: .{$clase} es decorativo.");
            }
        }
    }

    public function test_el_contenido_va_en_el_html_del_servidor(): void
    {
        // /review R6: un servicio propio del test, para no depender del volcado.
        DB::table('servicios')->insert([
            'id' => (string) Str::uuid(), 'nombre' => 'Servicio038 Html', 'descripcion' => 'Descripción038 Html',
            'categoria' => 'Categoria038 Html', 'duracion_min' => 30, 'precio' => 100, 'activo' => 1,
        ]);
        // Sobre el texto del HTML, no sobre el código: el titular lleva un salto y una cursiva (D16).
        $inicio = trim(preg_replace('/\s+/u', ' ', $this->xpath($this->get('/')->getContent())->query('//body')->item(0)->textContent));
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
        // Rediseño (D17): cada candidata del srcset de la foto cuenta (la mayor incluida).
        // Ronda 5 (D19): la imagen del SVG de la ventana del diente también se descarga.
        foreach ($xpath->query('//*[local-name()="image"][@href]') as $n) {
            $urls[] = ['image', $n->getAttribute('href')];
        }
        foreach ($xpath->query('//*[@srcset]') as $n) {
            foreach (explode(',', $n->getAttribute('srcset')) as $candidata) {
                $urls[] = [$n->nodeName, preg_split('/\s+/', trim($candidata))[0]];
            }
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

        $this->assertContains('/'.self::SCRIPT, $rutas, 'El peso cuenta publico.js (D16).');
        // Ronda 5 (D19, A37): la foto de la marca, su recorte y las seis fuentes del sitio público.
        foreach (self::FOTOS as $foto) {
            $this->assertContains('/'.$foto, $rutas, "El peso cuenta {$foto}.");
        }
        $normalizadas = array_map(fn ($ruta) => preg_replace('~/[^/]+/\.\./~', '/', $ruta), $rutas);
        foreach (self::FUENTES as $carpeta => $archivos) {
            foreach ($archivos as $archivo) {
                $this->assertContains("/fonts/{$carpeta}/{$archivo}", $normalizadas, "El peso cuenta {$archivo}.");
            }
        }
        $this->assertLessThanOrEqual(1_000_000, $total, 'La primera visita a / pesa como mucho 1 MB (sin el mapa).');
    }

    public function test_las_fuentes_van_con_su_licencia(): void
    {
        // Sustituye a «la fuente va con su licencia» (Lexend, retirada por D21). /analyze A38: cada
        // woff2 es el que se verificó en T047 (su SHA-256 está en system.md). A37: cada carpeta de
        // public/fonts/ (también la Roboto de la 020) lleva su OFL.txt.
        $tipografia = $this->seccionDelSistema('Tipografía');
        foreach (self::FUENTES as $carpeta => $archivos) {
            foreach ($archivos as $archivo) {
                $ruta = public_path("fonts/{$carpeta}/{$archivo}");
                $this->assertFileExists($ruta);
                $this->assertMatchesRegularExpression('~`public/fonts/'.preg_quote("{$carpeta}/{$archivo}", '~').'` \| \d+ \| `'.hash_file('sha256', $ruta).'`~', $tipografia, "system.md lleva el SHA-256 de {$archivo}.");
            }
        }

        // A43: cada carpeta de las fuentes públicas lleva exactamente los woff2 de la tabla.
        foreach (self::FUENTES as $carpeta => $archivos) {
            $hay = array_map('basename', glob(public_path("fonts/{$carpeta}/*.woff2")));
            sort($hay);
            $esperados = $archivos;
            sort($esperados);
            $this->assertSame($esperados, $hay, "public/fonts/{$carpeta}/ lleva exactamente los woff2 de system.md.");
        }

        $carpetas = glob(public_path('fonts/*'), GLOB_ONLYDIR);
        $this->assertNotEmpty($carpetas);
        foreach ($carpetas as $carpeta) {
            $this->assertFileExists("{$carpeta}/OFL.txt", basename($carpeta).' lleva su licencia.');
            $this->assertStringContainsString('SIL Open Font License', file_get_contents("{$carpeta}/OFL.txt"));
        }
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
        // D11, y en la ronda 5 (D21) Lexend, la foto de Unsplash y los iconos de categoría.
        $retirados = [
            'resources/views/landing/index.blade.php', 'public/images/cover-bg.jpg', 'public/images/logo-rosa.png',
            'public/fonts/lexend', 'public/images/portada-800.webp', 'public/images/portada-1600.webp',
            'resources/views/partials/icono-categoria.blade.php', 'public/fonts/cormorant-garamond',
        ];
        foreach ($retirados as $archivo) {
            $this->assertFileDoesNotExist(base_path($archivo));
        }

        // Solo en resources/ y public/ (los documentos sí las citan; /analyze A19).
        $buscados = ['landing.index', 'cover-bg.jpg', 'logo-rosa.png', 'fonts/lexend', 'portada-800', 'portada-1600', 'icono-categoria', 'cormorant'];
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
        // Rediseño (D16): el layout carga publico.js con defer (antes, ningún script).
        $this->soloElScriptPublico($xpath, '/_prueba/publico');
        $this->assertGreaterThan(0, $xpath->query('//details/summary')->length, 'Menú del móvil con details y summary.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"]')->length);
        $this->assertSame(1, $xpath->query('//header//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"][not(ancestor::details)]')->length, 'La cabecera ofrece «Crear cuenta» (ronda 5).');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Servicios"][@href="'.url('/servicios').'"]')->length, 'Navegación de la 038: Servicios → /servicios.');
        $this->assertSame(0, $xpath->query('//a[normalize-space()="Promociones" or normalize-space()="Trabajos"]')->length, 'Sin enlaces de la 039 y la 040.');
        $this->assertSame('publico', $xpath->query('//body')->item(0)->getAttribute('class'));

        // Ronda 6 (decisión del usuario, D23; como el refinado D15): solo el icono de hamburguesa, con el
        // nombre «Menú» para el lector y sin la palabra a la vista.
        $summary = $xpath->query('//details[contains(concat(" ", normalize-space(@class), " "), " pub-menu ")]/summary')->item(0);
        $this->assertNotNull($summary);
        $this->assertSame('', trim($summary->textContent), 'Sin la palabra «Menú» a la vista.');
        $this->assertSame('Menú', $summary->getAttribute('aria-label'));
        $this->assertSame(1, $xpath->query('*[local-name()="svg"][@aria-hidden="true"]', $summary)->length, 'El icono es un SVG oculto al lector.');
        // /review R18: por debajo de 900 px el menú sigue siendo un landmark de navegación.
        $this->assertSame(1, $xpath->query('//details[contains(concat(" ", normalize-space(@class), " "), " pub-menu ")]//nav[@aria-label="Secciones"]')->length, 'El panel del menú es la nav «Secciones».');
        $this->assertSame(1, $xpath->query('//details[contains(concat(" ", normalize-space(@class), " "), " pub-menu ")]//a[normalize-space()="Sobre nosotros"]')->length, 'El menú lleva también a «Sobre nosotros».');

        // Ronda 5: el pie con sus enlaces, el horario, el teléfono, la dirección y el nombre oculto.
        $pie = $xpath->query('//footer')->item(0);
        $this->assertStringContainsString(' pub-foot ', ' '.$pie->getAttribute('class').' ');
        $textoPie = preg_replace('/\s+/u', ' ', $pie->textContent);
        foreach (['Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00', 'Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950', 'Melissa López N. · Odontología integral'] as $texto) {
            $this->assertStringContainsString($texto, $textoPie);
        }
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $pie)->length, 'El teléfono del pie se marca con un toque.');
        $this->assertSame(1, $xpath->query('.//nav[@aria-label="Pie"]', $pie)->length);
        foreach (['Sobre nosotros', 'Servicios', 'Ubicación', 'Crear cuenta', 'Iniciar sesión'] as $enlace) {
            $this->assertSame(1, $xpath->query('.//a[normalize-space()="'.$enlace.'"]', $pie)->length, "El pie enlaza «{$enlace}».");
        }
        $this->assertSame(0, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " pub-foot__word ")]', $pie)->length, 'Ronda 6: sin el nombre a todo el ancho.');

        $cliente = Usuario::create([
            'nombre' => 'Paciente', 'apellido' => 'De Pruebas', 'email' => 'layout.'.Str::random(8).'@example.test',
            'rol' => 'cliente', 'telefono' => '555-0000', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
        $conSesion = $this->xpath($this->actingAs($cliente)->get('/_prueba/publico')->getContent());
        $this->assertGreaterThan(0, $conSesion->query('//a[normalize-space()="Ir a mi panel"][@href="'.route('cliente.dashboard').'"]')->length);
        $this->assertSame(0, $conSesion->query('//a[normalize-space()="Iniciar sesión"]')->length);
        $this->assertSame(0, $conSesion->query('//a[normalize-space()="Crear cuenta"]')->length, 'Con sesión, sin «Crear cuenta».');
    }

    public function test_servicios_con_la_superficie_de_la_ronda_6(): void
    {
        // /analyze A41: /servicios con la etiqueta, el título y «Volver al inicio» como botón de la superficie.
        $xpath = $this->xpath($this->get('/servicios')->assertOk()->getContent());
        $cabeza = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lb-cabeza ")]')->item(0);
        $this->assertNotNull($cabeza);
        $this->assertSame('Catálogo', trim($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " pub-etiqueta ")]', $cabeza)->item(0)?->textContent ?? ''), 'La etiqueta encabeza la página.');
        $this->assertSame(1, $xpath->query('.//h1[normalize-space()="Nuestros servicios"]', $cabeza)->length);
        $volver = $xpath->query('//a[normalize-space()="Volver al inicio"]')->item(0);
        $this->assertNotNull($volver);
        $this->assertStringContainsString(' c-btn ', ' '.$volver->getAttribute('class').' ', '«Volver al inicio» es un botón de la superficie.');
    }
}
