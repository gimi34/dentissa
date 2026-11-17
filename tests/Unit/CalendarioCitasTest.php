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
        ?string $dentista = null,
    ): CalendarioCitas {
        return new CalendarioCitas(
            $vista,
            $this->momento($fecha),
            $dia === null ? null : $this->momento($dia),
            $citas,
            $ocupacion ?? $citas,
            self::HORARIO,
            $this->momento($ahora),
            $dentista,
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

        $dias = $calendario->dias();
        $this->assertSame('2026-11-22', $dias[array_key_last($dias)]['iso'], 'El domingo con cita se añade');
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

    // ── T011 · Carriles, huecos y cuentas ─────────────────────────────

    public function test_dos_y_tres_citas_a_la_vez_van_en_carriles_y_ensanchan_el_dia(): void
    {
        $dos = [$this->cita('2026-11-17 11:00', 60), $this->cita('2026-11-17 11:30', 30, 'confirmada', 'D2')];
        $tres = [
            $this->cita('2026-11-18 15:00', 90), $this->cita('2026-11-18 15:30', 30, 'agendada', 'D2'),
            $this->cita('2026-11-18 15:30', 30, 'agendada', 'D3'),
        ];
        $sola = $this->cita('2026-11-18 09:00', 30);
        $calendario = $this->calendario(citas: [...$dos, ...$tres, $sola]);

        $martes = $this->dia($calendario, '2026-11-17');
        $this->assertSame(2, $martes['carriles']);
        $this->assertSame([1, 2], array_column($martes['citas'], 'carril'));

        $miercoles = $this->dia($calendario, '2026-11-18');
        $this->assertSame(3, $miercoles['carriles']);
        $porId = $this->carrilPorCita($miercoles);
        $this->assertSame([1, 2, 3], [$porId[$tres[0]->id], $porId[$tres[1]->id], $porId[$tres[2]->id]]);
        $this->assertNull($porId[$sola->id], 'Una cita que no coincide con otra ocupa todo el ancho');

        $this->assertSame(1, $this->dia($calendario, '2026-11-19')['carriles'], 'Un día sin citas');
    }

    public function test_una_cancelada_junto_a_otra_ocupa_su_carril_y_se_marca_atenuada(): void
    {
        $cancelada = $this->cita('2026-11-19 13:00', 30, 'cancelada');
        $vigente = $this->cita('2026-11-19 13:00', 30, 'agendada', 'D2');
        $jueves = $this->dia($this->calendario(citas: [$cancelada, $vigente]), '2026-11-19');

        $this->assertSame(2, $jueves['carriles']);
        $porId = [];
        foreach ($jueves['citas'] as $posicion) {
            $porId[$posicion['cita']->id] = $posicion;
        }
        $this->assertTrue($porId[$cancelada->id]['atenuada']);
        $this->assertFalse($porId[$vigente->id]['atenuada']);
        $this->assertNotSame($porId[$cancelada->id]['carril'], $porId[$vigente->id]['carril']);
    }

    public function test_los_huecos_empiezan_en_el_tramo_siguiente_a_ahora(): void
    {
        $alas1120 = $this->dia($this->calendario(ahora: '2026-11-17 11:20'), '2026-11-17')['huecos'];
        $this->assertSame('11:30', $alas1120[0]['hora']);

        $alas1130 = $this->dia($this->calendario(ahora: '2026-11-17 11:30:00'), '2026-11-17')['huecos'];
        $this->assertSame('12:00', $alas1130[0]['hora'], 'El tramo que empieza justo ahora ya no se ofrece');

        $this->assertSame([], $this->dia($this->calendario(), '2026-11-16')['huecos'], 'El lunes ya pasó');
    }

    public function test_no_hay_hueco_en_un_tramo_con_una_cita_vigente(): void
    {
        $cita = $this->cita('2026-11-18 12:00', 60);
        $horas = array_column($this->dia($this->calendario(citas: [$cita]), '2026-11-18')['huecos'], 'hora');

        $this->assertNotContains('12:00', $horas);
        $this->assertNotContains('12:30', $horas);
        $this->assertContains('11:30', $horas);
        $this->assertContains('13:00', $horas);
    }

    public function test_una_cita_de_las_10_15_con_45_minutos_no_deja_hueco_a_las_10_ni_a_las_10_30(): void
    {
        $cita = $this->cita('2026-11-19 10:15', 45);
        $horas = array_column($this->dia($this->calendario(citas: [$cita]), '2026-11-19')['huecos'], 'hora');

        $this->assertNotContains('10:00', $horas);
        $this->assertNotContains('10:30', $horas);
        $this->assertContains('09:30', $horas);
        $this->assertContains('11:00', $horas);
    }

    public function test_no_hay_hueco_sobre_una_cita_vigente_que_el_buscador_o_el_estado_dejan_fuera(): void
    {
        // La lista filtrada no la tiene, pero la ocupación sí (A28).
        $oculta = $this->cita('2026-11-18 12:00', 30, 'confirmada');
        $huecos = $this->dia($this->calendario(citas: [], ocupacion: [$oculta]), '2026-11-18')['huecos'];
        $horas = array_column($huecos, 'hora');

        $this->assertNotContains('12:00', $horas);
        $this->assertContains('12:30', $horas);
    }

    public function test_hay_hueco_junto_a_una_cancelada_o_un_no_asistio_en_su_propio_carril(): void
    {
        $cancelada = $this->cita('2026-11-18 13:00', 30, 'cancelada');
        $noAsistio = $this->cita('2026-11-19 10:00', 30, 'no_asistio');
        $calendario = $this->calendario(citas: [$cancelada, $noAsistio]);

        foreach (['2026-11-18' => '13:00', '2026-11-19' => '10:00'] as $iso => $hora) {
            $dia = $this->dia($calendario, $iso);
            $huecos = array_column($dia['huecos'], null, 'hora');
            $this->assertArrayHasKey($hora, $huecos, "Hueco el {$iso} a las {$hora}");
            $this->assertSame(2, $dia['carriles']);
            $this->assertNotNull($huecos[$hora]['carril']);
            $this->assertNotSame($dia['citas'][0]['carril'], $huecos[$hora]['carril'], 'El hueco va en otro carril');
        }
    }

    public function test_con_una_sola_dentista_los_huecos_son_sus_tramos_libres(): void
    {
        $deOtra = $this->cita('2026-11-18 12:00', 30, 'agendada', 'D2');
        $suya = $this->cita('2026-11-18 13:00', 30, 'agendada', 'D1');

        $todas = array_column($this->dia($this->calendario(citas: [$deOtra, $suya]), '2026-11-18')['huecos'], 'hora');
        $this->assertNotContains('12:00', $todas);
        $this->assertNotContains('13:00', $todas);

        $deD1 = array_column($this->dia($this->calendario(citas: [$suya], ocupacion: [$deOtra, $suya], dentista: 'D1'), '2026-11-18')['huecos'], 'hora');
        $this->assertContains('12:00', $deD1, 'La cita de otra dentista no ocupa el tramo de D1');
        $this->assertNotContains('13:00', $deD1);
    }

    public function test_no_hay_huecos_fuera_del_horario_y_la_hora_lleva_cero(): void
    {
        $calendario = $this->calendario(ahora: '2026-11-16 08:00');

        $lunes = $this->dia($calendario, '2026-11-16')['huecos'];
        $this->assertSame('09:00', $lunes[0]['hora']);
        $this->assertSame(1, $lunes[0]['fila']);
        $this->assertSame('2026-11-16', $lunes[0]['fecha']);
        $this->assertSame('19:30', end($lunes)['hora']);
        $this->assertCount(22, $lunes);

        $sabado = array_column($this->dia($calendario, '2026-11-21')['huecos'], 'hora');
        $this->assertCount(10, $sabado);
        $this->assertSame('13:30', end($sabado), 'El sábado cierra a las 14:00');
    }

    public function test_cada_dia_y_cada_celda_del_mes_cuentan_sus_citas(): void
    {
        $citas = [
            $this->cita('2026-11-17 09:00'), $this->cita('2026-11-17 10:00', 30, 'cancelada'),
            $this->cita('2026-11-19 12:00'),
        ];
        $semana = $this->calendario(citas: $citas);

        $this->assertSame([0, 2, 0, 1, 0, 0], array_column($semana->dias(), 'total'));

        $mes = $this->calendario(citas: $citas, vista: 'mes');
        $this->assertSame(2, $this->celda($mes->celdasMes(), '2026-11-17')['total']);
        $this->assertSame(1, $this->celda($mes->celdasMes(), '2026-11-19')['total']);
        $this->assertSame(0, $this->celda($mes->celdasMes(), '2026-11-18')['total']);
    }

    /** @return array<string, ?int> carril de cada cita del día, por id */
    private function carrilPorCita(array $dia): array
    {
        $carriles = [];
        foreach ($dia['citas'] as $posicion) {
            $carriles[$posicion['cita']->id] = $posicion['carril'];
        }

        return $carriles;
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
