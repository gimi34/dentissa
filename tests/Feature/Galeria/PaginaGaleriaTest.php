<?php

namespace Tests\Feature\Galeria;

use App\Models\GaleriaFoto;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\GaleriaDePrueba;
use Tests\TestCase;

/**
 * Spec 039 (T014; CA2, CA6, CA7, CA14, CA15, RNF de peso; TM2, TM4, TM6, TM8): la página
 * `/galeria` y la entrega de cada foto (plan → Contratos, «Entrega de las fotos»).
 */
class PaginaGaleriaTest extends TestCase
{
    use DatabaseTransactions;
    use GaleriaDePrueba;

    private const SIZES_PRIMERA = '(min-width: 1200px) 800px, (min-width: 900px) 66vw, (min-width: 560px) 50vw, 100vw';

    private const SIZES_RESTO = '(min-width: 900px) 33vw, (min-width: 560px) 50vw, 100vw';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
        config(['app.url' => 'https://dentissa.app']);
    }

    private function foto(int $posicion, string $descripcion = '', array $mas = []): GaleriaFoto
    {
        $foto = GaleriaFoto::create(array_merge([
            'descripcion' => $descripcion ?: "Trabajo número {$posicion}",
            'posicion' => $posicion,
            'con_paciente' => false,
            'ancho' => 1600,
            'alto' => 1200,
            'bytes' => 11,
            'bytes_pequena' => 12,
        ], $mas));
        Storage::disk('galeria')->put($foto->archivo(1600), "grande-{$posicion}");
        Storage::disk('galeria')->put($foto->archivo(800), "pequena-{$posicion}");

        return $foto;
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($rol), 'apellido' => 'De Pruebas',
            'email' => "{$rol}.pagina.".Str::random(8).'@example.test', 'rol' => $rol,
            'telefono' => '555-0000', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
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

    private function pagina(): DOMXPath
    {
        return $this->xpath($this->get('/galeria')->assertOk()->getContent());
    }

    private function url(GaleriaFoto $foto, int $medida): string
    {
        return "/galeria/fotos/{$foto->id}/{$medida}";
    }

    public function test_muestra_todas_en_orden_con_su_descripcion(): void
    {
        $this->foto(2);
        $this->foto(1);
        $this->foto(3);

        $xpath = $this->pagina();
        $figuras = $xpath->query('//main//ol/li/figure');
        $this->assertSame(3, $figuras->length, 'Una figura por foto, en una lista.');
        foreach ([1, 2, 3] as $i => $n) {
            $figura = $figuras->item($i);
            $this->assertSame("Trabajo número {$n}", $xpath->query('.//img', $figura)->item(0)->getAttribute('alt'), 'En orden, con la descripción como alt.');
            $pie = preg_replace('/\s+/', ' ', $xpath->query('.//figcaption', $figura)->item(0)->textContent);
            $this->assertStringContainsString("Foto {$n} de 3", $pie);
            $this->assertStringContainsString("Trabajo número {$n}", $pie, 'Y en el pie.');
        }
        $volver = $xpath->query('//main//a[normalize-space()="Volver al inicio"]');
        $this->assertSame(1, $volver->length);
        // url('/') no lleva la barra final: una ruta vacía también es la raíz.
        $this->assertSame('/', parse_url($volver->item(0)->getAttribute('href'), PHP_URL_PATH) ?? '/');
        $this->assertNull(parse_url($volver->item(0)->getAttribute('href'), PHP_URL_QUERY), 'Sin parámetros.');
    }

    public function test_vacia_muestra_el_aviso(): void
    {
        $xpath = $this->pagina();

        $this->assertStringContainsString('Aún no hay fotos de nuestros trabajos.', $xpath->query('//main')->item(0)->textContent);
        $this->assertSame(0, $xpath->query('//main//img')->length);
        $this->assertSame(1, $xpath->query('//main//a[normalize-space()="Volver al inicio"]')->length);
    }

    public function test_metadatos_de_galeria(): void
    {
        $xpath = $this->xpath($this->withHeaders(['X-Forwarded-Host' => 'atacante.example'])->get('/galeria')->assertOk()->getContent());

        $this->assertSame('Nuestros trabajos · Dentissa', trim($xpath->query('//title')->item(0)->textContent));
        $this->assertSame('Fotos de tratamientos hechos en la consulta Dentissa en Lázaro Cárdenas.', $xpath->query('//meta[@name="description"]')->item(0)?->getAttribute('content'));
        $canonicas = $xpath->query('//link[@rel="canonical"]');
        $this->assertSame(1, $canonicas->length);
        $this->assertSame('https://dentissa.app/galeria', $canonicas->item(0)->getAttribute('href'));
        $this->assertStringNotContainsString('noindex', strtolower((string) $xpath->query('//meta[@name="robots"]')->item(0)?->getAttribute('content')));
        $this->assertSame(1, $xpath->query('//h1[normalize-space()="Nuestros trabajos"]')->length);
    }

    public function test_solo_carga_el_script_publico_con_defer(): void
    {
        $this->foto(1);
        $scripts = $this->pagina()->query('//script');

        $this->assertSame(1, $scripts->length, 'Un solo script propio.');
        $this->assertSame('/js/publico.js', parse_url($scripts->item(0)->getAttribute('src'), PHP_URL_PATH));
        $this->assertTrue($scripts->item(0)->hasAttribute('defer'));
    }

    public static function roles(): array
    {
        return ['admin' => ['admin'], 'dentista' => ['dentista'], 'asistente' => ['asistente'], 'paciente' => ['cliente']];
    }

    #[DataProvider('roles')]
    public function test_con_sesion_se_ve_igual(string $rol): void
    {
        $this->foto(1);
        $this->foto(2);
        $principal = fn (string $html) => $this->xpath($html)->query('//main')->item(0)?->C14N();

        $sinSesion = $principal($this->get('/galeria')->assertOk()->getContent());
        $conSesion = $principal($this->actingAs($this->usuario($rol))->get('/galeria')->assertOk()->getContent());

        $this->assertNotNull($sinSesion);
        $this->assertSame($sinSesion, $conSesion, 'El contenido de la página es el mismo con sesión.');
    }

    public function test_cada_foto_ofrece_las_dos_medidas(): void
    {
        $f1 = $this->foto(1);
        $f2 = $this->foto(2);
        $imgs = $this->pagina()->query('//main//img');

        foreach ([[$f1, self::SIZES_PRIMERA], [$f2, self::SIZES_RESTO]] as $i => [$foto, $sizes]) {
            $img = $imgs->item($i);
            $this->assertSame($this->url($foto, 800), $img->getAttribute('src'), 'El src es el pequeño.');
            $this->assertSame($this->url($foto, 800).' 800w, '.$this->url($foto, 1600).' 1600w', $img->getAttribute('srcset'));
            $this->assertSame($sizes, $img->getAttribute('sizes'));
            $this->assertSame(['1600', '1200'], [$img->getAttribute('width'), $img->getAttribute('height')], 'Medidas del grande, contra los saltos.');
        }
    }

    public function test_el_pequeno_mide_como_el_grande_si_este_no_llega_a_800(): void
    {
        $foto = $this->foto(1, 'Pequeña', ['ancho' => 600, 'alto' => 450]);

        $img = $this->pagina()->query('//main//img')->item(0);
        $this->assertSame($this->url($foto, 800).' 600w, '.$this->url($foto, 1600).' 600w', $img->getAttribute('srcset'));
    }

    public function test_solo_las_tres_primeras_cargan_sin_esperar(): void
    {
        foreach (range(1, 5) as $n) {
            $this->foto($n);
        }
        $imgs = $this->pagina()->query('//main//img');

        $this->assertSame(5, $imgs->length);
        foreach (range(0, 4) as $i) {
            $lazy = $imgs->item($i)->getAttribute('loading') === 'lazy';
            $this->assertSame($i >= 3, $lazy, 'Foto '.($i + 1).($i >= 3 ? ' en diferido.' : ' sin esperar.'));
        }
    }

    public function test_la_descripcion_se_muestra_como_texto(): void
    {
        $this->foto(1, '<script>alert(1)</script>');

        $html = $this->get('/galeria')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_la_foto_se_sirve_como_webp_con_nosniff(): void
    {
        $foto = $this->foto(1);

        foreach ([800 => 'pequena-1', 1600 => 'grande-1'] as $medida => $contenido) {
            $respuesta = $this->get($this->url($foto, $medida))->assertOk();
            $this->assertSame('image/webp', $respuesta->headers->get('Content-Type'));
            $this->assertSame('nosniff', $respuesta->headers->get('X-Content-Type-Options'));
            $this->assertSame($contenido, $respuesta->getContent(), "La medida {$medida} sirve su archivo.");
        }
    }

    public function test_la_foto_se_sirve_con_revalidacion(): void
    {
        $foto = $this->foto(1);
        $etiqueta = '"'.$foto->id.'-800"';

        $respuesta = $this->get($this->url($foto, 800))->assertOk();
        $this->assertSame('no-cache, private', $respuesta->headers->get('Cache-Control'), 'Revalida en cada visita; ninguna caché compartida la guarda.');
        $this->assertSame($etiqueta, $respuesta->headers->get('ETag'));
        $this->assertNull($respuesta->headers->get('Pragma'), 'Sin las cabeceras de PreventBackHistory.');

        $igual = $this->get($this->url($foto, 800), ['If-None-Match' => $etiqueta]);
        $igual->assertStatus(304);
        $this->assertSame('', $igual->getContent(), '304 sin cuerpo.');

        $this->get($this->url($foto, 800), ['If-None-Match' => '"otra"'])->assertOk();
    }

    public function test_solo_las_fotos_con_paciente_llevan_noindex(): void
    {
        $con = $this->foto(1, 'Con paciente', ['con_paciente' => true, 'consentimiento_nombre' => 'Admin', 'consentimiento_en' => now()]);
        $sin = $this->foto(2, 'Sin paciente');

        foreach ([800, 1600] as $medida) {
            $this->assertSame('noindex', $this->get($this->url($con, $medida))->assertOk()->headers->get('X-Robots-Tag'));
            $this->assertNull($this->get($this->url($sin, $medida))->assertOk()->headers->get('X-Robots-Tag'));
        }
    }

    public function test_una_direccion_que_no_es_una_foto_responde_404(): void
    {
        $foto = $this->foto(1);

        foreach ([
            '/galeria/fotos/texto/800',
            '/galeria/fotos/..%2F..%2F.env/800',
            '/galeria/fotos/'.Str::uuid().'/800',
            "/galeria/fotos/{$foto->id}/1200",
            "/galeria/fotos/{$foto->id}/800.webp",
            "/galeria/fotos/{$foto->id}",
        ] as $direccion) {
            $this->get($direccion)->assertNotFound();
        }
    }

    public function test_una_fila_sin_archivo_responde_404_sin_error(): void
    {
        $foto = $this->foto(1);
        Storage::disk('galeria')->delete([$foto->archivo(800), $foto->archivo(1600)]);
        Log::spy();

        $this->get($this->url($foto, 800))->assertNotFound();
        $this->get($this->url($foto, 1600))->assertNotFound();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_la_foto_descargada_no_tiene_exif(): void
    {
        $admin = $this->usuario('admin');
        $subida = $this->jpgConOrientacion($this->lienzoPartido(1600, 1200), 1, true);
        $this->actingAs($admin)->post(route('admin.galeria.store'), [
            'foto' => $subida, 'descripcion' => 'Con datos de la cámara', 'con_paciente' => 'no',
        ])->assertSessionHasNoErrors();
        $foto = GaleriaFoto::sole();

        foreach ([800, 1600] as $medida) {
            $bytes = $this->get($this->url($foto, $medida))->assertOk()->getContent();
            $bloques = $this->bloquesWebp($bytes);
            $this->assertNotContains('EXIF', $bloques, "La medida {$medida} se descarga sin EXIF.");
            $this->assertNotContains('XMP ', $bloques);
            $this->assertStringNotContainsString('GPSLatitude', $bytes);
        }
    }
}
