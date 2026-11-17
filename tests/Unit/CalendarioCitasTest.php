<?php

namespace Tests\Unit;

use App\Agenda\CalendarioCitas;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Spec 049: periodo, cuadrícula, carriles, huecos y cuentas del calendario de citas, sin base de
 * datos. «Ahora» es el martes 17/11/2026 a las 11:20, como en los candidatos del acta.
 */
class CalendarioCitasTest extends TestCase
{
    private const ZONA = 'America/Mexico_City';

    private const HORARIO = [
        1 => ['9:00', '20:00'], 2 => ['9:00', '20:00'], 3 => ['9:00', '20:00'],
        4 => ['9:00', '20:00'], 5 => ['9:00', '20:00'], 6 => ['9:00', '14:00'],
    ];

    private function momento(string $valor): CarbonImmutable
    {
        return CarbonImmutable::parse($valor, self::ZONA);
    }

    private function cita(string $inicio, int $duracion = 30, string $estado = 'agendada', string $dentista = 'D1'): object
    {
        static $n = 0;
        $n++;

        return (object) [
            'id' => 'c'.$n,
            'fecha_hora' => $inicio.':00',
            'duracion_min' => $duracion,
            'estado' => $estado,
            'paciente' => 'Paciente '.$n,
            'dentista' => 'Dentista '.$dentista,
            'dentista_id' => $dentista,
        ];
    }

    /** @param list<object> $citas @param list<object>|null $ocupacion */
    private function calendario(
        string $fecha = '2026-11-17',
        array $citas = [],
        ?array $ocupacion = null,
        string $vista = 'semana',
        ?string $dia = null,
        string $ahora = '2026-11-17 11:20',
    ): CalendarioCitas {
        return new CalendarioCitas(
            $vista,
            $this->momento($fecha),
            $dia === null ? null : $this->momento($dia),
            $citas,
            $ocupacion ?? $citas,
            self::HORARIO,
            $this->momento($ahora),
        );
    }

    private function dia(CalendarioCitas $calendario, string $iso): array
    {
        foreach ($calendario->dias() as $dia) {
            if ($dia['iso'] === $iso) {
                return $dia;
            }
        }
        $this->fail("El calendario no tiene el día {$iso}.");
    }

    // ── T010 · Periodo y cuadrícula ───────────────────────────────────

    public function test_la_semana_va_de_lunes_a_sabado_para_un_lunes_un_miercoles_y_un_domingo(): void
    {
        foreach (['2026-11-16', '2026-11-18', '2026-11-22'] as $fecha) {
            $calendario = $this->calendario($fecha);

            $this->assertSame(
                ['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20', '2026-11-21'],
                array_column($calendario->dias(), 'iso'),
                "La semana de {$fecha}",
            );
            $this->assertSame('2026-11-16 00:00:00', $calendario->inicio()->format('Y-m-d H:i:s'));
            $this->assertSame('2026-11-22 23:59:59', $calendario->fin()->format('Y-m-d H:i:s'));
        }
    }

    public function test_el_mes_tiene_celdas_de_lunes_a_sabado_desde_la_primera_semana_hasta_la_ultima(): void
    {
        $calendario = $this->calendario('2026-11-17', vista: 'mes');
        $celdas = $calendario->celdasMes();

        // Noviembre de 2026 empieza en domingo y termina en lunes: del lunes 26/10 al sábado 5/12.
        $this->assertSame('2026-10-26', $celdas[0]['iso']);
        $this->assertSame('2026-12-05', end($celdas)['iso']);
        $this->assertCount(36, $celdas);
        foreach ($celdas as $celda) {
            $this->assertNotSame(7, CarbonImmutable::parse($celda['iso'])->dayOfWeekIso, 'Sin domingos');
        }
        $this->assertTrue($celdas[0]['fuera'], 'El 26/10 es de otro mes');
        $this->assertFalse($this->celda($celdas, '2026-11-02')['fuera']);
        $this->assertSame('2026-11-01 00:00:00', $calendario->inicio()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-30 23:59:59', $calendario->fin()->format('Y-m-d H:i:s'));
    }

    public function test_las_filas_van_de_las_9_a_las_20_y_el_sabado_cierra_a_las_14(): void
    {
        $calendario = $this->calendario();
        $filas = $calendario->filas();

        $this->assertCount(22, $filas);
        $this->assertSame(['fila' => 1, 'hora' => '09:00', 'etiqueta' => '9:00'], $filas[0]);
        $this->assertSame(['fila' => 2, 'hora' => '09:30', 'etiqueta' => null], $filas[1]);
        $this->assertSame('19:30', end($filas)['hora']);

        // Los días de lunes a viernes no tienen nada cerrado; el sábado, desde las 14:00 (fila 11).
        $this->assertSame([], $this->dia($calendario, '2026-11-20')['cerrados']);
        $this->assertSame(
            [['fila' => 11, 'tramos' => 12, 'texto' => 'Cerrado desde las 14:00']],
            $this->dia($calendario, '2026-11-21')['cerrados'],
        );
    }

    public function test_una_cita_fuera_del_horario_amplia_las_filas_y_el_domingo(): void
    {
        $temprana = $this->cita('2026-11-18 08:00', 60);
        $tardia = $this->cita('2026-11-19 20:00', 30);
        $domingo = $this->cita('2026-11-22 10:00', 30);
        $calendario = $this->calendario(citas: [$temprana, $tardia, $domingo]);

        $filas = $calendario->filas();
        $this->assertSame('08:00', $filas[0]['hora']);
        $this->assertSame('20:00', end($filas)['hora']);
        $this->assertCount(25, $filas);

        $this->assertSame('2026-11-22', end($calendario->dias())['iso'], 'El domingo con cita se añade');
        $this->assertSame(1, $this->dia($calendario, '2026-11-18')['citas'][0]['fila']);
        $this->assertSame(2, $this->dia($calendario, '2026-11-18')['citas'][0]['tramos']);

        // Antes de abrir y después de cerrar queda marcado como fuera de horario.
        $this->assertSame(
            [['fila' => 1, 'tramos' => 2, 'texto' => 'Cerrado hasta las 9:00'], ['fila' => 25, 'tramos' => 1, 'texto' => 'Cerrado desde las 20:00']],
            $this->dia($calendario, '2026-11-20')['cerrados'],
        );
        $this->assertSame(
            [['fila' => 1, 'tramos' => 25, 'texto' => 'Cerrado']],
            $this->dia($calendario, '2026-11-22')['cerrados'],
        );
    }

    public function test_una_cita_de_las_10_15_con_45_minutos_ocupa_los_tramos_de_las_10_y_las_10_30(): void
    {
        $cita = $this->cita('2026-11-19 10:15', 45);
        $posicion = $this->dia($this->calendario(citas: [$cita]), '2026-11-19')['citas'][0];

        $this->assertSame(3, $posicion['fila'], 'Empieza en el tramo de las 10:00');
        $this->assertSame(2, $posicion['tramos'], 'Ocupa los tramos de las 10:00 y las 10:30');
        $this->assertSame('10:15', $posicion['hora']);
    }

    private function celda(array $celdas, string $iso): array
    {
        foreach ($celdas as $celda) {
            if ($celda['iso'] === $iso) {
                return $celda;
            }
        }
        $this->fail("El mes no tiene la celda {$iso}.");
    }
}
