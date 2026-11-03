<?php

namespace App\Auditoria;

use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Denegación que ya dejó su evento `acceso_denegado` con `Auditoria::denegar()` (spec 020, D4).
 *
 * Responde 403, o 404 cuando el recurso se oculta; el registro central de denegaciones la omite
 * para no registrarla dos veces.
 */
final class AccesoDenegadoAuditado extends HttpException
{
    public function __construct(int $estado)
    {
        if (! in_array($estado, [403, 404], true)) {
            throw new InvalidArgumentException('Una denegación responde 403 o 404.');
        }

        parent::__construct($estado);
    }
}
