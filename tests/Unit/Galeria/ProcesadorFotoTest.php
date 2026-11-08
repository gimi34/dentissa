<?php

namespace Tests\Unit\Galeria;

use App\Galeria\FotoNoProcesable;
use App\Galeria\ProcesadorFoto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Concerns\GaleriaDePrueba;

/**
 * Spec 039 (T010; CA8, CA14, RNF de peso; TM2, TM3, TM4): el procesado de cada foto subida.
 * Plan → Estrategia de pruebas, D9 y D15.
 */
class ProcesadorFotoTest extends TestCase
{
    use GaleriaDePrueba;

    private const TOPE_GRANDE = 512000;

    private const TOPE_PEQUENA = 61440;

    private function procesador(): ProcesadorFoto
    {
        return new ProcesadorFoto;
    }

    /** Medidas y tipo de una imagen codificada. */
    private function medidas(string $bytes): array
    {
        $info = getimagesizefromstring($bytes);
        $this->assertNotFalse($info, 'La imagen se puede leer.');

        return [$info[0], $info[1], $info['mime']];
    }

    public static function formatos(): array
    {
        return ['jpg' => ['jpg'], 'png' => ['png'], 'webp' => ['webp']];
    }

    #[DataProvider('formatos')]
    public function test_el_resultado_son_dos_webp_4_3_de_las_medidas_del_plan(string $formato): void
    {
        $r = $this->procesador()->procesar($this->imagenLisa($formato, 2400, 1800));

        [$ancho, $alto, $tipo] = $this->medidas($r->grande);
        $this->assertSame('image/webp', $tipo);
        $this->assertSame([1600, 1200], [$ancho, $alto], 'El grande mide 1600 × 1200 como mucho.');
        $this->assertSame([1600, 1200], [$r->ancho, $r->alto], 'Las medidas que devuelve son las del grande.');

        [$anchoP, $altoP, $tipoP] = $this->medidas($r->pequena);
        $this->assertSame('image/webp', $tipoP);
        $this->assertSame([800, 600], [$anchoP, $altoP], 'El pequeño mide min(800, ancho).');
    }

    public function test_recorta_al_centro_a_4_3_sin_agrandar(): void
    {
        $r = $this->procesador()->procesar($this->imagenLisa('jpg', 2000, 1000));
        [$ancho, $alto] = $this->medidas($r->grande);
        $this->assertSame(1000, $alto, 'No se agranda: el alto del recorte es el del original.');
        $this->assertLessThanOrEqual(4, abs($ancho * 3 - $alto * 4), "Proporción 4:3 ({$ancho} × {$alto}).");

        $pequena = $this->procesador()->procesar($this->imagenLisa('png', 600, 450));
        $this->assertSame([600, 450], array_slice($this->medidas($pequena->grande), 0, 2), 'Una foto pequeña no se agranda.');
        $this->assertSame([600, 450], array_slice($this->medidas($pequena->pequena), 0, 2), 'El pequeño mide como el grande si este no llega a 800 px.');
    }

    public function test_el_grande_pesa_como_mucho_500_kb_tambien_con_ruido(): void
    {
        $r = $this->procesador()->procesar($this->imagenRuido(6000, 4500));

        $this->assertLessThanOrEqual(self::TOPE_GRANDE, strlen($r->grande), 'El grande pesa 500 kB o menos aunque sea ruido.');
        [$ancho, $alto] = $this->medidas($r->grande);
        $this->assertLessThanOrEqual(4, abs($ancho * 3 - $alto * 4), "Sigue en 4:3 al reducir las medidas ({$ancho} × {$alto}).");
        $this->assertSame([$ancho, $alto], [$r->ancho, $r->alto]);
        $this->assertSame(min(800, $ancho), $this->medidas($r->pequena)[0]);
    }

    public function test_el_pequeno_de_una_foto_realista_pesa_como_mucho_60_kb(): void
    {
        $r = $this->procesador()->procesar($this->fotoConGrano(1600, 1200));

        $this->assertSame(800, $this->medidas($r->pequena)[0]);
        $this->assertLessThanOrEqual(self::TOPE_PEQUENA, strlen($r->pequena), 'El pequeño de una foto con grano pesa 60 kB o menos.');
        $this->assertLessThanOrEqual(self::TOPE_GRANDE, strlen($r->grande));
    }

    public function test_no_quedan_datos_de_la_camara(): void
    {
        $subida = $this->jpgConOrientacion($this->lienzoPartido(1600, 1200), 1, true);
        $original = file_get_contents($subida->getPathname());
        $exif = @exif_read_data($subida->getPathname());
        $this->assertSame('N', $exif['GPSLatitudeRef'] ?? null, 'La subida de prueba sí lleva GPS.');
        $this->assertStringContainsString('http://ns.adobe.com/xap/1.0/', $original, 'Y XMP.');

        $r = $this->procesador()->procesar($subida);

        foreach (['grande' => $r->grande, 'pequena' => $r->pequena] as $medida => $bytes) {
            $bloques = $this->bloquesWebp($bytes);
            $this->assertNotContains('EXIF', $bloques, "El {$medida} no lleva EXIF.");
            $this->assertNotContains('XMP ', $bloques, "El {$medida} no lleva XMP.");
            $this->assertStringNotContainsString('Exif', $bytes);
            $this->assertStringNotContainsString('ns.adobe.com', $bytes);
            $this->assertStringNotContainsString('GPSLatitude', $bytes);
        }
    }

    public static function orientaciones(): array
    {
        return ['180°' => [3], '90° a la derecha' => [6], '90° a la izquierda' => [8]];
    }

    #[DataProvider('orientaciones')]
    public function test_se_endereza_segun_la_orientacion(int $orientacion): void
    {
        $r = $this->procesador()->procesar($this->jpgConOrientacion($this->lienzoPartido(1600, 1200), $orientacion));

        [$ancho, $alto] = $this->medidas($r->grande);
        $this->assertSame([1600, 1200], [$ancho, $alto], 'Sale apaisada y en 4:3.');
        foreach (['grande' => $r->grande, 'pequena' => $r->pequena] as $medida => $bytes) {
            [$rojo, , $azul] = $this->colorEn($bytes, 0.1, 0.5);
            $this->assertGreaterThan($azul + 100, $rojo, "En el {$medida}, el rojo queda a la izquierda.");
            [$rojo, , $azul] = $this->colorEn($bytes, 0.9, 0.5);
            $this->assertGreaterThan($rojo + 100, $azul, "En el {$medida}, el azul queda a la derecha.");
        }
    }

    public function test_con_6000_px_y_orientacion_6_el_pico_de_memoria_queda_por_debajo_de_200_mb(): void
    {
        $subida = $this->jpgEnormeConOrientacion(6000, 6000, 6);
        gc_collect_cycles();

        $antes = memory_get_usage();
        memory_reset_peak_usage();
        $r = $this->procesador()->procesar($subida);
        $pico = memory_get_peak_usage() - $antes;

        $this->assertLessThan(200 * 1024 * 1024, $pico, sprintf('Pico del procesado: %.1f MB (memory_limit 256M).', $pico / 1048576));
        $this->assertSame([1600, 1200], array_slice($this->medidas($r->grande), 0, 2), 'Cuadrada y girada, sale recortada a 4:3 apaisada.');
    }

    public function test_un_archivo_que_no_decodifica_lanza_la_excepcion(): void
    {
        $this->expectException(FotoNoProcesable::class);
        $this->procesador()->procesar($this->textoComoJpg());
    }
}
