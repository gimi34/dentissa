<?php

namespace App\Agenda;

use RuntimeException;

/**
 * El horario del dentista no se pudo guardar por un bloqueo de la base de datos
 * (spec 027, TM5). El mensaje es apto para el usuario.
 */
class HorarioNoGuardado extends RuntimeException
{
    public static function ocupado(): self
    {
        return new self('Otra persona está agendando con este dentista en este momento. Vuelve a guardar el horario.');
    }
}
