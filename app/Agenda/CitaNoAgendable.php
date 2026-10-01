<?php

namespace App\Agenda;

use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Rechazo de la regla de agenda con un mensaje apto para el usuario. Nunca
 * incluye datos de otras citas (spec 025, CA4).
 */
class CitaNoAgendable extends RuntimeException
{
    public static function sinHorario(): self
    {
        return new self('El dentista no tiene horario disponible ese día de la semana.');
    }

    public static function fueraDeFranja(CarbonInterface $inicio, CarbonInterface $fin): self
    {
        return new self('El horario elegido está fuera del horario de atención del dentista ('
            .$inicio->format('H:i').' - '.$fin->format('H:i').').');
    }

    public static function noDisponible(): self
    {
        return new self('Ese horario ya no está disponible. Por favor elige otro.');
    }
}
