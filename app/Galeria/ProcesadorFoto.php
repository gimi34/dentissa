<?php

namespace App\Galeria;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Spec 039 (ADR 0009, plan D9 y D15): convierte una foto subida en los dos WebP que se publican.
 * Sin implementar todavía (T010 escribe sus tests; T020 lo implementa).
 */
class ProcesadorFoto
{
    public function procesar(UploadedFile $archivo): FotoProcesada
    {
        throw new RuntimeException('sin implementar');
    }
}
