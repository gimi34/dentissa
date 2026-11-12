<?php

namespace Tests\Feature\Reportes;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 047, CA1, CA2 y CA7 (TM2, TM3): el periodo de los reportes.
 *
 * Fechas de 2031, sin citas del volcado (plan D12). «Hoy» es el 15 de marzo de 2031 a las 12:00
 * de la clínica (18:00 UTC).
 */
class ReportesPeriodoTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2031-03-15 18:00:00');
        $this->crearEscenario();
        $this->assertSame(0, DB::table('citas')->whereBetween('fecha_hora', ['2031-01-01 00:00:00', '2032-01-31 23:59:59'])->count(),
            'El periodo de prueba no tiene citas antes de sembrar.');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function reportes(array $consulta = []): TestResponse
    {
        return $this->actingAs($this->admin)->get('/admin/reportes'.($consulta === [] ? '' : '?'.http_build_query($consulta)));
    }

    private function xpath(TestResponse $respuesta): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function texto(TestResponse $respuesta, string $id): ?string
    {
        $nodo = $this->xpath($respuesta)->query("//*[@id='{$id}']")->item(0);

        return $nodo === null ? null : trim(preg_replace('/\s+/', ' ', $nodo->textContent));
    }

    public function test_sin_fechas_se_muestra_el_mes_en_curso_de_la_clinica(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', '2031-03-10 10:00');
        $this->cita($this->d1, $this->p1, 'agendada', '2031-02-10 10:00');

        $respuesta = $this->reportes()->assertOk();

        $this->assertSame('Del 1 al 31 de marzo de 2031', $this->texto($respuesta, 'reporte-periodo'));
        $this->assertSame('1', $this->texto($respuesta, 'resumen-total-cifra'));

        // A las 03:00 UTC del 1 de abril todavía es 31 de marzo en la clínica (D4).
        Carbon::setTestNow('2031-04-01 03:00:00');
        $this->assertSame('Del 1 al 31 de marzo de 2031', $this->texto($this->reportes()->assertOk(), 'reporte-periodo'));
    }

    public function test_el_periodo_elegido_recalcula_los_reportes(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', '2031-03-10 10:00');
        $this->cita($this->d1, $this->p1, 'completada', '2031-02-10 10:00');
        $this->cita($this->d2, $this->p2, 'completada', '2031-02-11 10:00');

        $respuesta = $this->reportes(['desde' => '2031-02-01', 'hasta' => '2031-02-28'])->assertOk();

        $this->assertSame('Del 1 al 28 de febrero de 2031', $this->texto($respuesta, 'reporte-periodo'));
        $this->assertSame('2', $this->texto($respuesta, 'resumen-total-cifra'));
        $xpath = $this->xpath($respuesta);
        $this->assertSame('2031-02-01', $xpath->query('//input[@name="desde"]')->item(0)->getAttribute('value'));
        $this->assertSame('2031-02-28', $xpath->query('//input[@name="hasta"]')->item(0)->getAttribute('value'));
    }

    public function test_los_dos_dias_extremos_cuentan(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', '2031-05-01 00:00');
        $this->cita($this->d1, $this->p1, 'agendada', '2031-05-31 23:59');
        $this->cita($this->d1, $this->p1, 'agendada', '2031-04-30 23:59');
        $this->cita($this->d1, $this->p1, 'agendada', '2031-06-01 00:00');

        $respuesta = $this->reportes(['desde' => '2031-05-01', 'hasta' => '2031-05-31'])->assertOk();

        $this->assertSame('2', $this->texto($respuesta, 'resumen-total-cifra'));
    }

    public function test_un_periodo_de_un_solo_dia_es_valido(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', '2031-05-05 10:00');

        $respuesta = $this->reportes(['desde' => '2031-05-05', 'hasta' => '2031-05-05'])->assertOk();

        $this->assertSame('1', $this->texto($respuesta, 'resumen-total-cifra'));
    }

    public function test_los_atajos_enlazan_este_mes_mes_pasado_y_este_ano(): void
    {
        $xpath = $this->xpath($this->reportes()->assertOk());

        $atajos = [];
        foreach ($xpath->query('//*[@id="reporte-atajos"]//a') as $enlace) {
            $atajos[trim($enlace->textContent)] = [$enlace->getAttribute('href'), $enlace->getAttribute('aria-current')];
        }

        $this->assertSame([
            'Este mes' => ['/admin/reportes?desde=2031-03-01&hasta=2031-03-31', 'true'],
            'Mes pasado' => ['/admin/reportes?desde=2031-02-01&hasta=2031-02-28', ''],
            'Este año' => ['/admin/reportes?desde=2031-01-01&hasta=2031-12-31', ''],
        ], $atajos);

        // El atajo marcado es el del periodo en pantalla.
        $xpath = $this->xpath($this->reportes(['desde' => '2031-01-01', 'hasta' => '2031-12-31'])->assertOk());
        $marcados = $xpath->query('//*[@id="reporte-atajos"]//a[@aria-current="true"]');
        $this->assertSame(1, $marcados->length);
        $this->assertSame('Este año', trim($marcados->item(0)->textContent));
    }

    /** @return array<string, array{0: string, 1: mixed}> campo con el valor no válido */
    public static function fechasMalFormadas(): array
    {
        $valores = [
            'fecha que no existe' => '2031-02-30',
            'texto' => 'ayer',
            'otro formato' => '15/03/2031',
            'texto con SQL' => "2031-03-01' OR '1'='1",
            'lista' => ['2031-03-01'],
            'fecha con hora' => '2031-03-01 10:00',
        ];

        $casos = [];
        foreach ($valores as $nombre => $valor) {
            $casos["{$nombre} en desde"] = ['desde', $valor];
            $casos["{$nombre} en hasta"] = ['hasta', $valor];
        }

        return $casos;
    }

    #[DataProvider('fechasMalFormadas')]
    public function test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor(string $campo, mixed $valor): void
    {
        $consulta = ['desde' => '2031-03-01', 'hasta' => '2031-03-31'];
        $consulta[$campo] = $valor;

        // Venga de donde venga, la vuelta es a «Reportes» (D3), nunca un 500.
        $respuesta = $this->actingAs($this->admin)->from('/admin/citas')->get('/admin/reportes?'.http_build_query($consulta));

        $respuesta->assertStatus(302)->assertRedirect('/admin/reportes');
        $this->assertSame('Escribe la fecha como AAAA-MM-DD.', $this->errorDe($campo));
    }

    public function test_hasta_anterior_a_desde(): void
    {
        $this->reportes(['desde' => '2031-03-10', 'hasta' => '2031-03-09'])->assertRedirect('/admin/reportes');

        $this->assertSame('«Hasta» no puede ser anterior a «Desde».', $this->errorDe('hasta'));
    }

    public function test_falta_una_de_las_dos_fechas(): void
    {
        $this->reportes(['desde' => '2031-03-10'])->assertRedirect('/admin/reportes');
        $this->assertSame('Falta la fecha «Hasta».', $this->errorDe('hasta'));

        $this->reportes(['hasta' => '2031-03-10'])->assertRedirect('/admin/reportes');
        $this->assertSame('Falta la fecha «Desde».', $this->errorDe('desde'));

        // Un campo vacío del formulario cuenta como ausente.
        $this->reportes(['desde' => '', 'hasta' => '2031-03-10'])->assertRedirect('/admin/reportes');
        $this->assertSame('Falta la fecha «Desde».', $this->errorDe('desde'));
    }

    public function test_los_dos_campos_vacios_muestran_el_mes_en_curso(): void
    {
        $respuesta = $this->reportes(['desde' => '', 'hasta' => ''])->assertOk();

        $this->assertSame('Del 1 al 31 de marzo de 2031', $this->texto($respuesta, 'reporte-periodo'));
    }

    public function test_un_periodo_de_mas_de_366_dias_se_rechaza(): void
    {
        // Del 1 de enero de 2031 al 1 de enero de 2032 son 366 días contando los dos extremos.
        $this->reportes(['desde' => '2031-01-01', 'hasta' => '2032-01-01'])->assertOk();

        $this->reportes(['desde' => '2031-01-01', 'hasta' => '2032-01-02'])->assertRedirect('/admin/reportes');
        $this->assertSame('El periodo puede ser de 366 días como mucho.', $this->errorDe('hasta'));
    }
}
