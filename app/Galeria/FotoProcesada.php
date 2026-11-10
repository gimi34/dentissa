<?php

namespace App\Galeria;

/**
 * Spec 039: los dos WebP de una foto, listos para guardar. `ancho` y `alto` son los del grande;
 * el pequeño mide `min(800, ancho)` con la misma proporción.
 */
final class FotoProcesada
{
    public function __construct(
        public readonly string $grande,
        public readonly string $pequena,
        public readonly int $ancho,
        public readonly int $alto,
    ) {}
}
