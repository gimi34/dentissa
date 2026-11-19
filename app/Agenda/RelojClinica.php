<?php

namespace App\Agenda;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * La hora de la clínica (spec 050): toda referencia a «hoy» o «ahora» y toda fecha mostrada pasa por aquí.
 * La aplicación trabaja en UTC; las citas guardan su hora local de la clínica y las fechas que escribe el
 * sistema (`creado_en`, historial, consentimiento) se guardan en UTC.
 */
final class RelojClinica
{
    public static function zona(): string
    {
        throw new RuntimeException('sin implementar');
    }

    public static function ahora(): CarbonImmutable
    {
        throw new RuntimeException('sin implementar');
    }

    public static function hoy(): CarbonImmutable
    {
        throw new RuntimeException('sin implementar');
    }

    public static function fechaHoy(): string
    {
        throw new RuntimeException('sin implementar');
    }

    public static function momentoLocal(): string
    {
        throw new RuntimeException('sin implementar');
    }

    public static function deCita(string $fechaHora): CarbonImmutable
    {
        throw new RuntimeException('sin implementar');
    }

    public static function delSistema(mixed $valor): ?CarbonImmutable
    {
        throw new RuntimeException('sin implementar');
    }

    public static function fechaDe(mixed $valor): ?CarbonImmutable
    {
        throw new RuntimeException('sin implementar');
    }
}
