<?php

namespace App\Agenda;

use Carbon\CarbonImmutable;
use LogicException;

/**
 * Calendario de citas del personal (spec 049): convierte las citas de un periodo, el horario de la
 * clínica y la hora actual en lo que pinta la vista de «Citas». No consulta la base de datos.
 *
 * Cada cita es un objeto con `id`, `fecha_hora` ('Y-m-d H:i:s', hora de la clínica), `duracion_min`,
 * `estado`, `paciente` y `dentista`. `$ocupacion` son las citas que ocupan los huecos: las del mismo
 * periodo y alcance, sin aplicar `buscar` ni `estado` (A28).
 */
final class CalendarioCitas
{
    public const TRAMO_MIN = 30;

    /** Estados que se ven atenuados y no ocupan un hueco. */
    public const ATENUADOS = ['cancelada', 'no_asistio'];

    /**
     * @param  'semana'|'mes'  $vista
     * @param  iterable<object>  $citas
     * @param  iterable<object>  $ocupacion
     * @param  array<int, array{0: string, 1: string}>  $horarioSemanal  día ISO => [apertura, cierre]
     */
    public function __construct(
        public readonly string $vista,
        public readonly CarbonImmutable $fecha,
        public readonly ?CarbonImmutable $dia,
        iterable $citas,
        iterable $ocupacion,
        public readonly array $horarioSemanal,
        public readonly CarbonImmutable $ahora,
    ) {
        throw new LogicException('sin implementar');
    }

    /** Primer instante del periodo que se consulta (lunes de la semana o día 1 del mes). */
    public function inicio(): CarbonImmutable
    {
        throw new LogicException('sin implementar');
    }

    /** Último instante del periodo que se consulta (domingo de la semana o último día del mes). */
    public function fin(): CarbonImmutable
    {
        throw new LogicException('sin implementar');
    }

    /** @return list<array{fila: int, hora: string, etiqueta: ?string}> */
    public function filas(): array
    {
        throw new LogicException('sin implementar');
    }

    /** @return list<array<string, mixed>> */
    public function dias(): array
    {
        throw new LogicException('sin implementar');
    }

    /** @return list<array<string, mixed>> */
    public function celdasMes(): array
    {
        throw new LogicException('sin implementar');
    }
}
