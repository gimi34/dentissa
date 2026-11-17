<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Calendario de «Citas» del personal (spec 049): semana por horas, mes, lista del periodo, huecos que
 * llevan a «Nueva cita» y el alta con parámetros manipulados. El reloj se fija el martes 17/11/2026 a
 * las 11:20 de la clínica (17:20 UTC), como en los candidatos del acta. Lo que solo se ve en el
 * navegador (pulsar, toque, desbordes) lo mide `docs/specs/049-calendario-citas/design/medir.mjs`.
 */
class CalendarioCitasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    /** Pablo Paciente con Diego Primero, martes 17 a las 10:00 (ya pasó). */
    private string $c17;

    /** Pablo con Diego, miércoles 18 a las 12:00, confirmada. */
    private string $c18a;

    /** Paula Otra con Dora Segunda, miércoles 18 a las 12:00: coincide con la anterior. */
    private string $c18b;

    /** Paula con Dora, jueves 19 a las 9:00, cancelada. */
    private string $c19;

    /** Pablo con Diego, el martes de la semana siguiente. */
    private string $cSiguiente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-17 17:20:00', 'UTC'));
        $this->crearEscenario();

        $this->c17 = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-17 10:00');
        $this->c18a = $this->cita($this->d1, $this->p1, 'confirmada', '2026-11-18 12:00');
        $this->c18b = $this->cita($this->d2, $this->p2, 'agendada', '2026-11-18 12:00');
        $this->c19 = $this->cita($this->d2, $this->p2, 'cancelada', '2026-11-19 09:00');
        $this->cSiguiente = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-24 10:00');
    }

    // ── T012 · CA1: la semana por horas ──────────────────────────────────

    public function test_la_semana_muestra_cada_cita_en_su_dia_y_hora(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');

        $this->assertSame('16 – 21 de noviembre de 2026', $this->texto($this->uno($x, "//*[@id='cal-titulo']")));
        $this->assertSame(
            ['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20', '2026-11-21'],
            $this->atributos($x->query("//*[{$this->clase('cal-dia')}]"), 'data-dia'),
            'De lunes a sábado',
        );

        $cita = $this->citaEnCalendario($x, '2026-11-17', $this->c17, 'admin');
        $this->assertContains('cal-cita--agendada', $this->clases($cita), 'El color va por el estado');
        $this->assertContains('cal-r3', $this->clases($cita), 'Las 10:00 son el tercer tramo');
        $this->assertContains('cal-s1', $this->clases($cita), '30 minutos ocupan un tramo');
        $texto = $this->texto($cita);
        foreach (['10:00', 'Pablo Paciente', 'Primero', 'Agendada'] as $parte) {
            $this->assertStringContainsString($parte, $texto);
        }

        $confirmada = $this->citaEnCalendario($x, '2026-11-18', $this->c18a, 'admin');
        $this->assertContains('cal-cita--confirmada', $this->clases($confirmada));
        $this->assertStringContainsString('Confirmada', $this->texto($confirmada));

        $this->assertSame(0, $x->query("//a[@href='".route('admin.citas.show', $this->cSiguiente)."']")->length, 'Solo las citas de la semana');
        $horas = array_map(fn (DOMNode $n) => $this->texto($n), iterator_to_array($x->query("//*[{$this->clase('cal-hora')}]")));
        $this->assertSame('9:00', $horas[0]);
        $this->assertSame('19:00', end($horas));
    }

    // ── T012 · CA2: navegación y vista en la dirección ──────────────────

    public function test_la_navegacion_lleva_al_periodo_y_la_vista(): void
    {
        $filtros = ['buscar' => 'Pablo', 'dentista_id' => $this->d1, 'estado' => 'agendada'];
        $x = $this->pagina($this->admin, '/admin/citas?'.http_build_query($filtros));

        foreach (['‹ Anterior' => '2026-11-09', 'Hoy' => '2026-11-16', 'Siguiente ›' => '2026-11-23'] as $texto => $fecha) {
            $enlace = $this->enlaceDeBarra($x, $texto);
            $this->assertSame(['vista' => 'semana', 'fecha' => $fecha] + $filtros, $this->parametros($enlace), $texto);
        }

        $semana = $this->enlaceDeBarra($x, 'Semana');
        $this->assertSame('page', $semana->getAttribute('aria-current'));
        $mes = $this->enlaceDeBarra($x, 'Mes');
        $this->assertFalse($mes->hasAttribute('aria-current'));
        $this->assertSame(['vista' => 'mes', 'fecha' => '2026-11-01'] + $filtros, $this->parametros($mes));

        // La página que piden esos enlaces es la del periodo y la vista.
        $siguiente = $this->pagina($this->admin, $this->enlaceDeBarra($x, 'Siguiente ›')->getAttribute('href'));
        $this->assertSame('23 – 28 de noviembre de 2026', $this->texto($this->uno($siguiente, "//*[@id='cal-titulo']")));

        $delMes = $this->pagina($this->admin, $mes->getAttribute('href'));
        $this->assertSame('Noviembre de 2026', $this->texto($this->uno($delMes, "//*[@id='cal-titulo']")));
        $this->assertSame('page', $this->enlaceDeBarra($delMes, 'Mes')->getAttribute('aria-current'));
        $this->assertSame(1, $delMes->query("//*[{$this->clase('cal-mes')}]")->length, 'La vista del mes');
        $this->assertSame(0, $delMes->query("//*[{$this->clase('cal-semana')}]")->length);
    }

    // ── T013 · CA3: el mes ──────────────────────────────────────────────

    public function test_el_mes_cuenta_las_citas_y_filtra_por_dia(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas?vista=mes&fecha=2026-11-17');

        foreach (['2026-11-17' => '1 cita', '2026-11-18' => '2 citas', '2026-11-24' => '1 cita', '2026-11-20' => '0 citas'] as $dia => $cuenta) {
            $celda = $this->uno($x, "//a[{$this->clase('mes-dia')}][@data-dia='{$dia}']");
            $this->assertStringContainsString($cuenta, $this->texto($celda), $dia);
        }
        $dia18 = $this->uno($x, "//a[{$this->clase('mes-dia')}][@data-dia='2026-11-18']");
        $this->assertSame(['vista' => 'mes', 'fecha' => '2026-11-01', 'dia' => '2026-11-18'], $this->parametros($dia18));
        $this->assertSame(0, $x->query("//a[{$this->clase('mes-dia')}][@data-dia='2026-10-26']")->length, 'Los días de otro mes no son enlaces');

        // Pulsar un día deja en la lista solo sus citas, con la forma de volver a todo el mes.
        $soloEse = $this->pagina($this->admin, $dia18->getAttribute('href'));
        $this->assertSame(['2026-11-18'], $this->atributos($soloEse->query("//*[@id='lista']//*[{$this->clase('cal-lista-grupo')}]"), 'data-dia'));
        $volver = $this->uno($soloEse, "//*[@id='lista']//a[normalize-space(.)='Ver todo el mes']");
        $this->assertSame(['vista' => 'mes', 'fecha' => '2026-11-01'], $this->parametros($volver));
    }

    // ── T013 · CA4: la lista del periodo ────────────────────────────────

    public function test_la_lista_agrupa_el_periodo_y_el_dia_elegido(): void
    {
        $tarde = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-18 16:00');
        $x = $this->pagina($this->admin, '/admin/citas');

        $this->assertSame('Citas de la semana', $this->texto($this->uno($x, "//*[@id='lista-titulo']")));
        $this->assertSame(
            ['2026-11-17', '2026-11-18', '2026-11-19'],
            $this->atributos($x->query("//*[@id='lista']//*[{$this->clase('cal-lista-grupo')}]"), 'data-dia'),
            'Un grupo por día con citas, en orden',
        );
        $this->assertSame(
            [$this->c18a, $this->c18b, $tarde],
            $this->idsDeCitas($x->query("//*[@id='lista']//*[@data-dia='2026-11-18']//a[{$this->clase('cal-lista-cita')}]")),
            'En orden de hora',
        );
        $fila = $this->uno($x, "//*[@id='lista']//a[@href='".route('admin.citas.show', $this->c19)."']");
        foreach (['9:00', 'Paula Otra', 'Dora Segunda', 'Cancelada'] as $parte) {
            $this->assertStringContainsString($parte, $this->texto($fila));
        }

        // Con un día elegido, solo ese día y «Ver toda la semana».
        $dia = $this->pagina($this->admin, '/admin/citas?vista=semana&fecha=2026-11-16&dia=2026-11-18');
        $this->assertSame(['2026-11-18'], $this->atributos($dia->query("//*[@id='lista']//*[{$this->clase('cal-lista-grupo')}]"), 'data-dia'));
        $this->assertSame('Citas del miércoles 18 de noviembre', $this->texto($this->uno($dia, "//*[@id='lista-titulo']")));
        $volver = $this->uno($dia, "//*[@id='lista']//a[normalize-space(.)='Ver toda la semana']");
        $this->assertSame(['vista' => 'semana', 'fecha' => '2026-11-16'], $this->parametros($volver));
        $this->assertContains('cal-dia-cab--elegido', $this->clases($this->uno($dia, "//a[{$this->clase('cal-dia-cab')}][@data-dia='2026-11-18']")));
    }

    public function test_los_filtros_se_aplican_al_calendario_y_a_la_lista(): void
    {
        $casos = [
            'buscar=Paula' => [$this->c18b, $this->c19],
            "dentista_id={$this->d1}" => [$this->c17, $this->c18a],
            'estado=confirmada' => [$this->c18a],
        ];

        foreach ($casos as $filtro => $esperadas) {
            $x = $this->pagina($this->admin, "/admin/citas?{$filtro}");
            $this->assertSame($esperadas, $this->idsDeCitas($x->query("//a[{$this->clase('cal-cita')}]")), "Calendario con {$filtro}");
            $this->assertSame($esperadas, $this->idsDeCitas($x->query("//*[@id='lista']//a[{$this->clase('cal-lista-cita')}]")), "Lista con {$filtro}");
        }
    }

    public function test_el_filtro_conserva_el_periodo_y_limpiar_tambien(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas?vista=semana&fecha=2026-11-23&dia=2026-11-24&buscar=Pablo');
        $formulario = $this->uno($x, "//form[{$this->clase('filtro-citas')}]");

        $ocultos = [];
        foreach ($x->query(".//input[@type='hidden']", $formulario) as $campo) {
            $ocultos[$campo->getAttribute('name')] = $campo->getAttribute('value');
        }
        $this->assertSame(['vista' => 'semana', 'fecha' => '2026-11-23', 'dia' => '2026-11-24'], array_intersect_key($ocultos, array_flip(['vista', 'fecha', 'dia'])));
        $this->assertSame('Pablo', $this->uno($x, ".//input[@name='buscar']", $formulario)->getAttribute('value'));
        $this->assertSame(0, $x->query(".//input[@name='fecha_desde' or @name='fecha_hasta']", $formulario)->length, 'Sin «Desde» ni «Hasta»');

        $limpiar = $this->uno($x, ".//a[normalize-space(.)='Limpiar']", $formulario);
        $this->assertSame(['vista' => 'semana', 'fecha' => '2026-11-23'], $this->parametros($limpiar));
    }

    // ── T013 · CA9: periodo vacío ───────────────────────────────────────

    public function test_un_periodo_sin_citas_lo_dice(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas?fecha=2026-12-07');

        $this->assertSame(0, $x->query("//a[{$this->clase('cal-cita')}]")->length);
        $this->assertStringContainsString('No hay citas en esta semana', $this->texto($this->uno($x, "//*[@id='lista']")));
        $agendar = $this->uno($x, "//*[@id='lista']//a[normalize-space(.)='Agendar una cita']");
        $this->assertSame(route('admin.citas.create'), $agendar->getAttribute('href'));

        $mes = $this->pagina($this->admin, '/admin/citas?vista=mes&fecha=2027-02-10');
        $this->assertStringContainsString('No hay citas en este mes', $this->texto($this->uno($mes, "//*[@id='lista']")));

        $buscado = $this->pagina($this->admin, '/admin/citas?buscar=Nadie+Existe');
        $this->assertStringContainsString('No hay citas en esta semana', $this->texto($this->uno($buscado, "//*[@id='lista']")));
    }

    // ── T013 · D5: el resumen es del periodo ────────────────────────────

    public function test_el_resumen_de_cifras_es_del_periodo_o_del_dia_y_su_titulo_lo_dice(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');
        $this->assertSame('Resumen de la semana del 16 al 21 de noviembre', $this->texto($this->uno($x, "//*[@id='resumen-titulo']")));
        $this->assertSame('4', $this->cifra($x, 'Total'));
        $this->assertSame('1', $this->cifra($x, 'Canceladas'));

        $dia = $this->pagina($this->admin, '/admin/citas?vista=semana&fecha=2026-11-16&dia=2026-11-18');
        $this->assertSame('Resumen del miércoles 18 de noviembre', $this->texto($this->uno($dia, "//*[@id='resumen-titulo']")));
        $this->assertSame('2', $this->cifra($dia, 'Total'));

        $mes = $this->pagina($this->admin, '/admin/citas?vista=mes&fecha=2026-11-17');
        $this->assertSame('Resumen de noviembre de 2026', $this->texto($this->uno($mes, "//*[@id='resumen-titulo']")));
        $this->assertSame('5', $this->cifra($mes, 'Total'));

        // El filtro de estado no cambia las cifras (como hoy): cuentan todos los estados del periodo.
        $this->assertSame('4', $this->cifra($this->pagina($this->admin, '/admin/citas?estado=confirmada'), 'Total'));
    }

    // ── T014 · CA5: cada cita abre su detalle ───────────────────────────

    public function test_cada_cita_abre_su_detalle_con_un_enlace(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');
        $href = route('admin.citas.show', $this->c18a);
        $nombre = '12:00, Pablo Paciente, Diego Primero, Confirmada';

        $enCalendario = $x->query("//*[@id='calendario']//a[@href='{$href}']");
        $this->assertSame(1, $enCalendario->length, 'Un solo enlace por cita en el calendario');
        $this->assertContains('cal-cita', $this->clases($enCalendario->item(0)), 'El bloque entero es el enlace');
        $this->assertSame($nombre, $enCalendario->item(0)->getAttribute('aria-label'));
        $this->assertSame(0, $x->query('.//a', $enCalendario->item(0))->length, 'Sin enlaces dentro');

        $enLista = $x->query("//*[@id='lista']//a[@href='{$href}']");
        $this->assertSame(1, $enLista->length, 'Un solo enlace por cita en la lista');
        $this->assertContains('cal-lista-cita', $this->clases($enLista->item(0)), 'La fila entera es el enlace');
        $this->assertSame($nombre, $enLista->item(0)->getAttribute('aria-label'));

        // Cada cita del calendario y de la lista tiene su nombre de cuatro partes.
        foreach ($x->query("//a[{$this->clase('cal-cita')} or {$this->clase('cal-lista-cita')}]") as $enlace) {
            $this->assertMatchesRegularExpression('/^\d{1,2}:\d{2}, .+, .+, .+$/u', $enlace->getAttribute('aria-label'));
        }
    }

    // ── T014 · CA6: coincidencias y atenuadas ───────────────────────────

    public function test_las_citas_que_coinciden_van_en_carriles(): void
    {
        $noAsistio = $this->cita($this->d1, $this->p1, 'no_asistio', '2026-11-16 09:00');
        $x = $this->pagina($this->admin, '/admin/citas');

        $this->assertContains('cal-carril-1', $this->clases($this->citaEnCalendario($x, '2026-11-18', $this->c18a, 'admin')));
        $this->assertContains('cal-carril-2', $this->clases($this->citaEnCalendario($x, '2026-11-18', $this->c18b, 'admin')));
        $this->assertContains('cal-col--carriles-2', $this->clases($this->columna($x, '2026-11-18')), 'El día se ensancha');
        $this->assertNotContains('cal-col--carriles-2', $this->clases($this->columna($x, '2026-11-17')));

        $cancelada = $this->citaEnCalendario($x, '2026-11-19', $this->c19, 'admin');
        $this->assertContains('cal-cita--cancelada', $this->clases($cancelada));
        $this->assertStringContainsString('Cancelada', $this->texto($cancelada));
        $ausente = $this->citaEnCalendario($x, '2026-11-16', $noAsistio, 'admin');
        $this->assertContains('cal-cita--no_asistio', $this->clases($ausente));
        $this->assertStringContainsString('No asistió', $this->texto($ausente));
    }

    // ── T014 · CA7: huecos ──────────────────────────────────────────────

    public function test_los_huecos_llevan_a_nueva_cita(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');

        $primero = $this->uno($x, "//*[{$this->clase('cal-dia')}][@data-dia='2026-11-17']//a[{$this->clase('cal-hueco')}]");
        $this->assertSame(route('admin.citas.create', ['fecha' => '2026-11-17', 'hora' => '11:30']), $primero->getAttribute('href'), 'Desde el tramo siguiente');
        $this->assertSame('Agendar el martes 17 a las 11:30', $primero->getAttribute('aria-label'));
        $this->assertSame(0, $this->huecos($x, '2026-11-16')->length, 'El lunes ya pasó');

        $miercoles = $this->horasDeHuecos($x, '2026-11-18');
        $this->assertSame('09:00', $miercoles[0], 'La hora lleva cero');
        $this->assertNotContains('12:00', $miercoles, 'Ocupado por citas vigentes');
        $this->assertContains('09:00', $this->horasDeHuecos($x, '2026-11-19'), 'La cancelada no ocupa el tramo');
        $this->assertSame(0, $this->pagina($this->admin, '/admin/citas?vista=mes')->query("//a[{$this->clase('cal-hueco')}]")->length, 'El mes no tiene huecos');

        // Con una sola dentista, el hueco la lleva.
        foreach ([[$this->admin, "/admin/citas?dentista_id={$this->d1}", 'admin'], [$this->dentista1, '/dentista/citas', 'dentista']] as [$usuario, $url, $rol]) {
            $pagina = $this->pagina($usuario, $url);
            $hueco = $this->uno($pagina, "//*[{$this->clase('cal-dia')}][@data-dia='2026-11-18']//a[{$this->clase('cal-hueco')}]");
            $this->assertSame(
                route("{$rol}.citas.create", ['fecha' => '2026-11-18', 'hora' => '09:00', 'dentista' => $this->d1]),
                $hueco->getAttribute('href'),
                $url,
            );
            $this->assertNotContains('12:00', $this->horasDeHuecos($pagina, '2026-11-18'), "{$url}: su cita ocupa el tramo");
        }
    }

    public function test_el_buscador_y_el_estado_no_ofrecen_huecos_sobre_citas_vigentes_ocultas(): void
    {
        // A28: los filtros ocultan las citas de las 12:00 del miércoles, pero siguen ocupando el tramo.
        foreach (['estado=cancelada', 'buscar=Nadie', "dentista_id={$this->d1}&buscar=Paula"] as $filtro) {
            $x = $this->pagina($this->admin, "/admin/citas?{$filtro}");
            $this->assertSame(0, $x->query("//*[{$this->clase('cal-dia')}][@data-dia='2026-11-18']//a[{$this->clase('cal-cita')}][contains(@aria-label, '12:00')]")->length, "{$filtro}: la cita no se ve");
            $this->assertNotContains('12:00', $this->horasDeHuecos($x, '2026-11-18'), "{$filtro}: sin hueco sobre la cita oculta");
            $this->assertContains('12:30', $this->horasDeHuecos($x, '2026-11-18'), "{$filtro}: el tramo libre de al lado sí");
        }
    }

    // ── T014 · CA8: la tira y el día del móvil ──────────────────────────

    public function test_la_tira_tiene_los_dias_con_sus_cuentas(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');
        $dias = $x->query("//ol[{$this->clase('cal-tira')}]//a[{$this->clase('tira-dia')}]");

        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20', '2026-11-21'], $this->atributos($dias, 'data-dia'));
        $cuentas = array_map(fn (DOMElement $a) => $this->texto($this->uno($x, ".//*[{$this->clase('tira-total')}]", $a)), iterator_to_array($dias));
        $this->assertSame(['0 citas', '1 cita', '2 citas', '1 cita', '0 citas', '0 citas'], $cuentas);
        $this->assertSame(['2026-11-17'], $this->atributos($x->query("//ol[{$this->clase('cal-tira')}]//a[@aria-current='date']"), 'data-dia'), 'Hoy, por defecto');
        $this->assertSame(['vista' => 'semana', 'fecha' => '2026-11-16', 'dia' => '2026-11-18'], $this->parametros($dias->item(2)));

        $elegido = $this->pagina($this->admin, '/admin/citas?vista=semana&fecha=2026-11-16&dia=2026-11-18');
        $this->assertSame(['2026-11-18'], $this->atributos($elegido->query("//ol[{$this->clase('cal-tira')}]//a[@aria-current='date']"), 'data-dia'));
    }

    public function test_el_dia_del_movil_es_el_elegido_hoy_o_el_primero_y_su_titulo_lo_nombra(): void
    {
        $casos = [
            '/admin/citas' => ['2026-11-17', 'Citas de hoy, martes 17'],
            '/admin/citas?vista=semana&fecha=2026-11-16&dia=2026-11-18' => ['2026-11-18', 'Citas del miércoles 18'],
            '/admin/citas?fecha=2026-11-25' => ['2026-11-23', 'Citas del lunes 23'],
            '/admin/citas?vista=mes&fecha=2026-12-01' => ['2026-12-01', 'Citas del martes 1'],
        ];

        foreach ($casos as $url => [$dia, $titulo]) {
            $x = $this->pagina($this->admin, $url);
            $this->assertSame($dia, $this->uno($x, "//*[@id='lista']")->getAttribute('data-dia-movil'), $url);
            $this->assertSame($titulo, $this->texto($this->uno($x, "//*[@id='lista']//*[{$this->clase('cal-lista-titulo-movil')}]")), $url);
        }
    }

    // ── T014 · Sin JavaScript nuevo ni estilos en línea (A7) ────────────

    public function test_la_pagina_no_lleva_scripts_nuevos_ni_estilos_en_linea(): void
    {
        $x = $this->pagina($this->admin, '/admin/citas');

        foreach ($x->query('//script') as $script) {
            $this->assertMatchesRegularExpression('#/js/(menu|filtros)\.js\?v=\d+$#', $script->getAttribute('src'), 'Solo menu.js y filtros.js');
        }
        $this->assertSame(0, $x->query("//*[{$this->clase('content')}]//*[@style]")->length, 'Ningún style="" en la página');
    }

    // ── T014 · CA10: el alta con parámetros manipulados ─────────────────

    public function test_el_alta_ignora_parametros_no_validos(): void
    {
        $this->conHorarios();

        $x = $this->pagina($this->admin, '/admin/citas/crear?fecha=2026-11-18&hora=09:00&dentista='.$this->d1);
        $this->assertSame('2026-11-18', $this->uno($x, "//input[@name='fecha']")->getAttribute('value'));
        $this->assertSame('09:00', $this->uno($x, "//input[@name='hora']")->getAttribute('value'));
        $this->assertSame($this->d1, $this->uno($x, "//select[@name='dentista_id']/option[@selected]")->getAttribute('value'));

        $this->actingAs($this->admin)->get('/admin/citas/crear?fecha=ayer&hora=25:99&dentista=nadie')->assertOk();

        // El alta valida como hoy: una hora fuera del horario de la dentista no se guarda.
        $antes = DB::table('citas')->count();
        $this->actingAs($this->admin)->post('/admin/citas', [
            'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'fecha' => '2026-11-18', 'hora' => '19:00', 'duracion_min' => 30,
        ])->assertSessionHasErrors('hora');
        $this->assertSame($antes, DB::table('citas')->count());
    }

    public function test_nueva_cita_responde_con_parametros_no_escalares(): void
    {
        foreach (['fecha', 'hora', 'dentista', 'paciente_id'] as $parametro) {
            $this->actingAs($this->admin)->get("/admin/citas/crear?{$parametro}[]=x")->assertOk();
        }
    }

    // ── T018 · Tras agendar se vuelve a la semana y al día de la cita ───

    public function test_tras_agendar_se_vuelve_a_la_semana_y_al_dia_de_la_cita(): void
    {
        $this->conHorarios();

        $respuesta = $this->actingAs($this->admin)->post('/admin/citas', [
            'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'fecha' => '2026-11-26', 'hora' => '09:00', 'duracion_min' => 30,
        ]);

        $respuesta->assertRedirect(route('admin.citas', ['vista' => 'semana', 'fecha' => '2026-11-26', 'dia' => '2026-11-26']));
        $this->assertSame('Cita agendada correctamente.', session('success'));
    }

    // ── Utilidades ───────────────────────────────────────────────────────

    private function pagina(Usuario $usuario, string $url): DOMXPath
    {
        return $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /** Condición XPath: el elemento tiene la clase. */
    private function clase(string $clase): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' {$clase} ')";
    }

    private function uno(DOMXPath $x, string $consulta, ?DOMNode $contexto = null): DOMElement
    {
        $nodo = $x->query($consulta, $contexto)->item(0);
        $this->assertInstanceOf(DOMElement::class, $nodo, "No se encontró {$consulta}");

        return $nodo;
    }

    private function citaEnCalendario(DOMXPath $x, string $dia, string $cita, string $rol): DOMElement
    {
        $href = route("{$rol}.citas.show", $cita);

        return $this->uno($x, "//*[{$this->clase('cal-dia')}][@data-dia='{$dia}']//a[{$this->clase('cal-cita')}][@href='{$href}']");
    }

    private function enlaceDeBarra(DOMXPath $x, string $texto): DOMElement
    {
        return $this->uno($x, "//*[{$this->clase('cal-barra')}]//a[normalize-space(.)='{$texto}']");
    }

    /** @return list<string> */
    private function clases(DOMElement $nodo): array
    {
        return preg_split('/\s+/', trim($nodo->getAttribute('class')));
    }

    private function texto(DOMNode $nodo): string
    {
        return trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
    }

    /** @return list<string> */
    private function atributos(DOMNodeList $nodos, string $atributo): array
    {
        return array_map(fn (DOMElement $n) => $n->getAttribute($atributo), iterator_to_array($nodos));
    }

    /** Parámetros de la dirección de un enlace, en el orden de `vista`, `fecha`, `dia` y los filtros. */
    private function parametros(DOMElement $enlace): array
    {
        parse_str((string) parse_url($enlace->getAttribute('href'), PHP_URL_QUERY), $parametros);
        $orden = ['vista', 'fecha', 'dia', 'buscar', 'dentista_id', 'estado'];
        uksort($parametros, fn ($a, $b) => array_search($a, $orden, true) <=> array_search($b, $orden, true));

        return $parametros;
    }

    /** @return list<string> ids de las citas que enlazan esos nodos (por el final de su `href`) */
    private function idsDeCitas(DOMNodeList $enlaces): array
    {
        return array_map(fn (DOMElement $a) => basename((string) parse_url($a->getAttribute('href'), PHP_URL_PATH)), iterator_to_array($enlaces));
    }

    /** Cifra de una tarjeta del resumen («Total», «Agendadas»…). */
    private function cifra(DOMXPath $x, string $etiqueta): string
    {
        return $this->texto($this->uno($x, "//*[{$this->clase('cal-resumen')}]//*[{$this->clase('stat-card')}][.//*[{$this->clase('stat-label')}][normalize-space(.)='{$etiqueta}']]//*[{$this->clase('stat-value')}]"));
    }

    private function columna(DOMXPath $x, string $dia): DOMElement
    {
        return $this->uno($x, "//*[{$this->clase('cal-col')}][.//*[{$this->clase('cal-dia')}][@data-dia='{$dia}']]");
    }

    private function huecos(DOMXPath $x, string $dia): DOMNodeList
    {
        return $x->query("//*[{$this->clase('cal-dia')}][@data-dia='{$dia}']//a[{$this->clase('cal-hueco')}]");
    }

    /** @return list<string> horas de los huecos de un día, en el orden de la página */
    private function horasDeHuecos(DOMXPath $x, string $dia): array
    {
        return array_map(function (DOMElement $a) {
            parse_str((string) parse_url($a->getAttribute('href'), PHP_URL_QUERY), $parametros);

            return $parametros['hora'] ?? '';
        }, iterator_to_array($this->huecos($x, $dia)));
    }
}
