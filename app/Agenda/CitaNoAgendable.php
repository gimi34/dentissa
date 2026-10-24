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

    public static function duracionInvalida(): self
    {
        return new self('La duración del servicio no es válida.');
    }

    public static function noDisponible(): self
    {
        return new self('Ese horario ya no está disponible. Por favor elige otro.');
    }

    /** Spec 028: la nueva fecha y hora ya pasaron en la hora de la clínica. */
    public static function enElPasado(): self
    {
        return new self('Elige una fecha y hora que aún no hayan pasado.');
    }

    /** Spec 028: la nueva fecha y hora son las mismas que tiene la cita. */
    public static function mismaFechaYHora(): self
    {
        return new self('La cita ya está en esa fecha y hora. Elige otra.');
    }
}
