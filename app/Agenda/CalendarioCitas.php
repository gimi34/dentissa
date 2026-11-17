<?php

namespace App\Agenda;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calendario de citas del personal (spec 049): convierte las citas de un periodo, el horario de la
 * clínica y la hora actual en lo que pinta la vista de «Citas». No consulta la base de datos.
 *
 * Cada cita es un objeto con `id`, `fecha_hora` ('Y-m-d H:i:s', hora de la clínica), `duracion_min`,
 * `estado`, `paciente`, `dentista` y `dentista_id`. `$ocupacion` son las citas que ocupan los huecos:
 * las del mismo periodo y alcance, sin aplicar `buscar` ni `estado` (A28). `$dentista` es la dentista
 * que muestra el calendario cuando muestra una sola (su vista o el filtro): los huecos son entonces sus
 * tramos libres y su enlace la lleva.
 *
 * Las filas son tramos de 30 minutos numerados desde 1. Una cita ocupa desde el tramo en que empieza
 * hasta el tramo en que termina (10:15 de 45 min ocupa los de 10:00 y 10:30; A10).
 */
final class CalendarioCitas
{
    public const TRAMO_MIN = 30;

    /** Estados que se ven atenuados y no ocupan un hueco. */
    public const ATENUADOS = ['cancelada', 'no_asistio'];

    private const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];

    private const CORTOS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /** @var list<object> citas del periodo, en orden de hora */
    private array $citas;

    /** @var array<string, array<int, true>> tramos ocupados por día ('Y-m-d' => [fila => true]) */
    private array $ocupados = [];

    private CarbonImmutable $inicio;

    private CarbonImmutable $fin;

    /** Minuto del día en que empieza la primera fila y en que termina la última. */
    private int $minutoInicial;

    private int $minutoFinal;

    /** @var list<array<string, mixed>>|null */
    private ?array $dias = null;

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
        public readonly ?string $dentista = null,
    ) {
        [$this->inicio, $this->fin] = self::periodo($vista, $fecha);

        $this->citas = array_values(array_filter(
            $this->ordenadas($citas),
            fn (object $cita) => $this->enPeriodo($this->momento($cita)),
        ));

        $this->calcularFilas();

        foreach ($this->ordenadas($ocupacion) as $cita) {
            if (in_array($cita->estado, self::ATENUADOS, true)) {
                continue;
            }
            if ($dentista !== null && ($cita->dentista_id ?? null) !== $dentista) {
                continue;
            }
            [$fila, $tramos] = $this->posicion($cita);
            $iso = $this->momento($cita)->toDateString();
            for ($f = $fila; $f < $fila + $tramos; $f++) {
                $this->ocupados[$iso][$f] = true;
            }
        }
    }

    // ── Periodo ────────────────────────────────────────────────────────

    /**
     * Periodo que se consulta para una vista y una fecha, sin construir el calendario: la semana de
     * lunes a domingo (un domingo pertenece a la semana que termina el sábado anterior) o el mes.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function periodo(string $vista, CarbonImmutable $fecha): array
    {
        if ($vista === 'mes') {
            return [$fecha->startOfMonth(), $fecha->endOfMonth()];
        }
        $lunes = $fecha->startOfWeek(CarbonInterface::MONDAY);

        return [$lunes, $lunes->addDays(6)->endOfDay()];
    }

    /** Primer instante del periodo que se consulta (lunes de la semana o día 1 del mes). */
    public function inicio(): CarbonImmutable
    {
        return $this->inicio;
    }

    /** Último instante del periodo que se consulta (domingo de la semana o último día del mes). */
    public function fin(): CarbonImmutable
    {
        return $this->fin;
    }

    /** `fecha` del periodo anterior, del siguiente y del que contiene hoy, para los enlaces. */
    public function fechaAnterior(): string
    {
        return ($this->vista === 'mes' ? $this->inicio->subMonthNoOverflow() : $this->inicio->subWeek())->toDateString();
    }

    public function fechaSiguiente(): string
    {
        return ($this->vista === 'mes' ? $this->inicio->addMonthNoOverflow() : $this->inicio->addWeek())->toDateString();
    }

    public function fechaHoy(): string
    {
        $hoy = $this->ahora->startOfDay();

        return ($this->vista === 'mes' ? $hoy->startOfMonth() : $hoy->startOfWeek(CarbonInterface::MONDAY))->toDateString();
    }

    /** `fecha` que identifica este periodo en los enlaces (su primer día). */
    public function fechaPeriodo(): string
    {
        return $this->inicio->toDateString();
    }

    /** `fecha` de la semana (o el mes) del otro modo de vista, para el interruptor «Semana · Mes». */
    public function fechaOtraVista(): string
    {
        $base = $this->dia ?? ($this->contieneHoy() ? $this->ahora->startOfDay() : $this->inicio);

        return ($this->vista === 'mes' ? $base->startOfWeek(CarbonInterface::MONDAY) : $base->startOfMonth())->toDateString();
    }

    public function contieneHoy(): bool
    {
        return $this->enPeriodo($this->ahora);
    }

    public function vacio(): bool
    {
        return $this->citas === [];
    }

    /** @return list<object> */
    public function citas(): array
    {
        return $this->citas;
    }

    // ── Cuadrícula de la semana ───────────────────────────────────────

    /** @return list<array{fila: int, hora: string, etiqueta: ?string}> */
    public function filas(): array
    {
        $filas = [];
        for ($minuto = $this->minutoInicial, $fila = 1; $minuto < $this->minutoFinal; $minuto += self::TRAMO_MIN, $fila++) {
            $filas[] = [
                'fila' => $fila,
                'hora' => $this->hora($minuto),
                'etiqueta' => $minuto % 60 === 0 ? $this->etiqueta($minuto) : null,
            ];
        }

        return $filas;
    }

    public function numeroDeFilas(): int
    {
        return intdiv($this->minutoFinal - $this->minutoInicial, self::TRAMO_MIN);
    }

    /**
     * Días del periodo (de lunes a sábado, y el domingo si tiene citas), cada uno con sus citas
     * posicionadas, sus huecos (solo en la semana) y lo que queda fuera del horario.
     *
     * @return list<array<string, mixed>>
     */
    public function dias(): array
    {
        if ($this->dias !== null) {
            return $this->dias;
        }

        $porDia = [];
        foreach ($this->citas as $cita) {
            $porDia[$this->momento($cita)->toDateString()][] = $cita;
        }

        $dias = [];
        for ($fecha = $this->inicio->startOfDay(); $fecha->lessThanOrEqualTo($this->fin); $fecha = $fecha->addDay()) {
            $iso = $fecha->toDateString();
            if ($fecha->dayOfWeekIso === 7 && ! isset($porDia[$iso])) {
                continue;
            }
            $dias[] = $this->dia($fecha, $porDia[$iso] ?? []);
        }

        return $this->dias = $dias;
    }

    /** Día de la lista que se ve en el móvil: el elegido; si no, hoy si está en el periodo; si no, el primero. */
    public function diaMovil(): CarbonImmutable
    {
        if ($this->dia !== null) {
            return $this->dia->startOfDay();
        }
        if ($this->contieneHoy() && $this->ahora->dayOfWeekIso !== 7) {
            return $this->ahora->startOfDay();
        }

        $primero = $this->inicio->startOfDay();

        return $primero->dayOfWeekIso === 7 ? $primero->addDay() : $primero;
    }

    // ── Mes ────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> celdas de lunes a sábado, desde la primera semana del mes hasta la última */
    public function celdasMes(): array
    {
        $mes = $this->fecha->startOfMonth();
        $desde = $mes->startOfWeek(CarbonInterface::MONDAY);
        $ultimo = $mes->endOfMonth()->startOfDay();
        $hasta = $ultimo->dayOfWeekIso === 7 ? $ultimo->subDay() : $ultimo->startOfWeek(CarbonInterface::MONDAY)->addDays(5);

        $totales = [];
        foreach ($this->citas as $cita) {
            $iso = $this->momento($cita)->toDateString();
            $totales[$iso] = ($totales[$iso] ?? 0) + 1;
        }

        $celdas = [];
        for ($fecha = $desde; $fecha->lessThanOrEqualTo($hasta); $fecha = $fecha->addDay()) {
            if ($fecha->dayOfWeekIso === 7) {
                continue;
            }
            $iso = $fecha->toDateString();
            $celdas[] = [
                'fecha' => $fecha,
                'iso' => $iso,
                'numero' => $fecha->day,
                'corto' => self::CORTOS[$fecha->dayOfWeekIso],
                'nombre' => $this->nombreDia($fecha),
                'fuera' => $fecha->month !== $mes->month,
                'hoy' => $fecha->isSameDay($this->ahora),
                'elegido' => $this->dia !== null && $fecha->isSameDay($this->dia),
                'total' => $totales[$iso] ?? 0,
            ];
        }

        return $celdas;
    }

    // ── Títulos ────────────────────────────────────────────────────────

    /** Título del periodo: «16 – 21 de noviembre de 2026» o «Noviembre de 2026». */
    public function titulo(): string
    {
        if ($this->vista === 'mes') {
            return ucfirst(self::MESES[$this->inicio->month]).' de '.$this->inicio->year;
        }

        $sabado = $this->inicio->addDays(5);
        if ($this->inicio->year !== $sabado->year) {
            return $this->fechaLarga($this->inicio, true).' – '.$this->fechaLarga($sabado, true);
        }
        if ($this->inicio->month !== $sabado->month) {
            return $this->fechaLarga($this->inicio).' – '.$this->fechaLarga($sabado).' de '.$sabado->year;
        }

        return $this->inicio->day.' – '.$this->fechaLarga($sabado).' de '.$sabado->year;
    }

    /** Título del resumen de cifras: dice de qué periodo son (refinado 5). */
    public function tituloResumen(): string
    {
        if ($this->dia !== null) {
            return 'Resumen del '.$this->nombreDia($this->dia, false);
        }
        if ($this->vista === 'mes') {
            return 'Resumen de '.self::MESES[$this->inicio->month].' de '.$this->inicio->year;
        }

        $sabado = $this->inicio->addDays(5);
        $desde = $this->inicio->month === $sabado->month ? (string) $this->inicio->day : $this->fechaLarga($this->inicio);

        return 'Resumen de la semana del '.$desde.' al '.$this->fechaLarga($sabado);
    }

    /** Título de la lista: «Citas de la semana», «Citas del mes» o «Citas del martes 17 de noviembre». */
    public function tituloLista(): string
    {
        if ($this->dia !== null) {
            return 'Citas del '.$this->nombreDia($this->dia, false);
        }

        return $this->vista === 'mes' ? 'Citas del mes' : 'Citas de la semana';
    }

    /** Título de la lista en el móvil: nombra el día que muestra (refinado 1). */
    public function tituloMovil(): string
    {
        $dia = $this->diaMovil();
        $nombre = self::DIAS[$dia->dayOfWeekIso].' '.$dia->day;

        return $dia->isSameDay($this->ahora) ? 'Citas de hoy, '.$nombre : 'Citas del '.$nombre;
    }

    public function verTodo(): string
    {
        return $this->vista === 'mes' ? 'Ver todo el mes' : 'Ver toda la semana';
    }

    public function sinCitas(): string
    {
        return $this->vista === 'mes' ? 'No hay citas en este mes' : 'No hay citas en esta semana';
    }

    /** «Martes 17 de noviembre» (o «martes 17 de noviembre» con `$mayuscula` en false). */
    public function nombreDia(CarbonImmutable $fecha, bool $mayuscula = true): string
    {
        $nombre = self::DIAS[$fecha->dayOfWeekIso].' '.$this->fechaLarga($fecha);

        return $mayuscula ? ucfirst($nombre) : $nombre;
    }

    // ── Internos ───────────────────────────────────────────────────────

    /** @param list<object> $citas */
    private function dia(CarbonImmutable $fecha, array $citas): array
    {
        $iso = $fecha->toDateString();

        $posiciones = [];
        foreach ($citas as $cita) {
            [$fila, $tramos] = $this->posicion($cita);
            $posiciones[] = [
                'cita' => $cita,
                'id' => $cita->id,
                'fila' => $fila,
                'tramos' => $tramos,
                'carril' => null,
                'atenuada' => in_array($cita->estado, self::ATENUADOS, true),
                'hora' => $this->momento($cita)->format('H:i'),
            ];
        }

        $huecos = $this->vista === 'semana' ? $this->huecos($fecha) : [];
        $carriles = $this->repartirCarriles($posiciones, $huecos);

        return [
            'fecha' => $fecha,
            'iso' => $iso,
            'corto' => self::CORTOS[$fecha->dayOfWeekIso],
            'numero' => $fecha->day,
            'nombre' => $this->nombreDia($fecha),
            'hoy' => $fecha->isSameDay($this->ahora),
            'elegido' => $this->dia !== null && $fecha->isSameDay($this->dia),
            'total' => count($posiciones),
            'carriles' => $carriles,
            'citas' => $posiciones,
            'huecos' => $huecos,
            'cerrados' => $this->cerrados($fecha),
        ];
    }

    /** @return list<array{fila: int, hora: string, fecha: string, carril: ?int}> */
    private function huecos(CarbonImmutable $fecha): array
    {
        $horario = $this->horarioSemanal[$fecha->dayOfWeekIso] ?? null;
        if ($horario === null) {
            return [];
        }

        $iso = $fecha->toDateString();
        [$apertura, $cierre] = [$this->minutos($horario[0]), $this->minutos($horario[1])];

        $huecos = [];
        for ($minuto = $apertura; $minuto + self::TRAMO_MIN <= $cierre; $minuto += self::TRAMO_MIN) {
            $inicio = $fecha->startOfDay()->addMinutes($minuto);
            if ($inicio->lessThanOrEqualTo($this->ahora)) {
                continue;
            }
            $fila = $this->filaDe($minuto);
            if (isset($this->ocupados[$iso][$fila])) {
                continue;
            }
            $huecos[] = ['fila' => $fila, 'hora' => $this->hora($minuto), 'fecha' => $iso, 'carril' => null];
        }

        return $huecos;
    }

    /** @return list<array{fila: int, tramos: int, texto: string}> */
    private function cerrados(CarbonImmutable $fecha): array
    {
        $total = $this->numeroDeFilas();
        $horario = $this->horarioSemanal[$fecha->dayOfWeekIso] ?? null;
        if ($horario === null) {
            return [['fila' => 1, 'tramos' => $total, 'texto' => 'Cerrado']];
        }

        [$apertura, $cierre] = [$this->minutos($horario[0]), $this->minutos($horario[1])];
        $cerrados = [];
        if ($apertura > $this->minutoInicial) {
            $cerrados[] = [
                'fila' => 1,
                'tramos' => intdiv($apertura - $this->minutoInicial, self::TRAMO_MIN),
                'texto' => 'Cerrado hasta las '.$this->etiqueta($apertura),
            ];
        }
        if ($cierre < $this->minutoFinal) {
            $cerrados[] = [
                'fila' => $this->filaDe($cierre),
                'tramos' => intdiv($this->minutoFinal - $cierre, self::TRAMO_MIN),
                'texto' => 'Cerrado desde las '.$this->etiqueta($cierre),
            ];
        }

        return $cerrados;
    }

    /**
     * Reparte en carriles las citas que coinciden (y los huecos que caen junto a una atenuada). Lo que
     * no coincide con nada ocupa todo el ancho (`carril` nulo). Devuelve el número de carriles del día:
     * el máximo de elementos a la vez.
     *
     * @param  list<array<string, mixed>>  $posiciones
     * @param  list<array<string, mixed>>  $huecos
     */
    private function repartirCarriles(array &$posiciones, array &$huecos): int
    {
        $elementos = [];
        foreach ($posiciones as $i => $posicion) {
            $elementos[] = ['tipo' => 'cita', 'i' => $i, 'desde' => $posicion['fila'], 'hasta' => $posicion['fila'] + $posicion['tramos'] - 1];
        }
        foreach ($huecos as $i => $hueco) {
            foreach ($posiciones as $posicion) {
                if ($hueco['fila'] >= $posicion['fila'] && $hueco['fila'] <= $posicion['fila'] + $posicion['tramos'] - 1) {
                    $elementos[] = ['tipo' => 'hueco', 'i' => $i, 'desde' => $hueco['fila'], 'hasta' => $hueco['fila']];
                    break;
                }
            }
        }
        usort($elementos, fn ($a, $b) => [$a['desde'], $a['tipo'] === 'hueco', $a['i']] <=> [$b['desde'], $b['tipo'] === 'hueco', $b['i']]);

        $maximo = 1;
        $grupo = [];
        $finGrupo = 0;
        foreach ($elementos as $elemento) {
            if ($grupo !== [] && $elemento['desde'] > $finGrupo) {
                $maximo = max($maximo, $this->asignarCarriles($grupo, $posiciones, $huecos));
                $grupo = [];
            }
            $finGrupo = $grupo === [] ? $elemento['hasta'] : max($finGrupo, $elemento['hasta']);
            $grupo[] = $elemento;
        }
        if ($grupo !== []) {
            $maximo = max($maximo, $this->asignarCarriles($grupo, $posiciones, $huecos));
        }

        return $maximo;
    }

    /** Asigna carriles a un grupo de elementos que se solapan; devuelve cuántos usa. */
    private function asignarCarriles(array $grupo, array &$posiciones, array &$huecos): int
    {
        if (count($grupo) === 1) {
            return 1;
        }

        $finDeCarril = [];
        foreach ($grupo as $elemento) {
            $carril = null;
            foreach ($finDeCarril as $c => $fin) {
                if ($fin < $elemento['desde']) {
                    $carril = $c;
                    break;
                }
            }
            $carril ??= count($finDeCarril) + 1;
            $finDeCarril[$carril] = $elemento['hasta'];

            if ($elemento['tipo'] === 'cita') {
                $posiciones[$elemento['i']]['carril'] = $carril;
            } else {
                $huecos[$elemento['i']]['carril'] = $carril;
            }
        }

        return count($finDeCarril);
    }

    private function calcularFilas(): void
    {
        $aperturas = $cierres = [];
        foreach ($this->horarioSemanal as [$apertura, $cierre]) {
            $aperturas[] = $this->minutos($apertura);
            $cierres[] = $this->minutos($cierre);
        }
        $inicial = $aperturas === [] ? 9 * 60 : min($aperturas);
        $final = $cierres === [] ? 20 * 60 : max($cierres);

        foreach ($this->citas as $cita) {
            $momento = $this->momento($cita);
            $empieza = $momento->hour * 60 + $momento->minute;
            $termina = min(24 * 60, $empieza + max(1, (int) $cita->duracion_min));
            $inicial = min($inicial, intdiv($empieza, self::TRAMO_MIN) * self::TRAMO_MIN);
            $final = max($final, (int) (ceil($termina / self::TRAMO_MIN) * self::TRAMO_MIN));
        }

        $this->minutoInicial = $inicial;
        $this->minutoFinal = $final;
    }

    /** @return array{0: int, 1: int} fila de inicio y número de tramos */
    private function posicion(object $cita): array
    {
        $momento = $this->momento($cita);
        $empieza = $momento->hour * 60 + $momento->minute;
        $termina = min(24 * 60, $empieza + max(1, (int) $cita->duracion_min));

        $fila = max(1, $this->filaDe($empieza));
        $ultima = intdiv($termina - $this->minutoInicial + self::TRAMO_MIN - 1, self::TRAMO_MIN);

        return [$fila, max(1, $ultima - $fila + 1)];
    }

    private function filaDe(int $minuto): int
    {
        return intdiv($minuto - $this->minutoInicial, self::TRAMO_MIN) + 1;
    }

    private function enPeriodo(CarbonImmutable $momento): bool
    {
        return $momento->greaterThanOrEqualTo($this->inicio) && $momento->lessThanOrEqualTo($this->fin);
    }

    private function momento(object $cita): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $cita->fecha_hora, $this->ahora->getTimezone());
    }

    /** @return list<object> */
    private function ordenadas(iterable $citas): array
    {
        $lista = [];
        foreach ($citas as $cita) {
            $lista[] = $cita;
        }
        // Orden estable por hora: a la misma hora se conserva el orden de la consulta (por paciente).
        usort($lista, fn (object $a, object $b) => (string) $a->fecha_hora <=> (string) $b->fecha_hora);

        return $lista;
    }

    private function minutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    private function hora(int $minuto): string
    {
        return sprintf('%02d:%02d', intdiv($minuto, 60), $minuto % 60);
    }

    private function etiqueta(int $minuto): string
    {
        return sprintf('%d:%02d', intdiv($minuto, 60), $minuto % 60);
    }

    private function fechaLarga(CarbonImmutable $fecha, bool $conAnio = false): string
    {
        return $fecha->day.' de '.self::MESES[$fecha->month].($conAnio ? ' de '.$fecha->year : '');
    }
}
