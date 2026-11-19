<?php

namespace Tests\Unit;

use App\Agenda\RelojClinica;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Spec 050, CA8: la hora de la clínica. Reloj por defecto: 03:50 UTC del 18/11/2026, las 21:50 del 17 en la
 * clínica (America/Mexico_City, UTC−6), la franja en la que hoy el panel ya cambia de día.
 */
class RelojClinicaTest extends TestCase
{
    private const ZONA = 'America/Mexico_City';

    protected function setUp(): void
    {
        parent::setUp();
        config(['clinica.zona_horaria' => self::ZONA]);
        Carbon::setTestNow(Carbon::parse('2026-11-18 03:50:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function relojEnLaClinica(string $momento): void
    {
        Carbon::setTestNow(Carbon::parse($momento, self::ZONA));
    }

    public function test_la_fecha_de_hoy_es_la_del_dia_de_la_clinica_en_sus_bordes(): void
    {
        foreach (['2026-11-17 17:59:00', '2026-11-17 18:00:00', '2026-11-17 23:59:00'] as $momento) {
            $this->relojEnLaClinica($momento);
            $this->assertSame('2026-11-17', RelojClinica::fechaHoy(), $momento);
        }

        $this->relojEnLaClinica('2026-11-18 00:00:00');
        $this->assertSame('2026-11-18', RelojClinica::fechaHoy());
    }

    public function test_el_momento_local_es_la_hora_de_la_clinica(): void
    {
        $this->assertSame('2026-11-17 21:50:00', RelojClinica::momentoLocal());
        $this->assertSame(self::ZONA, RelojClinica::ahora()->getTimezone()->getName());
        $this->assertSame('2026-11-17 21:50', RelojClinica::ahora()->format('Y-m-d H:i'));
    }

    public function test_hoy_es_el_inicio_del_dia_de_la_clinica(): void
    {
        $hoy = RelojClinica::hoy();

        $this->assertSame('2026-11-17 00:00:00', $hoy->format('Y-m-d H:i:s'));
        $this->assertSame(self::ZONA, $hoy->getTimezone()->getName());
    }

    public function test_una_fecha_del_sistema_se_pasa_a_la_hora_de_la_clinica(): void
    {
        $deTexto = RelojClinica::delSistema('2026-11-18 03:50:00');
        $deCarbon = RelojClinica::delSistema(Carbon::parse('2026-11-18 03:50:00', 'UTC'));

        $this->assertSame('2026-11-17 21:50', $deTexto->format('Y-m-d H:i'));
        $this->assertSame('2026-11-17 21:50', $deCarbon->format('Y-m-d H:i'));
        $this->assertSame(self::ZONA, $deCarbon->getTimezone()->getName());
        $this->assertNull(RelojClinica::delSistema(null));
    }

    public function test_la_hora_de_una_cita_se_lee_en_la_zona_de_la_clinica(): void
    {
        $cita = RelojClinica::deCita('2026-11-17 23:00:00');

        $this->assertTrue($cita->greaterThan(RelojClinica::ahora()));
        $this->assertSame('2026-11-17 23:00', $cita->format('Y-m-d H:i'));
        $this->assertTrue(RelojClinica::deCita('2026-11-17 21:00:00')->lessThan(RelojClinica::ahora()));
    }

    public function test_fecha_de_acepta_solo_una_fecha_valida(): void
    {
        $fecha = RelojClinica::fechaDe('2026-11-18');

        $this->assertInstanceOf(CarbonImmutable::class, $fecha);
        $this->assertSame('2026-11-18 00:00:00', $fecha->format('Y-m-d H:i:s'));
        $this->assertSame(self::ZONA, $fecha->getTimezone()->getName());

        foreach ([null, ['x'], 'ayer', '2026-02-30', '9999-12-31', '1999-12-31', '2026-11-18 10:00', 20261118, "2026-11-18\n"] as $valor) {
            $this->assertNull(RelojClinica::fechaDe($valor), var_export($valor, true));
        }
    }

    public function test_la_edad_cambia_el_dia_del_cumpleanos_en_la_clinica(): void
    {
        // La medianoche del 12/02/1950 no existió en esta zona (cambio de horario): /review R3.
        $this->relojEnLaClinica('2026-02-11 23:59:00');
        $this->assertSame(75, RelojClinica::edad('1950-02-12'));

        $this->relojEnLaClinica('2026-02-12 00:00:00');
        $this->assertSame(76, RelojClinica::edad('1950-02-12'));
    }

    public function test_la_zona_sale_de_la_configuracion(): void
    {
        config(['clinica.zona_horaria' => 'America/Bogota']);
        // 05:30 UTC del 18: en Bogotá (UTC−5) son las 00:30 del 18, en México aún las 23:30 del 17.
        Carbon::setTestNow(Carbon::parse('2026-11-18 05:30:00', 'UTC'));

        $this->assertSame('America/Bogota', RelojClinica::zona());
        $this->assertSame('2026-11-18', RelojClinica::fechaHoy());
        $this->assertSame('2026-11-18 00:30:00', RelojClinica::momentoLocal());
    }
}
