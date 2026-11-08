<?php

namespace Tests\Feature\Galeria;

use App\Models\GaleriaFoto;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Spec 039 (T015; CA1, CA3, CA4, CA15, RNF de peso y de accesibilidad; TM5): la sección «Nuestros
 * trabajos» de la página de inicio, en el HTML del servidor (el movimiento lo prueba
 * `tests/js/publico.test.mjs`).
 */
class CarruselInicioTest extends TestCase
{
    use DatabaseTransactions;

    private const SIZES_CARRUSEL = '(min-width: 900px) 53vw, (min-width: 560px) 72vw, 86vw';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
    }

    private function fotos(int $cuantas, string $descripcion = ''): array
    {
        return array_map(fn (int $n) => GaleriaFoto::create([
            'descripcion' => $descripcion ?: "Trabajo número {$n}",
            'posicion' => $n,
            'con_paciente' => false,
            'ancho' => 1600,
            'alto' => 1200,
            'bytes' => 10,
            'bytes_pequena' => 10,
        ]), range(1, $cuantas));
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    /** La sección de los trabajos (la que titula `#t-trabajos`), o `null`. */
    private function seccion(DOMXPath $xpath): ?DOMElement
    {
        return $xpath->query('//section[@aria-labelledby="t-trabajos"]')->item(0);
    }

    private function texto(?\DOMNode $nodo): string
    {
        return trim(preg_replace('/\s+/', ' ', $nodo?->textContent ?? ''));
    }

    public function test_el_carrusel_va_despues_de_los_servicios_en_orden(): void
    {
        $this->fotos(3);
        $html = $this->get('/')->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $seccion = $this->seccion($xpath);
        $this->assertNotNull($seccion, 'Hay sección de trabajos.');

        // A15: orden relativo (después del avance de servicios, antes de la agenda), no adyacencia.
        $servicios = strpos($html, 'Ver todos los servicios');
        $trabajos = strpos($html, 'id="t-trabajos"');
        $agenda = strpos($html, 'id="t-agenda"');
        $this->assertTrue($servicios < $trabajos && $trabajos < $agenda, 'Después de los servicios y antes de la agenda.');

        $this->assertSame('Nuestros trabajos', $this->texto($xpath->query('.//h2[@id="t-trabajos"]', $seccion)->item(0)));
        $items = $xpath->query('.//ol/li', $seccion);
        $this->assertSame(3, $items->length);
        foreach ([1, 2, 3] as $i => $n) {
            $li = $items->item($i);
            $this->assertSame("trabajo-{$n}", $li->getAttribute('id'), 'En orden, cada una con su ancla.');
            $this->assertSame("Trabajo número {$n}", $xpath->query('.//img', $li)->item(0)->getAttribute('alt'));
            $pie = $this->texto($xpath->query('.//figcaption', $li)->item(0));
            $this->assertStringContainsString("Foto {$n} de 3", $pie);
            $this->assertStringContainsString("Trabajo número {$n}", $pie);
            $this->assertSame(sprintf('%02d', $n), $this->texto($xpath->query('.//*[@aria-hidden="true"]', $li)->item(0)), 'Numeral decorativo.');
        }

        $ventana = $xpath->query('.//*[@role="region"]', $seccion)->item(0);
        $this->assertNotNull($ventana, 'La ventana es una región.');
        $this->assertSame('0', $ventana->getAttribute('tabindex'), 'Tabulable.');
        $this->assertNotSame('', trim($ventana->getAttribute('aria-label')), 'Con nombre.');

        $anterior = $xpath->query('.//a[normalize-space()="Anterior"]', $seccion);
        $siguiente = $xpath->query('.//a[normalize-space()="Siguiente"]', $seccion);
        $this->assertSame(1, $anterior->length);
        $this->assertSame(1, $siguiente->length);
        $this->assertSame('#trabajo-3', $anterior->item(0)->getAttribute('href'), 'Desde la primera, «Anterior» va a la última.');
        $this->assertSame('#trabajo-2', $siguiente->item(0)->getAttribute('href'));

        $galeria = $xpath->query('.//a[normalize-space()="Ver toda la galería"]', $seccion);
        $this->assertSame(1, $galeria->length);
        $this->assertSame('/galeria', parse_url($galeria->item(0)->getAttribute('href'), PHP_URL_PATH));
    }

    public function test_todas_las_fotos_estan_en_el_html(): void
    {
        $this->fotos(6);
        $xpath = $this->xpath($this->get('/')->assertOk()->getContent());

        $this->assertSame(6, $xpath->query('.//img', $this->seccion($xpath))->length, 'Sin JavaScript se ven todas (CA4).');
        foreach (range(1, 6) as $n) {
            $this->assertSame(1, $xpath->query('//*[@id="trabajo-'.$n.'"]')->length, "El ancla #trabajo-{$n} existe.");
        }
    }

    public function test_todas_las_fotos_del_carrusel_cargan_en_diferido(): void
    {
        $fotos = $this->fotos(4);
        $xpath = $this->xpath($this->get('/')->assertOk()->getContent());
        $imgs = $xpath->query('.//img', $this->seccion($xpath));

        foreach ($fotos as $i => $foto) {
            $img = $imgs->item($i);
            $this->assertSame('lazy', $img->getAttribute('loading'), 'La sección queda muy por debajo de la primera pantalla (/analyze A7).');
            $this->assertSame("/galeria/fotos/{$foto->id}/800", $img->getAttribute('src'));
            $this->assertSame("/galeria/fotos/{$foto->id}/800 800w, /galeria/fotos/{$foto->id}/1600 1600w", $img->getAttribute('srcset'));
            $this->assertSame(self::SIZES_CARRUSEL, $img->getAttribute('sizes'));
            $this->assertSame(['1600', '1200'], [$img->getAttribute('width'), $img->getAttribute('height')]);
        }
    }

    public function test_la_region_de_estado_esta_montada_y_vacia(): void
    {
        $this->fotos(2);
        $xpath = $this->xpath($this->get('/')->assertOk()->getContent());

        $estado = $xpath->query('.//*[@role="status"]', $this->seccion($xpath));
        $this->assertSame(1, $estado->length, 'Una sola región de estado.');
        $this->assertSame('', trim($estado->item(0)->textContent), 'Montada y vacía hasta que cambie la foto.');
        $this->assertStringContainsString('pub-solo-lector', $estado->item(0)->getAttribute('class'));
    }

    public function test_la_descripcion_se_muestra_como_texto(): void
    {
        $this->fotos(1, '<script>alert(1)</script>');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_sin_fotos_no_hay_carrusel_ni_enlace(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertNull($this->seccion($xpath), 'Sin fotos, no hay sección (CA3).');
        $this->assertStringNotContainsString('Ver toda la galería', $html);
        $this->assertSame(0, $xpath->query('//a[contains(@href, "/galeria")]')->length, 'Ni enlace a la galería.');
    }
}
