<?php

namespace App\Reportes;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Reportes de la administración sobre las citas (spec 047): el periodo que se puede pedir y las
 * cifras de «Citas por estado», «Citas por dentista» y «Servicios más solicitados».
 *
 * Las cifras salen de una sola consulta agrupada por dentista, servicio y estado (plan D2): los
 * tres reportes y el resumen se arman con ese resultado, así que siempre cuadran entre sí (CA4).
 * Nunca lee pacientes, notas ni horas de una cita (CA10). Una cita cuenta por la fecha para la
 * que está agendada (`citas.fecha_hora`, que guarda la hora local de la clínica).
 */
class ReporteCitas
{
    /** Los seis estados de una cita (spec 026), en el orden en que se muestran (CA3). */
    public const ESTADOS = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

    /** Días que puede abarcar un periodo, contando los dos extremos (CA7, TM3). */
    public const MAXIMO_DIAS = 366;

    private const FORMATO = 'Y-m-d';

    private const SIN_SERVICIO = 'Sin servicio';

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Reglas de `desde` y `hasta` (CA7, TM2): las dos fechas juntas o ninguna, como AAAA-MM-DD,
     * en orden y con 366 días como mucho. Una lista u otro valor que no sea texto no pasa el formato.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function reglasDePeriodo(): array
    {
        return [
            'desde' => ['bail', 'nullable', 'required_with:hasta', 'date_format:'.self::FORMATO],
            'hasta' => ['bail', 'nullable', 'required_with:desde', 'date_format:'.self::FORMATO, self::reglaDeOrdenYDuracion()],
        ];
    }

    /** @return array<string, string> */
    public static function mensajesDePeriodo(): array
    {
        return [
            'desde.required_with' => 'Falta la fecha «Desde».',
            'hasta.required_with' => 'Falta la fecha «Hasta».',
            'desde.date_format' => 'Escribe la fecha como AAAA-MM-DD.',
            'hasta.date_format' => 'Escribe la fecha como AAAA-MM-DD.',
        ];
    }

    /**
     * El mes en curso de la clínica (CA1, plan D4).
     *
     * @return array{desde: string, hasta: string}
     */
    public static function periodoPorDefecto(): array
    {
        $hoy = self::hoy();

        return [
            'desde' => $hoy->copy()->startOfMonth()->format(self::FORMATO),
            'hasta' => $hoy->copy()->endOfMonth()->format(self::FORMATO),
        ];
    }

    /**
     * Periodos de un clic (CA2), calculados en la hora de la clínica.
     *
     * @return array<int, array{etiqueta: string, desde: string, hasta: string}>
     */
    public static function atajos(): array
    {
        $hoy = self::hoy();
        $mesPasado = $hoy->copy()->startOfMonth()->subMonth();

        return [
            ['etiqueta' => 'Este mes'] + self::periodoPorDefecto(),
            [
                'etiqueta' => 'Mes pasado',
                'desde' => $mesPasado->format(self::FORMATO),
                'hasta' => $mesPasado->copy()->endOfMonth()->format(self::FORMATO),
            ],
            [
                'etiqueta' => 'Este año',
                'desde' => $hoy->copy()->startOfYear()->format(self::FORMATO),
                'hasta' => $hoy->copy()->endOfYear()->format(self::FORMATO),
            ],
        ];
    }

    /**
     * Las cifras del periodo, con los dos días extremos incluidos. `$desde` y `$hasta` llegan ya
     * validados con `reglasDePeriodo()`.
     *
     * @return array{total: int, cancelacion: array, inasistencia: array, porEstado: array, porDentista: array, porServicio: array}
     */
    public function generar(string $desde, string $hasta): array
    {
        $grupos = $this->db->table('citas')
            ->whereBetween('fecha_hora', ["{$desde} 00:00:00", "{$hasta} 23:59:59"])
            ->groupBy('dentista_id', 'servicio_id', 'estado')
            ->select('dentista_id', 'servicio_id', 'estado')
            ->selectRaw('COUNT(*) AS citas')
            ->get();

        $porEstado = array_fill_keys(self::ESTADOS, 0);
        $porDentista = [];
        $porServicio = [];
        foreach ($grupos as $grupo) {
            $citas = (int) $grupo->citas;
            $porEstado[$grupo->estado] = ($porEstado[$grupo->estado] ?? 0) + $citas;

            $porDentista[$grupo->dentista_id] ??= ['total' => 0, 'completadas' => 0, 'canceladas' => 0, 'no_asistio' => 0];
            $porDentista[$grupo->dentista_id]['total'] += $citas;
            $columna = ['completada' => 'completadas', 'cancelada' => 'canceladas', 'no_asistio' => 'no_asistio'][$grupo->estado] ?? null;
            if ($columna !== null) {
                $porDentista[$grupo->dentista_id][$columna] += $citas;
            }

            if ($grupo->estado !== 'cancelada') {
                $servicio = $grupo->servicio_id ?? '';
                $porServicio[$servicio] = ($porServicio[$servicio] ?? 0) + $citas;
            }
        }

        $total = array_sum($porEstado);

        return [
            'total' => $total,
            'cancelacion' => ['citas' => $porEstado['cancelada'], 'porcentaje' => self::porcentaje($porEstado['cancelada'], $total)],
            'inasistencia' => ['citas' => $porEstado['no_asistio'], 'porcentaje' => self::porcentaje($porEstado['no_asistio'], $total)],
            'porEstado' => array_map(
                fn (string $estado) => ['estado' => $estado, 'citas' => $porEstado[$estado], 'porcentaje' => self::porcentaje($porEstado[$estado], $total)],
                self::ESTADOS,
            ),
            'porDentista' => $this->filasPorDentista($porDentista),
            'porServicio' => $this->filasPorServicio($porServicio),
        ];
    }

    /**
     * @param  array<string, array{total: int, completadas: int, canceladas: int, no_asistio: int}>  $cuentas  por id de `personal`
     * @return array{filas: array, totales: array{total: int, completadas: int, canceladas: int, no_asistio: int}}
     */
    private function filasPorDentista(array $cuentas): array
    {
        $totales = ['total' => 0, 'completadas' => 0, 'canceladas' => 0, 'no_asistio' => 0];
        if ($cuentas === []) {
            return ['filas' => [], 'totales' => $totales];
        }

        // Nombre de cada dentista con citas, esté activo o no (CA4).
        $nombres = $this->db->table('personal')
            ->join('usuarios', 'usuarios.id', '=', 'personal.usuario_id')
            ->whereIn('personal.id', array_keys($cuentas))
            ->get(['personal.id', 'usuarios.nombre', 'usuarios.apellido'])
            ->keyBy('id');

        $filas = [];
        foreach ($cuentas as $id => $cuenta) {
            $persona = $nombres->get($id);
            $nombre = $persona === null ? 'Dentista sin nombre' : trim("{$persona->nombre} {$persona->apellido}");
            $filas[] = ['nombre' => $nombre] + $cuenta;
            foreach ($totales as $columna => $suma) {
                $totales[$columna] = $suma + $cuenta[$columna];
            }
        }

        // De más a menos citas; el empate, por nombre y apellido.
        usort($filas, fn (array $a, array $b) => [$b['total'], self::clave($a['nombre'])] <=> [$a['total'], self::clave($b['nombre'])]);

        return ['filas' => $filas, 'totales' => $totales];
    }

    /**
     * @param  array<string, int>  $cuentas  citas no canceladas por id de servicio ('' = sin servicio)
     * @return array{filas: array, total: int}
     */
    private function filasPorServicio(array $cuentas): array
    {
        $total = array_sum($cuentas);
        if ($cuentas === []) {
            return ['filas' => [], 'total' => 0];
        }

        // Nombre de cada servicio con citas, esté activo o no (CA5).
        $ids = array_values(array_filter(array_map('strval', array_keys($cuentas)), fn (string $id) => $id !== ''));
        $nombres = $ids === [] ? collect() : $this->db->table('servicios')->whereIn('id', $ids)->pluck('nombre', 'id');

        $filas = [];
        foreach ($cuentas as $id => $citas) {
            $filas[] = [
                'nombre' => $id === '' ? self::SIN_SERVICIO : (string) $nombres->get($id, 'Servicio sin nombre'),
                'citas' => $citas,
                'porcentaje' => self::porcentaje($citas, $total),
            ];
        }

        // De más a menos citas; el empate, por nombre. «Sin servicio» se ordena como una fila más.
        usort($filas, fn (array $a, array $b) => [$b['citas'], self::clave($a['nombre'])] <=> [$a['citas'], self::clave($b['nombre'])]);

        return ['filas' => $filas, 'total' => $total];
    }

    /** Porcentaje con un decimal; con total cero no hay nada que repartir. */
    private static function porcentaje(int $parte, int $total): float
    {
        return $total === 0 ? 0.0 : round($parte * 100 / $total, 1);
    }

    /** Clave de orden alfabético: sin mayúsculas ni acentos. */
    private static function clave(string $nombre): string
    {
        return mb_strtolower(Str::ascii($nombre));
    }

    private static function hoy(): Carbon
    {
        return Carbon::now((string) config('clinica.zona_horaria'));
    }

    /**
     * «Hasta» no va antes que «Desde» y el periodo no pasa de `MAXIMO_DIAS`. Solo compara cuando
     * las dos fechas ya son fechas AAAA-MM-DD: con una que no lo es, no interpreta nada ni lanza
     * nada, y el error que se muestra es el de formato de su campo.
     */
    private static function reglaDeOrdenYDuracion(): ValidationRule
    {
        return new class implements DataAwareRule, ValidationRule
        {
            /** @var array<string, mixed> */
            private array $datos = [];

            public function setData(array $data): static
            {
                $this->datos = $data;

                return $this;
            }

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                $desde = $this->fecha($this->datos['desde'] ?? null);
                $hasta = $this->fecha($value);
                if ($desde === null || $hasta === null) {
                    return;
                }

                if ($hasta < $desde) {
                    $fail('«Hasta» no puede ser anterior a «Desde».');
                } elseif ($desde->diff($hasta)->days + 1 > ReporteCitas::MAXIMO_DIAS) {
                    $fail('El periodo puede ser de '.ReporteCitas::MAXIMO_DIAS.' días como mucho.');
                }
            }

            private function fecha(mixed $valor): ?DateTimeImmutable
            {
                if (! is_string($valor)) {
                    return null;
                }
                $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

                return $fecha !== false && $fecha->format('Y-m-d') === $valor ? $fecha : null;
            }
        };
    }
}
