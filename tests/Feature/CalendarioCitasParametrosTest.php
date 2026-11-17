<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Parámetros de `GET /{rol}/citas` (spec 049, CA2, TM2): lo no válido o no escalar se ignora sin
 * error y se ve la semana actual (o el filtro no se aplica). Reloj: martes 17/11/2026, 11:20.
 */
class CalendarioCitasParametrosTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const SEMANA_ACTUAL = '16 – 21 de noviembre de 2026';

    private string $deD1;

    private string $deD2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-17 17:20:00', 'UTC'));
        $this->crearEscenario();

        $this->deD1 = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-18 10:00');
        $this->deD2 = $this->cita($this->d2, $this->p2, 'confirmada', '2026-11-19 12:00');
    }

    public function test_un_periodo_o_una_vista_no_validos_muestran_la_semana_actual(): void
    {
        $consultas = [
            'vista=dia', 'vista[]=mes', 'fecha=2026-13-45', 'fecha=ayer', 'fecha[]=2026-11-01',
            'fecha=2026-11-01T10:00', 'vista=semana&fecha=', 'dia[]=2026-11-18', 'fecha_desde[]=2026-11-01',
        ];

        foreach ($consultas as $consulta) {
            $x = $this->pagina("/admin/citas?{$consulta}");
            $this->assertSame(self::SEMANA_ACTUAL, $this->texto($x, "//*[@id='cal-titulo']"), $consulta);
            $this->assertSame('Citas de la semana', $this->texto($x, "//*[@id='lista-titulo']"), $consulta);
        }
    }

    public function test_un_dia_fuera_del_periodo_o_mal_escrito_no_se_elige(): void
    {
        foreach (['dia=2026-12-25', 'dia=mañana', 'vista=mes&fecha=2026-11-01&dia=2026-10-30'] as $consulta) {
            $x = $this->pagina("/admin/citas?{$consulta}");
            $this->assertStringStartsWith('Citas de', $this->texto($x, "//*[@id='lista-titulo']"), $consulta);
            $this->assertSame(0, $x->query("//*[{$this->clase('cal-dia-cab--elegido')}]")->length, $consulta);
        }
    }

    public function test_un_filtro_no_valido_no_se_aplica(): void
    {
        $consultas = [
            'dentista_id=no-es-un-uuid', 'dentista_id[]=x', 'estado=inventado', 'estado[]=agendada',
            'buscar='.str_repeat('a', 101), 'buscar[]=Pablo',
        ];

        foreach ($consultas as $consulta) {
            $x = $this->pagina("/admin/citas?{$consulta}");
            $citas = $x->query("//*[@id='lista']//a[{$this->clase('cal-lista-cita')}]")->length;
            $this->assertSame(2, $citas, "{$consulta}: se ven las dos citas de la semana");
        }
    }

    public function test_fecha_desde_abre_la_semana_de_esa_fecha_y_fecha_manda(): void
    {
        $this->assertSame('23 – 28 de noviembre de 2026', $this->texto($this->pagina('/admin/citas?fecha_desde=2026-11-25&fecha_hasta=2026-11-25'), "//*[@id='cal-titulo']"));
        $this->assertSame('30 de noviembre – 5 de diciembre de 2026', $this->texto($this->pagina('/admin/citas?fecha=2026-12-02&fecha_desde=2026-11-25'), "//*[@id='cal-titulo']"));
    }

    public function test_los_tres_grupos_aguantan_parametros_no_escalares(): void
    {
        $consulta = 'vista[]=x&fecha[]=x&dia[]=x&dentista_id[]=x&estado[]=x&buscar[]=x&fecha_desde[]=x';

        $this->pagina("/admin/citas?{$consulta}");
        $this->actingAs($this->dentista1)->get("/dentista/citas?{$consulta}")->assertOk()->assertSee(self::SEMANA_ACTUAL);
        $this->actingAs($this->asistente)->get("/asistente/citas?{$consulta}")->assertOk()->assertSee(self::SEMANA_ACTUAL);
    }

    // ── Utilidades ───────────────────────────────────────────────────────

    private function pagina(string $url): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$this->actingAs($this->admin)->get($url)->assertOk()->getContent());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function clase(string $clase): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' {$clase} ')";
    }

    private function texto(DOMXPath $x, string $consulta): string
    {
        $nodo = $x->query($consulta)->item(0);
        $this->assertNotNull($nodo, "No se encontró {$consulta}");

        return trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
    }
}
