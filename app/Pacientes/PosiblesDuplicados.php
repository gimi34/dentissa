<?php

namespace App\Pacientes;

use RuntimeException;

/**
 * Spec 054 (CA3, D5): pacientes que podrían ser la misma persona que un alta nueva.
 */
class PosiblesDuplicados
{
    /** @return array{ids: list<string>, total: int} */
    public static function buscar(string $nombre, string $apellido, ?string $telefono, ?string $fechaNacimiento): array
    {
        throw new RuntimeException('Sin implementar (T020).');
    }
}
