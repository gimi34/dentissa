<?php

namespace Tests\Feature\Concerns;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Imágenes de prueba de la galería (spec 039, T010). Todas se generan con GD en el propio test:
 * no se versiona ninguna foto (P5). Las que llevan datos de la cámara los llevan sintéticos
 * (orientación EXIF, un GPS inventado y un bloque XMP), nunca de una foto real.
 */
trait GaleriaDePrueba
{
    /** Archivos temporales creados por el test, que se borran al terminar. */
    private array $archivosDePrueba = [];

    protected function tearDown(): void
    {
        foreach ($this->archivosDePrueba as $ruta) {
            @unlink($ruta);
        }
        $this->archivosDePrueba = [];
        parent::tearDown();
    }

    /**
     * La «foto real» de las pruebas de orientación: apaisada, la mitad izquierda roja y la derecha
     * azul. Bien enderezada, el rojo queda a la izquierda.
     */
    protected function lienzoPartido(int $ancho, int $alto): GdImage
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagefilledrectangle($imagen, 0, 0, intdiv($ancho, 2) - 1, $alto - 1, imagecolorallocate($imagen, 220, 30, 30));
        imagefilledrectangle($imagen, intdiv($ancho, 2), 0, $ancho - 1, $alto - 1, imagecolorallocate($imagen, 30, 30, 220));

        return $imagen;
    }

    /**
     * Un JPEG como lo guardaría una cámara con esa orientación EXIF: los píxeles van girados y la
     * etiqueta dice cómo enderezarlos. 1: tal cual; 3: 180°; 6: hay que girar 90° a la derecha;
     * 8: 90° a la izquierda. Con `$metadatos`, lleva además GPS y XMP sintéticos.
     */
    protected function jpgConOrientacion(GdImage $real, int $orientacion = 1, bool $metadatos = false, string $nombre = 'foto.jpg'): UploadedFile
    {
        $guardada = match ($orientacion) {
            3 => imagerotate($real, 180, 0),
            6 => imagerotate($real, 90, 0),   // GD gira en sentido antihorario
            8 => imagerotate($real, 270, 0),
            default => $real,
        };

        $jpeg = $this->codificar($guardada, 'jpg');
        $jpeg = $this->insertarExif($jpeg, $orientacion, $metadatos);

        return $this->subida($jpeg, $nombre);
    }

    /**
     * Un JPEG enorme con la etiqueta de orientación, sin girar sus píxeles: para medir la memoria
     * del peor caso admitido (6000 × 6000) sin que el propio test la agote al girarlo.
     */
    protected function jpgEnormeConOrientacion(int $ancho, int $alto, int $orientacion): UploadedFile
    {
        $imagen = $this->lienzoPartido($ancho, $alto);
        $jpeg = $this->codificar($imagen, 'jpg', 80);
        imagedestroy($imagen);

        return $this->subida($this->insertarExif($jpeg, $orientacion, false), 'enorme.jpg');
    }

    /**
     * Un PNG con canal alfa (una esquina semitransparente), el peor caso de memoria al decodificar un
     * PNG con GD (/review 039 R1).
     */
    protected function pngConAlfa(int $ancho, int $alto, string $nombre = 'alfa.png'): UploadedFile
    {
        $imagen = $this->lienzoPartido($ancho, $alto);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        imagefilledrectangle($imagen, 0, 0, min(99, $ancho - 1), min(99, $alto - 1), imagecolorallocatealpha($imagen, 0, 0, 0, 60));
        $png = $this->codificar($imagen, 'png');
        imagedestroy($imagen);

        return $this->subida($png, $nombre);
    }

    /** Una imagen lisa del formato pedido (`jpg`, `png`, `webp` o `gif`). */
    protected function imagenLisa(string $formato, int $ancho = 1200, int $alto = 900, ?string $nombre = null): UploadedFile
    {
        $imagen = $this->lienzoPartido($ancho, $alto);

        return $this->subida($this->codificar($imagen, $formato), $nombre ?? "foto.{$formato}");
    }

    /**
     * Ruido aleatorio de ese tamaño: el peor caso de peso, porque no se comprime. Se hace con una
     * tesela de 400 × 300 repetida, que tras reducir a 1600 px sigue siendo ruido.
     */
    protected function imagenRuido(int $ancho = 6000, int $alto = 4500): UploadedFile
    {
        $tesela = imagecreatetruecolor(400, 300);
        for ($y = 0; $y < 300; $y++) {
            for ($x = 0; $x < 400; $x++) {
                imagesetpixel($tesela, $x, $y, random_int(0, 0xFFFFFF));
            }
        }
        $imagen = imagecreatetruecolor($ancho, $alto);
        for ($y = 0; $y < $alto; $y += 300) {
            for ($x = 0; $x < $ancho; $x += 400) {
                imagecopy($imagen, $tesela, $x, $y, 0, 0, 400, 300);
            }
        }

        // JPEG y no PNG: un PNG de 6000 px ya no se admite (/review R1) y decodificarlo agota la memoria.
        $jpeg = $this->codificar($imagen, 'jpg', 95);
        imagedestroy($imagen);

        return $this->subida($jpeg, 'ruido.jpg');
    }

    /**
     * Una «foto» con grano realista: un degradado suave con un 8 % de ruido encima, como la prueba
     * de concepto del plan (D15).
     */
    protected function fotoConGrano(int $ancho = 3000, int $alto = 2250): UploadedFile
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        for ($x = 0; $x < $ancho; $x++) {
            $t = $x / max(1, $ancho - 1);
            $color = imagecolorallocate($imagen, (int) (200 - 90 * $t), (int) (120 + 60 * $t), (int) (140 + 80 * $t));
            imageline($imagen, $x, 0, $x, $alto - 1, $color);
        }
        $tesela = imagecreatetruecolor(256, 256);
        for ($y = 0; $y < 256; $y++) {
            for ($x = 0; $x < 256; $x++) {
                imagesetpixel($tesela, $x, $y, random_int(0, 0xFFFFFF));
            }
        }
        for ($y = 0; $y < $alto; $y += 256) {
            for ($x = 0; $x < $ancho; $x += 256) {
                imagecopymerge($imagen, $tesela, $x, $y, 0, 0, 256, 256, 8);
            }
        }

        return $this->subida($this->codificar($imagen, 'jpg', 90), 'grano.jpg');
    }

    /**
     * Un JPEG válido de más de `$bytes` bytes: relleno tras el final de la imagen, que el tipo y
     * las medidas no ven. Así solo falla la regla del peso.
     */
    protected function jpgPesado(int $bytes = 5300000): UploadedFile
    {
        $jpeg = $this->codificar($this->lienzoPartido(800, 600), 'jpg');

        return $this->subida($jpeg.str_repeat("\0", max(0, $bytes - strlen($jpeg))), 'pesada.jpg');
    }

    /** Un archivo de texto que se hace pasar por foto. */
    protected function textoComoJpg(): UploadedFile
    {
        return $this->subida("<?php echo 'no soy una foto';\n", 'foto.jpg');
    }

    /** Color medio de un punto (`x`, `y` en fracciones del ancho y el alto) de una imagen codificada. */
    protected function colorEn(string $bytes, float $x, float $y): array
    {
        $imagen = imagecreatefromstring($bytes);
        $rgb = imagecolorat($imagen, (int) (imagesx($imagen) * $x), (int) (imagesy($imagen) * $y));

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    /** Los bloques (FourCC) de un archivo WebP, para comprobar que no lleva EXIF ni XMP. */
    protected function bloquesWebp(string $bytes): array
    {
        $this->assertSame('RIFF', substr($bytes, 0, 4), 'Es un RIFF.');
        $this->assertSame('WEBP', substr($bytes, 8, 4), 'Es un WebP.');
        $bloques = [];
        $pos = 12;
        while ($pos + 8 <= strlen($bytes)) {
            $bloques[] = substr($bytes, $pos, 4);
            $tam = unpack('V', substr($bytes, $pos + 4, 4))[1];
            $pos += 8 + $tam + ($tam % 2);
        }

        return $bloques;
    }

    private function codificar(GdImage $imagen, string $formato, int $calidad = 92): string
    {
        ob_start();
        match ($formato) {
            'jpg' => imagejpeg($imagen, null, $calidad),
            'png' => imagepng($imagen, null, 1),
            'webp' => imagewebp($imagen, null, $calidad),
            'gif' => imagegif($imagen),
        };

        return ob_get_clean();
    }

    private function subida(string $contenido, string $nombre): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'galeria');
        file_put_contents($ruta, $contenido);
        $this->archivosDePrueba[] = $ruta;

        return new UploadedFile($ruta, $nombre, null, null, true);
    }

    /**
     * Inserta tras el SOI un segmento APP1 Exif (orientación y, con `$metadatos`, un GPS con
     * latitud) y, con `$metadatos`, un APP1 XMP. TIFF en little endian.
     */
    private function insertarExif(string $jpeg, int $orientacion, bool $metadatos): string
    {
        $entradas = [pack('vvVvv', 0x0112, 3, 1, $orientacion, 0)];   // Orientation, SHORT
        $ifd0Tam = 2 + 12 * ($metadatos ? 2 : 1) + 4;
        $gps = '';
        if ($metadatos) {
            $gpsOffset = 8 + $ifd0Tam;
            $entradas[] = pack('vvVV', 0x8825, 4, 1, $gpsOffset);   // GPSInfo, LONG
            // GPS IFD: GPSLatitudeRef = "N" y GPSLatitude = 17/1, 57/1, 0/1 (Lázaro Cárdenas, inventado).
            $racionalesOffset = $gpsOffset + 2 + 12 * 2 + 4;
            $gps = pack('v', 2)
                .pack('vvV', 0x0001, 2, 2).'N'."\0\0\0"
                .pack('vvVV', 0x0002, 5, 3, $racionalesOffset)
                .pack('V', 0)
                .pack('VVVVVV', 17, 1, 57, 1, 0, 1);
        }
        $tiff = 'II'.pack('vV', 42, 8).pack('v', count($entradas)).implode('', $entradas).pack('V', 0).$gps;
        $exif = "Exif\0\0".$tiff;
        $segmentos = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        if ($metadatos) {
            $xmp = "http://ns.adobe.com/xap/1.0/\0".'<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
                .'<rdf:Description xmlns:exif="http://ns.adobe.com/exif/1.0/" exif:GPSLatitude="17,57N"/></rdf:RDF></x:xmpmeta>';
            $segmentos .= "\xFF\xE1".pack('n', strlen($xmp) + 2).$xmp;
        }

        return substr($jpeg, 0, 2).$segmentos.substr($jpeg, 2);
    }
}
