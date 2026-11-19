<?php

namespace App\Agenda;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * La hora de la clínica (spec 050): toda referencia a «hoy» o «ahora» y toda fecha mostrada pasa por aquí.
 * La aplicación trabaja en UTC; las citas guardan su hora local de la clínica y las fechas que escribe el
 * sistema (`creado_en`, historial, consentimiento) se guardan en UTC.
 */
final class RelojClinica
{
    /** Primer y último año que admite una fecha recibida por la dirección (spec 049, /review R27). */
    private const ANIOS = [2000, 2100];

    public static function zona(): string
    {
        return (string) config('clinica.zona_horaria');
    }

    public static function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zona());
    }

    /** Inicio del día de hoy en la clínica. */
    public static function hoy(): CarbonImmutable
    {
        return self::ahora()->startOfDay();
    }

    /** Hoy en la clínica, `AAAA-MM-DD`: para reglas de validación y consultas por día. */
    public static function fechaHoy(): string
    {
        return self::ahora()->toDateString();
    }

    /** Ahora en la clínica, `AAAA-MM-DD HH:MM:SS`: para comparar con `citas.fecha_hora`, que está en hora local. */
    public static function momentoLocal(): string
    {
        return self::ahora()->format('Y-m-d H:i:s');
    }

    /** Una `fecha_hora` de cita: ya está en la hora de la clínica, no se convierte. */
    public static function deCita(string $fechaHora): CarbonImmutable
    {
        return CarbonImmutable::parse($fechaHora, self::zona());
    }

    /** Una fecha escrita por el sistema (en UTC), en la hora de la clínica. */
    public static function delSistema(mixed $valor): ?CarbonImmutable
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $fecha = $valor instanceof DateTimeInterface
            ? CarbonImmutable::instance($valor)
            : CarbonImmutable::parse((string) $valor, 'UTC');

        return $fecha->setTimezone(self::zona());
    }

    /** Años cumplidos hoy en la clínica. Cuenta con fechas puras: una medianoche que no existió en la zona no resta un año. */
    public static function edad(string $nacimiento): int
    {
        return (int) CarbonImmutable::parse(substr($nacimiento, 0, 10), 'UTC')
            ->diffInYears(CarbonImmutable::parse(self::fechaHoy(), 'UTC'));
    }

    /** Fecha `AAAA-MM-DD` válida, entre 2000 y 2100, en la zona de la clínica; cualquier otro valor da null. */
    public static function fechaDe(mixed $valor): ?CarbonImmutable
    {
        if (! is_string($valor) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $valor, $m)
            || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            || (int) $m[1] < self::ANIOS[0] || (int) $m[1] > self::ANIOS[1]) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $valor, self::zona());
    }
}
