<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 050: los paneles, «Mis citas», la ficha del paciente y las cuentas del mes usan el día de la clínica
 * (America/Mexico_City), no el UTC de la aplicación. Reloj: 03:50 UTC del 18/11/2026, las 21:50 del martes 17
 * en la clínica. Citas de D1: el 17 a las 21:00 y a las 23:00 (Pablo) y el 18 a las 10:00 (Paula).
 */
class HoraClinicaPanelesTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const ZONA = 'America/Mexico_City';

    private string $cita21;

    private string $cita23;

    private string $cita18;

    protected function setUp(): void
    {
        parent::setUp();
        config(['clinica.zona_horaria' => self::ZONA]);
        $this->travelTo(Carbon::parse('2026-11-18 03:50:00', 'UTC'));
        $this->crearEscenario();

        $this->cita21 = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-17 21:00');
        $this->cita23 = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-17 23:00');
        $this->cita18 = $this->cita($this->d1, $this->p2, 'agendada', '2026-11-18 10:00');
    }

    private function relojEnLaClinica(string $momento): void
    {
        $this->travelTo(Carbon::parse($momento, self::ZONA));
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /** Valor de la tarjeta de cifras con esa etiqueta. */
    private function cifra(string $html, string $etiqueta): string
    {
        $nodo = $this->xpath($html)->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' stat-label ')][normalize-space()='{$etiqueta}']/preceding-sibling::*[contains(concat(' ', normalize-space(@class), ' '), ' stat-value ')][1]")->item(0);
        $this->assertNotNull($nodo, "La tarjeta «{$etiqueta}» está en la página.");

        return trim($nodo->textContent);
    }

    private function subtitulo(string $html): string
    {
        $nodo = $this->xpath($html)->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' welcome-sub ')]")->item(0);
        $this->assertNotNull($nodo, 'El panel tiene el subtítulo con la fecha.');

        return preg_replace('/\s+/u', ' ', trim($nodo->textContent));
    }

    /** Las citas de un panel del personal: las de hoy enlazan a su detalle (spec 048). */
    private function assertCitasDeHoy(string $html, string $rutaDetalle, array $presentes, array $ausentes, string $cuando): void
    {
        foreach ($presentes as $cita) {
            $this->assertStringContainsString('href="'.route($rutaDetalle, $cita).'"', $html, "{$cuando}: la cita está en la lista de hoy.");
        }
        foreach ($ausentes as $cita) {
            $this->assertStringNotContainsString('href="'.route($rutaDetalle, $cita).'"', $html, "{$cuando}: la cita no es de hoy.");
        }
    }

    // ── CA1: paneles del personal ─────────────────────────────────────────

    public function test_los_paneles_del_personal_muestran_el_dia_de_la_clinica(): void
    {
        $admin = $this->actingAs($this->admin)->get('/admin')->assertOk()->getContent();
        $this->assertCitasDeHoy($admin, 'admin.citas.show', [$this->cita21, $this->cita23], [$this->cita18], 'Administración');
        $this->assertSame('2', $this->cifra($admin, 'Citas hoy'), 'Administración: citas de hoy del 17.');
        $this->assertStringContainsString('17 Nov', $admin, 'Administración: la fecha de la tarjeta es el 17.');
        $this->assertStringNotContainsString('18 Nov', $admin);

        $dentista = $this->actingAs($this->dentista1)->get('/dentista')->assertOk()->getContent();
        $this->assertCitasDeHoy($dentista, 'dentista.citas.show', [$this->cita21, $this->cita23], [$this->cita18], 'Dentista');
        $this->assertSame('2', $this->cifra($dentista, 'Citas hoy'), 'Dentista: citas de hoy del 17.');
        $this->assertStringStartsWith('martes, 17 de noviembre · 2 citas programadas', $this->subtitulo($dentista));
        // La tarjeta de la agenda de hoy lleva la fecha de la clínica (/analyze A12).
        $this->assertStringContainsString('17/11/2026', $dentista);
        $this->assertStringNotContainsString('18/11/2026', $dentista);

        $asistente = $this->actingAs($this->asistente)->get('/asistente')->assertOk()->getContent();
        $this->assertCitasDeHoy($asistente, 'asistente.citas.show', [$this->cita21, $this->cita23], [$this->cita18], 'Asistente');
        $this->assertSame('2', $this->cifra($asistente, 'Citas hoy'), 'Asistente: citas de hoy del 17.');
        $this->assertStringStartsWith('martes, 17 de noviembre · 2 citas programadas', $this->subtitulo($asistente));
    }

    public function test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica(): void
    {
        $html = $this->actingAs($this->asistente)->get('/asistente/doctores')->assertOk()->getContent();
        $nodo = $this->xpath($html)->query("//span[normalize-space()='Citas hoy']/following-sibling::span[1]")->item(0);

        $this->assertNotNull($nodo, 'La tarjeta de la dentista lleva sus citas de hoy.');
        $this->assertSame('2', trim($nodo->textContent));
    }

    // ── CA2: el paciente ──────────────────────────────────────────────────

    public function test_el_paciente_ve_como_proxima_su_cita_de_esta_noche(): void
    {
        $panel = $this->actingAs($this->paciente1)->get('/cliente')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/23:00\s+hrs/', $panel, '«Tu próxima cita» es la de las 23:00.');
        $this->assertStringContainsString('class="cita-enlace" href="'.route('cliente.citas.show', $this->cita21).'"', $panel, 'La de las 21:00 está en el historial.');
        $this->assertStringNotContainsString('class="cita-enlace" href="'.route('cliente.citas.show', $this->cita23).'"', $panel, 'La de las 23:00 no está en el historial.');

        $proximas = $this->actingAs($this->paciente1)->get('/cliente/citas')->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('cliente.citas.show', $this->cita23).'"', $proximas, 'Próximas: la de las 23:00.');
        $this->assertStringNotContainsString('href="'.route('cliente.citas.show', $this->cita21).'"', $proximas, 'Próximas: no la de las 21:00.');

        $pasadas = $this->actingAs($this->paciente1)->get('/cliente/citas?filtro=pasadas')->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('cliente.citas.show', $this->cita21).'"', $pasadas, 'Pasadas: la de las 21:00.');
        $this->assertStringNotContainsString('href="'.route('cliente.citas.show', $this->cita23).'"', $pasadas, 'Pasadas: no la de las 23:00.');
    }

    // ── CA3: la ficha del paciente ────────────────────────────────────────

    public function test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche(): void
    {
        // Cumple 26 el 18: a las 21:50 del 17 en la clínica aún tiene 25 (/analyze A10).
        DB::table('pacientes')->where('id', $this->p1)->update(['fecha_nacimiento' => '2000-11-18']);

        $html = $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}")->assertOk()->getContent();

        $this->assertStringContainsString('Próxima cita', $html, 'La ficha tiene próxima cita.');
        $this->assertMatchesRegularExpression('/·\s*23:00/u', $html, 'La próxima es la de las 23:00 del 17.');
        $this->assertMatchesRegularExpression('/\b25 años/u', $html, 'La edad cambia el día del cumpleaños en la clínica.');
        $this->assertDoesNotMatchRegularExpression('/\b26 años/u', $html);
    }

    // ── CA6: cuentas del mes ──────────────────────────────────────────────

    public function test_las_cuentas_del_mes_usan_el_mes_de_la_clinica(): void
    {
        // 20:00 del 30 de noviembre en la clínica (02:00 UTC del 1 de diciembre).
        $this->cita($this->d1, $this->p1, 'agendada', '2026-11-30 10:00');
        $this->relojEnLaClinica('2026-11-30 20:00:00');

        $ficha = $this->actingAs($this->admin)->get("/admin/personal/{$this->d1}")->assertOk()->getContent();
        $this->assertSame('4', $this->cifra($ficha, 'Este mes'), 'Ficha de la dentista: las cuatro citas de noviembre.');

        // 20:00 del 31 de diciembre en la clínica (02:00 UTC del 1 de enero).
        $this->cita($this->d1, $this->p1, 'completada', '2026-12-31 10:00');
        $this->relojEnLaClinica('2026-12-31 20:00:00');

        $panel = $this->actingAs($this->dentista1)->get('/dentista')->assertOk()->getContent();
        $this->assertSame('1', $this->cifra($panel, 'Atendidos este mes'), 'Panel de la dentista: la completada de diciembre.');
    }

    // ── CA4: el calendario de «Citas» (spec 049; caracterización) ────────

    public function test_la_agenda_usa_el_dia_de_la_clinica(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/citas')->assertOk()->getContent());
        $dias = fn (string $consulta) => array_map(fn ($n) => $n->getAttribute('data-dia'), iterator_to_array($xpath->query($consulta)));
        $clase = fn (string $c) => "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";

        $semana = $dias("//a[{$clase('cal-dia-cab')}]");
        $this->assertSame('2026-11-16', $semana[0] ?? null, 'La semana empieza el lunes 16.');
        $this->assertContains('2026-11-21', $semana, 'La semana llega al sábado 21.');

        $this->assertSame(['2026-11-17'], $dias("//a[{$clase('cal-dia-cab--hoy')}]"), 'Cabecera: solo el 17 es hoy.');
        $this->assertSame(['2026-11-17'], $dias("//a[{$clase('tira-dia--hoy')}]"), 'Tira del móvil: solo el 17 es hoy.');
        $this->assertSame(['2026-11-17'], $dias("//section[{$clase('cal-lista-grupo')}][.//*[{$clase('cal-lista-hoy')}]]"), 'Lista: solo el grupo del 17 lleva «Hoy».');

        $hoy = $xpath->query("//a[{$clase('cal-control')}][normalize-space()='Hoy']")->item(0);
        $this->assertNotNull($hoy, 'La barra tiene «Hoy».');
        $this->assertStringContainsString('fecha=2026-11-16', $hoy->getAttribute('href'), '«Hoy» lleva a la semana del 17.');
    }

    // ── CA8: bordes del día ───────────────────────────────────────────────

    public function test_los_bordes_del_dia_de_la_clinica(): void
    {
        foreach (['2026-11-17 17:59:00', '2026-11-17 18:00:00', '2026-11-17 23:59:00'] as $momento) {
            $this->relojEnLaClinica($momento);
            $html = $this->actingAs($this->admin)->get('/admin')->assertOk()->getContent();
            $this->assertCitasDeHoy($html, 'admin.citas.show', [$this->cita21, $this->cita23], [$this->cita18], $momento);
        }

        $this->relojEnLaClinica('2026-11-18 00:00:00');
        $html = $this->actingAs($this->admin)->get('/admin')->assertOk()->getContent();
        $this->assertCitasDeHoy($html, 'admin.citas.show', [$this->cita18], [$this->cita21, $this->cita23], '00:00 del 18');
    }
}
