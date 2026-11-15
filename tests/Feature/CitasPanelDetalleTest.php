<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Las citas del panel de inicio abren su detalle (spec 048): un solo enlace por cita, en su dato
 * principal, con la fila o tarjeta entera pulsable por CSS. El comportamiento en el navegador
 * (pulsar en otra columna, toque, foco) lo mide `docs/specs/048-citas-panel-detalle/design/medir.mjs`.
 */
class CitasPanelDetalleTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss;

    private string $citaHoy;

    private string $citaPasada;

    protected function setUp(): void
    {
        parent::setUp();
        // Reloj fijo, como MovilPersonalTest: «hoy» no depende de la hora a la que corra la suite.
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        $this->crearEscenario();

        $this->citaHoy = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-14 10:00');
        $this->citaPasada = $this->cita($this->d1, $this->p1, 'completada', '2026-10-01 10:00');
    }

    // ── CA1, CA2: administración y asistente ─────────────────────────────

    public function test_el_administrador_abre_la_cita_desde_el_panel(): void
    {
        $this->assertAbreLaCitaDesdeLaTabla($this->admin, '/admin', 'admin.citas.show', $this->citaHoy);
    }

    public function test_la_asistente_abre_la_cita_desde_el_panel(): void
    {
        $this->assertAbreLaCitaDesdeLaTabla($this->asistente, '/asistente', 'asistente.citas.show', $this->citaHoy);
    }

    // ── CA3: la dentista ──────────────────────────────────────────────────

    public function test_la_dentista_abre_la_cita_desde_la_agenda(): void
    {
        $xpath = $this->xpath($this->actingAs($this->dentista1)->get('/dentista')->assertOk()->getContent());
        $href = route('dentista.citas.show', $this->citaHoy);

        $item = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' timeline-item ')][.//a[@href='{$href}']]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $item, 'La cita de hoy está en la agenda con un enlace a su detalle.');
        $this->assertContains('fila-cita', $this->clases($item), 'El elemento de la agenda lleva fila-cita.');

        $enlaces = $xpath->query('.//a', $item);
        $this->assertSame(1, $enlaces->length, 'Un solo enlace por cita en la agenda.');
        $ojo = $enlaces->item(0);
        $this->assertContains('cita-enlace', $this->clases($ojo), 'El ojo es el enlace de la cita.');
        $this->assertContains('btn-icono', $this->clases($ojo), 'El enlace sigue siendo el ojo.');

        // «Próximo paciente» sigue abriendo la misma cita, como hoy.
        $proximo = $xpath->query("//a[contains(@href, '/dentista/citas/{$this->citaHoy}')][not(ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' timeline-item ')])]");
        $this->assertSame(1, $proximo->length, '«Próximo paciente» enlaza a la misma cita.');

        $this->actingAs($this->dentista1)->get($href)->assertOk()
            ->assertSee("{$this->paciente1->nombre} {$this->paciente1->apellido}");
    }

    // ── CA4: el historial del paciente ────────────────────────────────────

    public function test_el_paciente_abre_una_cita_del_historial(): void
    {
        $xpath = $this->xpath($this->actingAs($this->paciente1)->get('/cliente')->assertOk()->getContent());
        $href = route('cliente.citas.show', $this->citaPasada);

        $fila = $xpath->query("//table/tbody/tr[.//a[@href='{$href}']]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $fila, 'La cita pasada está en el historial con un enlace a su detalle.');
        $this->assertContains('fila-cita', $this->clases($fila), 'La fila del historial lleva fila-cita.');
        $enlaces = $xpath->query('.//a', $fila);
        $this->assertSame(1, $enlaces->length, 'Un solo enlace por cita en el historial.');
        $enlace = $enlaces->item(0);
        $this->assertContains('cita-enlace', $this->clases($enlace), 'El enlace lleva cita-enlace.');
        $this->assertSame('01/10/2026', trim($enlace->textContent), 'El enlace es la fecha.');
        $this->assertContains('celda-cab', $this->clases($enlace->parentNode), 'La fecha sigue en la celda principal.');

        // «Tu próxima cita» conserva sus acciones (la cita de hoy a las 10:00 es la próxima).
        $proxima = $xpath->query("//a[@href='".route('cliente.citas.show', $this->citaHoy)."'][normalize-space()='Ver detalles']");
        $this->assertSame(1, $proxima->length, '«Tu próxima cita» sigue con «Ver detalles».');

        $this->actingAs($this->paciente1)->get($href)->assertOk();
    }

    // ── CA5, A8, A10, A11, A15: un solo enlace por cita y su nombre ──────

    public function test_cada_cita_tiene_un_solo_enlace_con_nombre_accesible(): void
    {
        // A15: el nombre lo escribe el paciente; sale escapado en el atributo.
        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['nombre' => 'Pa"blo <b>']);
        $paciente = 'Pa"blo <b> '.$this->paciente1->apellido;

        $casos = [
            [$this->admin, '/admin', "10:00, {$paciente}, ver detalle"],
            [$this->asistente, '/asistente', "10:00, {$paciente}, ver detalle"],
            [$this->dentista1, '/dentista', "10:00, {$paciente}, ver detalle"],
            // A11: la cita no tiene servicio; la celda dice «—» y el nombre, «sin servicio».
            [$this->paciente1, '/cliente', '01/10/2026, sin servicio, ver detalle'],
        ];
        foreach ($casos as [$usuario, $panel, $nombre]) {
            $html = $this->actingAs($usuario)->get($panel)->assertOk()->getContent();
            $this->assertStringNotContainsString('<b> ', $html, "{$panel}: el nombre del paciente sale escapado (A15).");
            $xpath = $this->xpath($html);

            $citas = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' fila-cita ')]");
            $this->assertGreaterThan(0, $citas->length, "{$panel}: hay citas con fila-cita.");
            foreach ($citas as $cita) {
                $this->assertSame(1, $xpath->query('.//a', $cita)->length, "{$panel}: un solo enlace por cita.");
                $this->assertSame(0, $xpath->query('.//button | .//a//a | .//script', $cita)->length, "{$panel}: sin botones, enlaces anidados ni scripts en la cita.");
                $this->assertSame('', $cita->getAttribute('style'), "{$panel}: la cita sin style=\"\" (A10).");

                $enlace = $xpath->query('.//a', $cita)->item(0);
                $this->assertSame('', $enlace->getAttribute('style'), "{$panel}: el enlace sin style=\"\" (A10).");
                $this->assertSame('', $enlace->getAttribute('onclick'), "{$panel}: el enlace no depende de JavaScript.");
                $etiqueta = $enlace->getAttribute('aria-label');
                $this->assertStringEndsWith(', ver detalle', $etiqueta, "{$panel}: el nombre termina en «ver detalle».");

                // WCAG 2.5.3: empieza por el texto visible; en el ojo, por la hora de su elemento (A8).
                $visible = trim($enlace->textContent) !== ''
                    ? trim($enlace->textContent)
                    : trim($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' timeline-time ')]", $cita)->item(0)?->textContent ?? '');
                $this->assertNotSame('', $visible, "{$panel}: el enlace tiene un texto visible de referencia.");
                $this->assertStringStartsWith($visible.', ', $etiqueta, "{$panel}: el nombre empieza por «{$visible}».");
            }
            $this->assertContains($nombre, array_map(fn ($a) => $a->getAttribute('aria-label'), iterator_to_array($xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' cita-enlace ')]"))), "{$panel}: la cita se llama «{$nombre}».");
        }
    }

    // ── CA7: sin citas ────────────────────────────────────────────────────

    public function test_sin_citas_el_panel_no_tiene_enlaces_de_cita(): void
    {
        // Un día sin citas y una paciente sin historial.
        $this->travelTo(Carbon::parse('2030-01-08 08:00:00', 'UTC'));
        $casos = [
            [$this->admin, '/admin', 'No hay citas programadas para hoy'],
            [$this->asistente, '/asistente', 'No hay citas programadas para hoy'],
            [$this->dentista1, '/dentista', 'No tienes citas programadas para hoy'],
            [$this->paciente2, '/cliente', null],
        ];
        foreach ($casos as [$usuario, $panel, $vacio]) {
            $html = $this->actingAs($usuario)->get($panel)->assertOk()->getContent();
            $xpath = $this->xpath($html);
            $this->assertSame(0, $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' cita-enlace ') or contains(concat(' ', normalize-space(@class), ' '), ' fila-cita ')]")->length, "{$panel}: sin citas no hay enlaces de cita.");
            if ($vacio !== null) {
                $this->assertStringContainsString($vacio, $html, "{$panel}: se ve el estado vacío de hoy.");
            }
        }
    }

    // ── CA9: las tablas conservan sus columnas ────────────────────────────

    public function test_las_tablas_del_panel_conservan_sus_columnas(): void
    {
        $tablas = [
            [$this->admin, '/admin', ['Hora', 'Paciente', 'Dentista', 'Servicio', 'Estado']],
            [$this->asistente, '/asistente', ['Hora', 'Paciente', 'Doctor', 'Servicio', 'Estado']],
            [$this->paciente1, '/cliente', ['Fecha', 'Servicio', 'Dentista', 'Estado']],
        ];
        foreach ($tablas as [$usuario, $panel, $columnas]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($panel)->assertOk()->getContent());
            $tabla = $xpath->query('//table')->item(0);
            $this->assertInstanceOf(DOMElement::class, $tabla, "{$panel}: tiene su tabla.");
            $cabeceras = array_map(fn ($th) => trim($th->textContent), iterator_to_array($xpath->query('./thead/tr/th', $tabla)));
            $this->assertSame($columnas, $cabeceras, "{$panel}: las mismas columnas que antes.");

            $filas = $xpath->query('./tbody/tr', $tabla);
            $this->assertGreaterThan(0, $filas->length, "{$panel}: hay filas.");
            foreach ($filas as $fila) {
                $etiquetas = array_map(fn ($td) => $td->getAttribute('data-label'), iterator_to_array($xpath->query('./td', $fila)));
                $this->assertSame($columnas, $etiquetas, "{$panel}: una celda por columna con su data-label.");
            }
        }
    }

    // ── CA8 (abuso), TM1: una cita ajena se niega como hoy ───────────────

    public function test_una_cita_ajena_desde_la_href_del_panel_se_niega_como_hoy(): void
    {
        $ajena = $this->cita($this->d2, $this->p2, 'agendada', '2026-10-14 11:00');
        $nombreAjeno = "{$this->paciente2->nombre} {$this->paciente2->apellido}";

        // La href que da el panel (`route()` del detalle de cada rol), con el identificador cambiado.
        $casos = [
            [$this->dentista1, 'dentista.citas.show', $this->citaHoy, 403],
            [$this->asistente, 'asistente.citas.show', $this->citaHoy, 403],
            [$this->paciente1, 'cliente.citas.show', $this->citaPasada, 404],
        ];
        foreach ($casos as [$usuario, $ruta, $propia, $estado]) {
            $this->actingAs($usuario)->get(route($ruta, $propia))->assertOk();

            $manipulada = str_replace($propia, $ajena, route($ruta, $propia));
            $respuesta = $this->actingAs($usuario)->get($manipulada);
            $respuesta->assertStatus($estado);
            $respuesta->assertDontSee($nombreAjeno);
        }
    }

    // ── CA5, CA6, P9: reglas de app.css ───────────────────────────────────

    public function test_las_reglas_048_usan_tokens(): void
    {
        $bloque = $this->bloque048();

        // P9 (A9): sin literales, con control positivo de la comprobación.
        $this->assertNotEmpty($this->literales('padding: 4px; color: #ffffff;'), 'Control: literales() detecta px y colores.');
        // Solo los cuerpos de las reglas: los puntos de corte de las @media (900 px) no son valores de estilo.
        $cuerpos = implode(' ', array_column($this->analizarCss($bloque), 1));
        $this->assertNotSame('', trim($cuerpos), 'Control: el bloque tiene reglas.');
        $this->assertSame([], $this->literales($cuerpos), 'El bloque 048 solo usa tokens.');

        $this->assertDeclara048('.fila-cita', 'position: relative');
        // A2: en reposo, la hora o fecha se ve como hoy; el ojo conserva su .btn-ghost.
        foreach (['.fila-cita .cita-enlace:not(.btn)', '.fila-cita .cita-enlace:not(.btn):visited'] as $selector) {
            $this->assertDeclara048($selector, 'color: inherit');
            $this->assertDeclara048($selector, 'text-decoration: none');
        }
        $this->assertDeclara048('.fila-cita .cita-enlace::after', 'content: ""');
        $this->assertDeclara048('.fila-cita .cita-enlace::after', 'position: absolute');
        $this->assertDeclara048('.fila-cita .cita-enlace::after', 'inset: 0');
        $this->assertDeclara048('.fila-cita .cita-enlace::after', 'cursor: pointer');
        // A6: un solo anillo, el del ::after, por dentro de la cita.
        $this->assertDeclara048('.fila-cita .cita-enlace:focus-visible', 'outline: none');
        $this->assertDeclara048('.fila-cita .cita-enlace:focus-visible::after', 'outline: var(--focus-ring-width) solid var(--focus-ring)');
        $this->assertDeclara048('.fila-cita .cita-enlace:focus-visible::after', 'outline-offset: calc(-1 * var(--focus-ring-width))');
        // A5: en la tarjeta del móvil, el anillo sigue su radio.
        $this->assertDeclara048('.fila-cita .cita-enlace:focus-visible::after', 'border-radius: var(--radius)', ['@media (max-width: 899px)']);
        // CA6 y A16: fondo al pasar el puntero en la agenda de la dentista, solo en el escritorio.
        $this->assertDeclara048('.timeline-item.fila-cita:hover', 'background: var(--p50)', ['@media (min-width: 900px)']);

        // A1: ninguna regla del bloque da fondo a una cita por debajo de 900 px (A57 de la 034).
        foreach ($this->analizarCss($bloque) as [$selectores, $cuerpo, $condiciones]) {
            if (in_array('@media (max-width: 899px)', $condiciones, true)) {
                $this->assertDoesNotMatchRegularExpression('/(^|;)\s*background(-color)?\s*:/', trim($cuerpo), "{$selectores}: sin fondo en el móvil.");
            }
            $this->assertStringContainsString('fila-cita', $selectores, "{$selectores}: las reglas 048 se limitan a .fila-cita.");
        }
    }

    public function test_el_foco_de_la_cita_contrasta(): void
    {
        $tokens = $this->tokensDeColor();
        foreach (['--white', '--p50'] as $fondo) {
            $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--focus-ring'], $tokens[$fondo]), "--focus-ring sobre {$fondo}: ≥ 3:1.");
        }
    }

    // ── Ayudantes ─────────────────────────────────────────────────────────

    /** Bloque `/* Spec 048 *\/` de app.css, hasta su cierre. */
    private function bloque048(): string
    {
        $css = file_get_contents(public_path('css/app.css'));
        $inicio = strpos($css, '/* Spec 048');
        $this->assertNotFalse($inicio, 'app.css debe tener el bloque /* Spec 048 */.');
        $fin = strpos($css, '/* Fin spec 048 */', $inicio);
        $this->assertNotFalse($fin, 'app.css debe cerrar el bloque con /* Fin spec 048 */.');

        return substr($css, $inicio, $fin - $inicio);
    }

    private function assertDeclara048(string $selector, string $declaracion, array $condiciones = []): void
    {
        $cuerpos = [];
        foreach ($this->analizarCss($this->bloque048()) as [$selectores, $cuerpo, $suyas]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if ($suyas === $condiciones && in_array($selector, $lista, true)) {
                $cuerpos[] = trim(preg_replace('/\s+/', ' ', $cuerpo));
            }
        }
        [$propiedad, $valor] = array_map('trim', explode(':', $declaracion, 2));
        $this->assertMatchesRegularExpression(
            '/(^|;)\s*'.preg_quote($propiedad, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*(;|$)/',
            implode(' ', $cuerpos),
            "{$selector} debe declarar «{$declaracion}» en el bloque 048 de app.css."
        );
    }

    /** La fila de la cita lleva `fila-cita` y un único enlace, en su `td.celda-cab`, que abre su detalle. */
    private function assertAbreLaCitaDesdeLaTabla(Usuario $usuario, string $panel, string $ruta, string $cita): DOMElement
    {
        $xpath = $this->xpath($this->actingAs($usuario)->get($panel)->assertOk()->getContent());
        $href = route($ruta, $cita);

        $fila = $xpath->query("//table/tbody/tr[.//a[@href='{$href}']]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $fila, "{$panel}: la fila de la cita tiene un enlace a su detalle.");
        $this->assertContains('fila-cita', $this->clases($fila), "{$panel}: la fila lleva fila-cita.");

        $enlaces = $xpath->query('.//a', $fila);
        $this->assertSame(1, $enlaces->length, "{$panel}: un solo enlace por cita.");
        $enlace = $enlaces->item(0);
        $this->assertContains('cita-enlace', $this->clases($enlace), "{$panel}: el enlace lleva cita-enlace.");
        $celda = $enlace->parentNode;
        $this->assertInstanceOf(DOMElement::class, $celda);
        $this->assertSame('td', $celda->nodeName, "{$panel}: el enlace va directamente en su celda.");
        $this->assertContains('celda-cab', $this->clases($celda), "{$panel}: el enlace va en la celda principal.");

        $this->actingAs($usuario)->get($href)->assertOk()
            ->assertSee("{$this->paciente1->nombre} {$this->paciente1->apellido}");

        return $enlace;
    }

    /** @return string[] */
    private function clases(DOMElement $elemento): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($elemento->getAttribute('class')))));
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
