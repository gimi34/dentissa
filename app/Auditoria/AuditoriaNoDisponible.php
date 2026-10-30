<?php

namespace App\Auditoria;

use RuntimeException;

/**
 * Un evento del modo crítico no se pudo escribir: la acción se detiene (spec 020, CA11).
 *
 * Nunca lleva la excepción del almacén como previa, porque una `QueryException` arrastra el SQL
 * y sus bindings (TM13). Se muestra como la página 503 genérica y no se reporta.
 */
final class AuditoriaNoDisponible extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El registro de auditoría no está disponible.');
    }
}
