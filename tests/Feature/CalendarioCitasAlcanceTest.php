<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Alcance del calendario de «Citas» por rol (spec 049, CA10, TM1). Caracterización: la dentista ve
 * sus citas y la asistente las de sus dentistas (D1), aunque cambien en la dirección el filtro de
 * dentista, el periodo o la vista, en el calendario, la lista, las cuentas por día y el resumen; y
 * una cita fuera de su alcance no les quita huecos. Reloj: martes 17/11/2026, 11:20 de la clínica.
 */
class CalendarioCitasAlcanceTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private string $propia;

    private string $ajena;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-17 17:20:00', 'UTC'));
        $this->crearEscenario();

        // La propia (D1) y la ajena (D2) el miércoles 18, a horas distintas.
        $this->propia = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-18 10:00');
        $this->ajena = $this->cita($this->d2, $this->p2, 'agendada', '2026-11-18 14:00');
    }

    public function test_la_dentista_y_la_asistente_solo_ven_sus_citas_aunque_cambien_la_direccion(): void
    {
        $urls = ['', "?dentista_id={$this->d2}", '?vista=mes&fecha=2026-11-02', "?vista=semana&fecha=2026-11-16&dia=2026-11-18&dentista_id={$this->d2}"];

        foreach (['dentista' => $this->dentista1, 'asistente' => $this->asistente] as $rol => $usuario) {
            foreach ($urls as $consulta) {
                $url = "/{$rol}/citas{$consulta}";
                $respuesta = $this->actingAs($usuario)->get($url)->assertOk();
                $x = $this->xpath($respuesta->getContent());
                $ajena = route("{$rol}.citas.show", $this->ajena);

                $this->assertSame(0, $x->query("//a[@href='{$ajena}']")->length, "{$url}: la cita ajena no aparece");
                $this->assertNotContains($this->ajena, $respuesta->viewData('citas')->pluck('id')->all(), $url);
                // El filtro de dentista se suma al alcance y nunca lo amplía: con una dentista ajena, nada.
                $total = str_contains($consulta, 'dentista_id') ? '0' : '1';
                $this->assertSame($total, $this->cifra($x, 'Total'), "{$url}: el resumen solo cuenta lo que puede ver");

                if (! str_contains($consulta, 'dentista_id')) {
                    $propia = route("{$rol}.citas.show", $this->propia);
                    $this->assertGreaterThan(0, $x->query("//a[@href='{$propia}']")->length, "{$url}: su cita sí");
                }
            }

            // Las cuentas por día (tira y mes) solo cuentan la suya.
            $semana = $this->xpath($this->actingAs($usuario)->get("/{$rol}/citas")->getContent());
            $this->assertSame('1 cita', $this->texto($semana, "//a[@data-dia='2026-11-18'][{$this->clase('tira-dia')}]//*[{$this->clase('tira-total')}]"));
            $mes = $this->xpath($this->actingAs($usuario)->get("/{$rol}/citas?vista=mes&fecha=2026-11-17")->getContent());
            $this->assertStringContainsString('1 cita', $this->texto($mes, "//a[{$this->clase('mes-dia')}][@data-dia='2026-11-18']"));

            // A28: la cita de D2 a las 14:00 no les quita ese hueco.
            $huecos = array_map(
                fn (DOMElement $a) => $a->getAttribute('href'),
                iterator_to_array($semana->query("//*[{$this->clase('cal-dia')}][@data-dia='2026-11-18']//a[{$this->clase('cal-hueco')}]")),
            );
            $this->assertNotEmpty(array_filter($huecos, fn ($href) => str_contains($href, 'hora=14%3A00') || str_contains($href, 'hora=14:00')), "{$rol}: hueco a las 14:00");
        }
    }

    /**
     * R7 (TM1): con el buscador o con una dentista fuera de su alcance, la ocupación de los huecos sale
     * de su propia consulta, que también lleva el alcance: la cita de D2 a las 14:00 no le quita a la
     * asistente (que solo lleva a D1) ese hueco.
     */
    public function test_la_ocupacion_de_los_huecos_respeta_el_alcance_con_buscador_o_dentista_ajena(): void
    {
        foreach (['?buscar=Nadie', "?dentista_id={$this->d2}", '?buscar=Pablo'] as $consulta) {
            $x = $this->xpath($this->actingAs($this->asistente)->get("/asistente/citas{$consulta}")->assertOk()->getContent());
            $horas = array_map(function (DOMElement $a) {
                parse_str((string) parse_url($a->getAttribute('href'), PHP_URL_QUERY), $parametros);

                return $parametros['hora'] ?? '';
            }, iterator_to_array($x->query("//*[{$this->clase('cal-dia')}][@data-dia='2026-11-18']//a[{$this->clase('cal-hueco')}]")));

            $this->assertContains('14:00', $horas, "{$consulta}: la cita de D2 no ocupa el tramo de la asistente");
            $this->assertNotContains('10:00', $horas, "{$consulta}: su propia cita sí lo ocupa");
        }
    }

    public function test_la_administracion_ve_todas_las_citas(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/admin/citas')->assertOk();
        $ids = $respuesta->viewData('citas')->pluck('id')->all();

        $this->assertContains($this->propia, $ids);
        $this->assertContains($this->ajena, $ids);
        $this->assertSame('2', $this->cifra($this->xpath($respuesta->getContent()), 'Total'));
    }

    public function test_cada_grupo_de_citas_responde_403_a_los_roles_ajenos(): void
    {
        $casos = [
            [$this->paciente1, ['/admin/citas', '/dentista/citas', '/asistente/citas']],
            [$this->dentista1, ['/admin/citas', '/asistente/citas']],
            [$this->asistente, ['/admin/citas', '/dentista/citas']],
        ];

        foreach ($casos as [$usuario, $urls]) {
            foreach ($urls as $url) {
                $this->actingAs($usuario)->get($url)->assertForbidden();
                $this->actingAs($usuario)->get("{$url}?vista=mes&fecha=2026-11-01")->assertForbidden();
            }
        }
    }

    // ── Utilidades ───────────────────────────────────────────────────────

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
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

    private function cifra(DOMXPath $x, string $etiqueta): string
    {
        return $this->texto($x, "//*[{$this->clase('cal-resumen')}]//*[{$this->clase('stat-card')}][.//*[{$this->clase('stat-label')}][normalize-space(.)='{$etiqueta}']]//*[{$this->clase('stat-value')}]");
    }
}
