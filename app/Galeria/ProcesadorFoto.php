<?php

namespace App\Galeria;

use GdImage;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Spec 039 (ADR 0009; plan D2, D9 y D15; CA14; TM2, TM3, TM4): convierte una foto subida en los
 * dos WebP que se publican. Solo se guardan píxeles recodificados: GD no copia los datos de la
 * cámara (EXIF, GPS, XMP) y nunca se conserva el original.
 *
 * Orden pensado para la memoria (D9): se decodifica la original (hasta 6000 × 6000, unos 144 MB),
 * se recorta al centro a 4:3 y se reduce en un solo remuestreo, se libera la original y solo
 * entonces se aplica la orientación EXIF, sobre la imagen ya reducida. Nunca hay dos copias de la
 * imagen completa en memoria.
 */
class ProcesadorFoto
{
    private const ANCHO_MAX = 1600;

    private const ALTO_MAX = 1200;

    private const ANCHO_PEQUENA = 800;

    /** Topes de peso (ADR 0009): el grande es estricto; el pequeño, un objetivo (D15). */
    private const TOPE_GRANDE = 512000;

    private const TOPE_PEQUENA = 61440;

    private const CALIDADES_GRANDE = [82, 72, 62, 52];

    private const CALIDADES_PEQUENA = [82, 72, 62, 52, 42, 32, 22];

    public function procesar(UploadedFile $archivo): FotoProcesada
    {
        $ruta = $archivo->getRealPath();
        $original = $this->decodificar($ruta);
        $orientacion = $this->orientacion($ruta);
        $girada = $orientacion >= 5;

        // Medidas tal como se ve la foto (ya girada) y su recorte 4:3 centrado.
        [$ancho, $alto] = [imagesx($original), imagesy($original)];
        [$verAncho, $verAlto] = $girada ? [$alto, $ancho] : [$ancho, $alto];
        if ($verAncho * 3 > $verAlto * 4) {
            [$corteAncho, $corteAlto] = [(int) round($verAlto * 4 / 3), $verAlto];
        } else {
            [$corteAncho, $corteAlto] = [$verAncho, (int) round($verAncho * 3 / 4)];
        }
        $escala = min(1, self::ANCHO_MAX / $corteAncho, self::ALTO_MAX / $corteAlto);
        [$salidaAncho, $salidaAlto] = [max(1, (int) round($corteAncho * $escala)), max(1, (int) round($corteAlto * $escala))];

        // El recorte centrado de la foto vista es el recorte centrado de la guardada, con los
        // lados cambiados si la orientación gira 90°; el destino, sin girar todavía (/analyze A28).
        [$fuenteAncho, $fuenteAlto] = $girada ? [$corteAlto, $corteAncho] : [$corteAncho, $corteAlto];
        [$destinoAncho, $destinoAlto] = $girada ? [$salidaAlto, $salidaAncho] : [$salidaAncho, $salidaAlto];
        $reducida = $this->lienzo($destinoAncho, $destinoAlto);
        imagecopyresampled(
            $reducida, $original, 0, 0,
            intdiv($ancho - $fuenteAncho, 2), intdiv($alto - $fuenteAlto, 2),
            $destinoAncho, $destinoAlto, $fuenteAncho, $fuenteAlto,
        );
        unset($original);

        $foto = $this->enderezar($reducida, $orientacion);
        [$grande, $ancho, $alto] = $this->codificarGrande($foto);
        $pequena = $this->codificarPequena($foto, $ancho, $alto);

        return new FotoProcesada($grande, $pequena, $ancho, $alto);
    }

    private function decodificar(string|false $ruta): GdImage
    {
        try {
            $tipo = $ruta === false ? false : @getimagesize($ruta);
            $imagen = match ($tipo[2] ?? null) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
                IMAGETYPE_PNG => @imagecreatefrompng($ruta),
                IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
                default => false,
            };
        } catch (Throwable) {
            $imagen = false;
        }
        if (! $imagen instanceof GdImage) {
            throw new FotoNoProcesable('GD no pudo decodificar la imagen.');
        }
        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }

        return $imagen;
    }

    /** La orientación EXIF (1 a 8) de un JPEG; 1 si no tiene o no es JPEG. */
    private function orientacion(string|false $ruta): int
    {
        if ($ruta === false || (@getimagesize($ruta)[2] ?? null) !== IMAGETYPE_JPEG) {
            return 1;
        }
        $exif = @exif_read_data($ruta);
        $valor = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return $valor >= 1 && $valor <= 8 ? $valor : 1;
    }

    /** Lienzo de color verdadero en blanco: las zonas transparentes de un PNG no salen negras. */
    private function lienzo(int $ancho, int $alto): GdImage
    {
        $lienzo = imagecreatetruecolor($ancho, $alto);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));

        return $lienzo;
    }

    /** Aplica la orientación EXIF a la imagen reducida (GD gira en sentido antihorario). */
    private function enderezar(GdImage $imagen, int $orientacion): GdImage
    {
        $blanco = 0xFFFFFF;
        $resultado = match ($orientacion) {
            3 => imagerotate($imagen, 180, $blanco),
            5, 6, 7 => imagerotate($imagen, 270, $blanco),
            8 => imagerotate($imagen, 90, $blanco),
            default => $imagen,
        };
        match ($orientacion) {
            2, 5 => imageflip($resultado, IMG_FLIP_HORIZONTAL),
            4, 7 => imageflip($resultado, IMG_FLIP_VERTICAL),
            default => null,
        };

        return $resultado;
    }

    /**
     * El grande: baja la calidad hasta 500 kB y, si con 52 no llega, reduce las medidas a 0,8 veces
     * y repite (D15). Devuelve los bytes y las medidas finales.
     */
    private function codificarGrande(GdImage $foto): array
    {
        $actual = $foto;
        while (true) {
            foreach (self::CALIDADES_GRANDE as $calidad) {
                $bytes = $this->webp($actual, $calidad);
                if (strlen($bytes) <= self::TOPE_GRANDE) {
                    return [$bytes, imagesx($actual), imagesy($actual)];
                }
            }
            $ancho = max(1, (int) round(imagesx($actual) * 0.8));
            $alto = max(1, (int) round(imagesy($actual) * 0.8));
            $actual = $this->escalar($foto, $ancho, $alto);
        }
    }

    /** El pequeño, de `min(800, ancho)`: hasta 60 kB o, si no llega, el de calidad 22 (D15). */
    private function codificarPequena(GdImage $foto, int $ancho, int $alto): string
    {
        $pequenaAncho = min(self::ANCHO_PEQUENA, $ancho);
        $pequena = $this->escalar($foto, $pequenaAncho, max(1, (int) round($pequenaAncho * $alto / $ancho)));
        $bytes = '';
        foreach (self::CALIDADES_PEQUENA as $calidad) {
            $bytes = $this->webp($pequena, $calidad);
            if (strlen($bytes) <= self::TOPE_PEQUENA) {
                break;
            }
        }

        return $bytes;
    }

    private function escalar(GdImage $foto, int $ancho, int $alto): GdImage
    {
        if ($ancho === imagesx($foto) && $alto === imagesy($foto)) {
            return $foto;
        }
        $destino = $this->lienzo($ancho, $alto);
        imagecopyresampled($destino, $foto, 0, 0, 0, 0, $ancho, $alto, imagesx($foto), imagesy($foto));

        return $destino;
    }

    private function webp(GdImage $imagen, int $calidad): string
    {
        ob_start();
        $hecho = imagewebp($imagen, null, $calidad);
        $bytes = (string) ob_get_clean();
        if (! $hecho || $bytes === '') {
            throw new FotoNoProcesable('GD no pudo codificar el WebP.');
        }

        return $bytes;
    }
}
