<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Móvil del personal y listas en tarjetas (spec 034): marcado de las tablas incluidas, filtro de
 * citas, detalle de cita, reglas de `movil.css` y `app.css` y contrato de `filtros.js` (P9).
 *
 * El comportamiento en un navegador real (anchos, solapes, toque, foco, palabras largas, línea base
 * del escritorio) lo comprueba `docs/design/capturar.mjs --medir` (plan, Estrategia de pruebas).
 */
class MovilPersonalTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss;

    private const MOVIL = ['@media (max-width: 899px)'];

    /** Clases de celda de D1 que fija la tabla D2 del plan. */
    private const CLASES_D2 = ['celda-cab', 'celda-estado', 'celda-acciones', 'solo-escritorio'];

    /** Columnas con clase de celda de cada tabla incluida (plan D2), por el texto de su `<th>`. */
    private const D2 = [
        'citas-personal' => ['Hora / Fecha' => ['celda-cab'], 'Estado' => ['celda-estado'], 'Acciones' => ['celda-acciones']],
        'mis-citas' => ['Fecha / Hora' => ['celda-cab'], 'Estado' => ['celda-estado'], 'Acciones' => ['celda-acciones']],
        'panel-paciente' => ['Fecha' => ['celda-cab'], 'Estado' => ['celda-estado']],
        'panel-personal' => ['Hora' => ['celda-cab'], 'Estado' => ['celda-estado']],
        'pacientes' => [
            'Paciente' => ['celda-cab'], 'Acciones' => ['celda-acciones'], 'Doctor' => ['solo-escritorio'],
            'Ciudad' => ['solo-escritorio'], 'Tipo sangre' => ['solo-escritorio'], 'Registrado' => ['solo-escritorio'],
        ],
        'servicios' => [
            'Nombre' => ['celda-cab'], 'Acciones' => ['celda-acciones'],
            'Descripción' => ['solo-escritorio'], 'Categoría' => ['solo-escritorio'],
        ],
    ];

    /** Etiquetas de la tarjeta que no son el texto de su `<th>` (plan D1). */
    private const ETIQUETAS = ['Contacto' => 'Teléfono'];

    private const TELEFONO = '5550102';

    private string $citaHoy;

    private string $citaManana;

    protected function setUp(): void
    {
        parent::setUp();
        // Reloj fijo: «hoy» y «mañana» no dependen de la hora a la que corra la suite.
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        $this->crearEscenario();

        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['telefono' => self::TELEFONO]);
        $this->citaHoy = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-14 10:00');
        $this->citaManana = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-15 11:00');
        $this->cita($this->d1, $this->p1, 'completada', '2026-10-01 10:00');
        $this->crearServicio();
    }

    public function test_las_tablas_incluidas_se_marcan_como_tarjetas(): void
    {
        foreach ($this->pantallas() as [$usuario, $url, $tipo]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());

            $tablas = $xpath->query('//table');
            $this->assertSame(1, $tablas->length, "{$url}: una tabla.");
            $tabla = $tablas->item(0);
            $this->assertContains('tabla-tarjetas', $this->clases($tabla), "{$url}: la tabla lleva tabla-tarjetas.");

            $cabeceras = $this->cabeceras($xpath, $tabla);
            $filas = $this->filas($xpath, $tabla);
            $this->assertNotEmpty($filas, "{$url}: hay filas con datos.");
            foreach ($filas as $fila) {
                $celdas = $xpath->query('./td', $fila);
                $this->assertSame(count($cabeceras), $celdas->length, "{$url}: una celda por columna.");
                foreach ($cabeceras as $i => [$texto, $th]) {
                    $td = $celdas->item($i);
                    $esperada = self::ETIQUETAS[$texto] ?? $texto;
                    $this->assertSame($esperada, $td->getAttribute('data-label'), "{$url}: data-label de «{$texto}».");
                    $this->assertEqualsCanonicalizing($this->clases($th), $this->clases($td), "{$url}: «{$texto}» con las mismas clases que su <th>.");
                }
            }
        }
    }

    public function test_las_clases_de_celda_siguen_la_tabla_d2(): void
    {
        foreach ($this->pantallas() as [$usuario, $url, $tipo]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            $tabla = $xpath->query('//table')->item(0);
            $this->assertNotNull($tabla, "{$url}: hay tabla.");

            $vistas = [];
            foreach ($this->cabeceras($xpath, $tabla) as [$texto, $th]) {
                $vistas[] = $texto;
                $esperadas = self::D2[$tipo][$texto] ?? [];
                $this->assertEqualsCanonicalizing($esperadas, array_values(array_intersect($this->clases($th), self::CLASES_D2)),
                    "{$url}: clases de celda de «{$texto}» (D2).");
            }
            foreach (array_keys(self::D2[$tipo]) as $columna) {
                $this->assertContains($columna, $vistas, "{$url}: existe la columna «{$columna}».");
            }
        }

        // El correo va dentro de la celda del paciente y tampoco se muestra en el móvil (A16).
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes')->assertOk()->getContent());
        $correo = $xpath->query("//table//td[1]//div[normalize-space()='{$this->paciente1->email}']");
        $this->assertSame(1, $correo->length, 'El correo del paciente está en su celda.');
        $this->assertContains('solo-escritorio', $this->clases($correo->item(0)), 'El div del correo lleva solo-escritorio.');
    }

    /** A37: el valor de una celda con etiqueta queda entero en la columna del valor. */
    public function test_cada_celda_con_etiqueta_tiene_un_solo_valor(): void
    {
        foreach ($this->pantallas() as [$usuario, $url]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            $tabla = $xpath->query('//table')->item(0);
            $this->assertNotNull($tabla, "{$url}: hay tabla.");

            foreach ($this->filas($xpath, $tabla) as $fila) {
                foreach ($xpath->query('./td', $fila) as $td) {
                    if (array_intersect($this->clases($td), ['celda-cab', 'celda-estado', 'celda-acciones', 'solo-escritorio']) !== []) {
                        continue;
                    }
                    $elementos = $xpath->query('./*', $td)->length;
                    $texto = trim(implode('', array_map(fn ($n) => $n->textContent, iterator_to_array($xpath->query('./text()', $td)))));
                    $this->assertTrue($elementos === 0 || ($elementos === 1 && $texto === ''),
                        "{$url}: «{$td->getAttribute('data-label')}» es solo texto o un único elemento.");
                }
            }
        }

        // Caso con dos datos: nombre y teléfono del paciente, dentro de un único celda-valor.
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/citas')->assertOk()->getContent());
        $valor = $xpath->query("(//table//td[@data-label='Paciente'][contains(., '".self::TELEFONO."')])[1]/*");
        $this->assertSame(1, $valor->length, 'La celda del paciente con teléfono tiene un solo hijo.');
        $this->assertContains('celda-valor', $this->clases($valor->item(0)), 'Ese hijo es .celda-valor.');
    }

    public function test_la_cita_de_hoy_se_distingue_sin_fondo_en_linea(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/citas')->assertOk()->getContent());

        $hoy = $this->filaDeLaCita($xpath, $this->citaHoy);
        $this->assertContains('fila-hoy', $this->clases($hoy), 'La fila de hoy lleva fila-hoy.');
        $this->assertStringContainsString('Hoy', $hoy->textContent, 'La fila de hoy dice «Hoy».');
        $this->assertStringNotContainsString('background', $hoy->getAttribute('style'), 'Su fondo no va en style="" (A57).');

        $manana = $this->filaDeLaCita($xpath, $this->citaManana);
        $this->assertNotContains('fila-hoy', $this->clases($manana), 'La de mañana no es fila-hoy.');
    }

    public function test_los_botones_de_icono_tienen_nombre_y_texto_en_el_movil(): void
    {
        $casos = [
            [$this->admin, '/admin/citas', ['Ver detalle']],
            [$this->admin, '/admin/pacientes', ['Ver perfil', 'Editar']],
            [$this->asistente, '/asistente/pacientes', ['Ver perfil']],
            [$this->admin, '/admin/servicios', ['Editar', 'Eliminar']],
        ];
        foreach ($casos as [$usuario, $url, $nombres]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            foreach ($nombres as $nombre) {
                $botones = $xpath->query("//table//td//*[(self::a or self::button)][@title='{$nombre}']");
                $this->assertGreaterThan(0, $botones->length, "{$url}: hay botones «{$nombre}».");
                foreach ($botones as $boton) {
                    $this->assertSame($nombre, $boton->getAttribute('aria-label'), "{$url}: «{$nombre}» con aria-label.");
                    $texto = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' solo-movil ')]", $boton);
                    $this->assertSame(1, $texto->length, "{$url}: «{$nombre}» con su texto en .solo-movil.");
                    $this->assertSame($nombre, trim($texto->item(0)->textContent), "{$url}: el texto visible en el móvil es «{$nombre}».");
                }
            }
        }
    }

    /** D5b (A35) y D8 (A56): el panel de la dentista sin los style="" que impiden el móvil. */
    public function test_el_panel_de_la_dentista_se_adapta_al_movil(): void
    {
        $xpath = $this->xpath($this->actingAs($this->dentista1)->get('/dentista')->assertOk()->getContent());

        $enlace = $xpath->query("//*[contains(@class,'timeline-item')]//a[@href='/dentista/citas/{$this->citaHoy}']");
        $this->assertSame(1, $enlace->length, 'La cita de hoy está en la línea de tiempo con su enlace.');
        $enlace = $enlace->item(0);
        $this->assertSame('Ver detalle', $enlace->getAttribute('aria-label'), 'El enlace se llama «Ver detalle».');
        $this->assertContains('btn-icono', $this->clases($enlace), 'El enlace lleva btn-icono.');
        $this->assertStringNotContainsString('padding', $enlace->getAttribute('style'), 'Sin padding en su style="".');

        $acciones = $enlace->parentNode;
        $this->assertInstanceOf(DOMElement::class, $acciones);
        $this->assertContains('timeline-acciones', $this->clases($acciones), 'El estado y el enlace van en .timeline-acciones.');
        $this->assertSame('', $acciones->getAttribute('style'), '.timeline-acciones sin style="".');
        $this->assertSame(1, $xpath->query(".//*[contains(@class,'badge')]", $acciones)->length, 'El estado va con el enlace.');

        $proximo = $xpath->query("//div[normalize-space()='{$this->paciente1->nombre} {$this->paciente1->apellido}'][contains(concat(' ', normalize-space(@class), ' '), ' texto-partible ')]");
        $this->assertGreaterThan(0, $proximo->length, 'El nombre de «Próximo paciente» lleva texto-partible (A56).');
    }

    /** D8 (A56): el nombre de la cabecera de la tarjeta de pacientes parte de línea. */
    public function test_el_nombre_de_la_tarjeta_de_pacientes_parte_de_linea(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes')->assertOk()->getContent());

        $nombre = $xpath->query("//table//td[1]//div[contains(concat(' ', normalize-space(@class), ' '), ' texto-partible ')][contains(., '{$this->paciente1->nombre}')]");
        $this->assertSame(1, $nombre->length, 'El div del nombre del paciente lleva texto-partible.');
    }

    public function test_las_rejillas_de_estadisticas_usan_stats_grid(): void
    {
        // Control positivo del detector de columnas en línea.
        $this->assertTrue($this->columnasEnLinea('display:grid;grid-template-columns:repeat(4,1fr)'));
        $this->assertFalse($this->columnasEnLinea('max-width:600px'));

        $casos = [
            [$this->admin, '/admin/citas', null],
            [$this->admin, '/admin/pacientes', 'stats-grid-3'],
            [$this->admin, '/admin/servicios', 'stats-grid-2'],
            [$this->admin, '/admin', 'stats-grid-2'],
            [$this->asistente, '/asistente', 'stats-grid-2'],
            [$this->dentista1, '/dentista', 'stats-grid-3'],
        ];
        foreach ($casos as [$usuario, $url, $variante]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            $rejillas = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' stats-grid ')]");
            $this->assertSame(1, $rejillas->length, "{$url}: una rejilla .stats-grid.");
            $rejilla = $rejillas->item(0);
            if ($variante !== null) {
                $this->assertContains($variante, $this->clases($rejilla), "{$url}: lleva {$variante}.");
            }
            $this->assertFalse($this->columnasEnLinea($rejilla->getAttribute('style')), "{$url}: sin grid-template-columns en style=\"\".");
        }

        // Servicios conserva su ancho máximo en línea (D10b).
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/servicios')->assertOk()->getContent());
        $this->assertStringContainsString('max-width:600px', (string) $xpath->query("//*[contains(@class,'stats-grid')]")->item(0)?->getAttribute('style'));
    }

    /** D6 (A33): el filtro sin sus style="", en el orden del plan. */
    public function test_el_filtro_de_citas_tiene_el_orden_y_las_clases_del_plan(): void
    {
        foreach (['admin' => $this->admin, 'asistente' => $this->asistente, 'dentista' => $this->dentista1] as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/citas")->assertOk()->getContent());

            $form = $xpath->query("//form[.//input[@name='buscar']]");
            $this->assertSame(1, $form->length, "{$rol}: el formulario del filtro.");
            $form = $form->item(0);
            $this->assertContains('filtro-citas', $this->clases($form), "{$rol}: el <form> lleva .filtro-citas.");
            $this->assertSame('', $form->getAttribute('style'), "{$rol}: el <form> sin style=\"\".");

            // Cada contenedor con su clase y sin los style="" que impiden el móvil (control positivo abajo).
            $contenedores = [
                'filtro-tarjeta' => $form->parentNode,
                'filtro-buscar' => $xpath->query(".//input[@name='buscar']/..", $form)->item(0),
                'filtro-fecha' => $xpath->query(".//input[@name='fecha_desde']/..", $form)->item(0),
                'filtro-estado' => $xpath->query(".//select[@name='estado']/..", $form)->item(0),
                'filtro-atajos' => $xpath->query(".//a[normalize-space()='Hoy']/..", $form)->item(0),
            ];
            $hasta = $xpath->query(".//input[@name='fecha_hasta']/..", $form)->item(0);
            $this->assertContains('filtro-fecha', $this->clases($hasta), "{$rol}: «Hasta» lleva .filtro-fecha.");
            if ($rol !== 'dentista') {
                $contenedores['filtro-dentista'] = $xpath->query(".//select[@name='dentista_id']/..", $form)->item(0);
            }
            foreach ($contenedores + ['filtro-fecha (hasta)' => $hasta] as $clase => $nodo) {
                $this->assertInstanceOf(DOMElement::class, $nodo, "{$rol}: existe {$clase}.");
                $this->assertContains(strtok($clase, ' '), $this->clases($nodo), "{$rol}: lleva .{$clase}.");
                $this->assertFalse($this->estiloDeMaqueta($nodo->getAttribute('style')), "{$rol}: .{$clase} sin min-width, flex, margin ni padding en style=\"\".");
            }

            // Orden: buscador, fila de atajos (Esta semana, Hoy, Filtros) y el panel.
            $hijos = array_values(array_filter(iterator_to_array($xpath->query('./*', $form)), fn ($n) => $n->nodeName !== 'input' || $n->getAttribute('type') !== 'hidden'));
            $this->assertContains('filtro-buscar', $this->clases($hijos[0]), "{$rol}: primero el buscador.");
            $this->assertContains('filtro-atajos', $this->clases($hijos[1]), "{$rol}: después la fila de atajos.");
            $this->assertSame('filtro-panel', $hijos[2]->getAttribute('id'), "{$rol}: y después #filtro-panel.");
            $this->assertContains('filtro-panel', $this->clases($hijos[2]), "{$rol}: el panel lleva .filtro-panel.");

            $atajos = [];
            foreach ($xpath->query('./*', $hijos[1]) as $atajo) {
                $atajos[] = trim(preg_replace('/\s+/', ' ', $atajo->textContent));
            }
            $this->assertSame(['Esta semana', 'Hoy', 'Filtros'], $atajos, "{$rol}: «Esta semana · Hoy · Filtros» (A15).");

            $boton = $xpath->query("./button[contains(concat(' ', normalize-space(@class), ' '), ' filtro-btn ')]", $hijos[1]);
            $this->assertSame(1, $boton->length, "{$rol}: el botón «Filtros».");
            $boton = $boton->item(0);
            foreach (['btn', 'btn-outline', 'filtro-btn'] as $clase) {
                $this->assertContains($clase, $this->clases($boton), "{$rol}: «Filtros» lleva .{$clase} (A45).");
            }
            $this->assertSame('button', $boton->getAttribute('type'), "{$rol}: «Filtros» es type=button.");
            $this->assertSame('false', $boton->getAttribute('aria-expanded'), "{$rol}: «Filtros» empieza contraído.");
            $this->assertSame('filtro-panel', $boton->getAttribute('aria-controls'), "{$rol}: «Filtros» controla el panel.");

            $panel = [];
            foreach ($xpath->query('.//input[@name] | .//select[@name] | .//button[@type="submit"] | .//a', $hijos[2]) as $control) {
                $panel[] = $control->getAttribute('name') ?: trim($control->textContent);
            }
            $esperado = $rol === 'dentista'
                ? ['fecha_desde', 'fecha_hasta', 'estado', 'Filtrar', 'Limpiar']
                : ['fecha_desde', 'fecha_hasta', 'dentista_id', 'estado', 'Filtrar', 'Limpiar'];
            $this->assertSame($esperado, $panel, "{$rol}: el panel con desde, hasta, dentista, estado, «Filtrar» y «Limpiar».");

            // Cada etiqueta asociada a su campo (A4).
            $etiquetas = $xpath->query('.//label', $form);
            $this->assertSame($rol === 'dentista' ? 4 : 5, $etiquetas->length, "{$rol}: una etiqueta por campo.");
            foreach ($etiquetas as $etiqueta) {
                $para = $etiqueta->getAttribute('for');
                $this->assertNotSame('', $para, "{$rol}: «{$etiqueta->textContent}» con for.");
                $this->assertSame(1, $xpath->query(".//*[@id='{$para}'][self::input or self::select]", $form)->length, "{$rol}: «{$etiqueta->textContent}» apunta a su campo.");
            }
        }

        // Control positivo del detector de estilos de maquetación.
        $this->assertTrue($this->estiloDeMaqueta('flex:1;min-width:180px'));
        $this->assertTrue($this->estiloDeMaqueta('margin-left:auto;display:flex;gap:6px'));
        $this->assertTrue($this->estiloDeMaqueta('margin-bottom:20px;padding:16px 20px'));
        $this->assertFalse($this->estiloDeMaqueta(''));
    }

    public function test_el_boton_filtros_indica_los_filtros_aplicados(): void
    {
        $casos = [
            '/admin/citas' => null,
            '/admin/citas?estado=agendada' => 1,
            '/admin/citas?fecha_hasta=2026-10-20&estado=agendada' => 2,
        ];
        foreach ($casos as $url => $aplicados) {
            $xpath = $this->xpath($this->actingAs($this->admin)->get($url)->assertOk()->getContent());
            $boton = $xpath->query("//button[contains(concat(' ', normalize-space(@class), ' '), ' filtro-btn ')]")->item(0);
            $this->assertInstanceOf(DOMElement::class, $boton, "{$url}: hay botón «Filtros».");
            $contador = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' filtro-contador ')]", $boton);

            if ($aplicados === null) {
                $this->assertSame(0, $contador->length, "{$url}: sin filtros, sin contador.");
                $this->assertFalse($boton->hasAttribute('aria-label'), "{$url}: sin filtros, sin aria-label.");

                continue;
            }
            $this->assertSame(1, $contador->length, "{$url}: con filtros, contador.");
            $this->assertSame((string) $aplicados, trim($contador->item(0)->textContent), "{$url}: cuenta {$aplicados}.");
            $nombre = $aplicados === 1 ? 'Filtros, 1 aplicado' : "Filtros, {$aplicados} aplicados";
            $this->assertSame($nombre, $boton->getAttribute('aria-label'), "{$url}: nombre «{$nombre}» (A49).");
        }
    }

    public function test_la_lista_de_citas_carga_filtros_js_en_head(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/citas')->assertOk()->getContent();
        $cabecera = (string) strstr((string) strstr($html, '<head>'), '</head>', true);

        $this->assertMatchesRegularExpression('#<script src="[^"]*js/filtros\.js\?v=\d+"></script>#', $cabecera,
            'filtros.js en <head>, con ?v= y sin defer ni async.');
        $this->assertStringNotContainsString('filtros.js', $this->actingAs($this->admin)->get('/admin')->getContent(), 'Solo en la lista de citas.');
    }

    /** D7: el detalle de cita del personal en el orden de la spec, con sus id y envoltorios. */
    public function test_el_detalle_de_cita_sigue_el_orden_de_la_spec(): void
    {
        foreach (['admin' => $this->admin, 'asistente' => $this->asistente, 'dentista' => $this->dentista1] as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/citas/{$this->citaHoy}")->assertOk()->getContent());

            $detalle = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' detalle-cita ')]");
            $this->assertSame(1, $detalle->length, "{$rol}: un .detalle-cita.");
            $this->assertSame('', $detalle->item(0)->getAttribute('style'), "{$rol}: .detalle-cita sin style=\"\".");

            $bloques = [];
            foreach ($xpath->query('./*', $detalle->item(0)) as $hijo) {
                $bloques[] = implode(' ', array_values(array_filter($this->clases($hijo), fn ($c) => str_starts_with($c, 'detalle-'))));
            }
            $esperados = $rol === 'admin'
                ? ['detalle-datos', 'detalle-lateral', 'detalle-control', 'detalle-historial']
                : ['detalle-datos', 'detalle-lateral', 'detalle-historial'];
            $this->assertSame($esperados, $bloques, "{$rol}: bloques en el orden de la spec.");

            $lateral = "//*[contains(@class,'detalle-lateral')]";
            $this->assertSame(1, $xpath->query("{$lateral}//*[@id='estado']")->length, "{$rol}: #estado en el lateral.");
            $this->assertSame(1, $xpath->query("{$lateral}//*[contains(@class,'formulario-accesible')]/*[@id='acciones']")->length, "{$rol}: .formulario-accesible > #acciones en el lateral.");
            $this->assertSame(1, $xpath->query("//*[contains(@class,'detalle-historial')]/descendant-or-self::*[contains(@class,'formulario-accesible')]/*[@id='historial']")->length, "{$rol}: .formulario-accesible > #historial en su bloque.");
            $this->assertSame($rol === 'admin' ? 1 : 0, $xpath->query("//*[contains(@class,'detalle-control')]/descendant-or-self::*[@id='control-interno']")->length, "{$rol}: #control-interno solo para el admin.");

            $rejilla = $xpath->query("//*[contains(@class,'detalle-datos')]//*[contains(concat(' ', normalize-space(@class), ' '), ' detalle-datos-rejilla ')]");
            $this->assertSame(1, $rejilla->length, "{$rol}: la rejilla de datos lleva .detalle-datos-rejilla.");
            $this->assertFalse($this->columnasEnLinea($rejilla->item(0)->getAttribute('style')), "{$rol}: sin grid-template-columns en línea.");

            // D8 (A34): la cabecera de la tarjeta parte de línea con un servicio o una fecha largos.
            $this->assertSame(1, $xpath->query("//*[contains(@class,'detalle-datos')]//div[contains(concat(' ', normalize-space(@class), ' '), ' texto-partible ')][contains(., 'Consulta dental')]")->length,
                "{$rol}: el texto de la cabecera lleva texto-partible.");
        }
    }

    /** D1: la tabla se ve como tarjetas por debajo de 900 px (A37, A39, A57, A65). */
    public function test_movil_css_convierte_las_tablas_en_tarjetas(): void
    {
        $this->assertDeclara('.tabla-tarjetas thead', 'display: none');
        foreach (['display: block', 'background: var(--bg)', 'padding: var(--space-5)'] as $d) {
            $this->assertDeclara('.tabla-tarjetas tbody', $d);
        }
        foreach (['display: grid', 'grid-template-columns: minmax(0, 1fr) auto', 'background: var(--white)',
            'border: var(--border-width) solid var(--border)', 'border-radius: var(--radius)', 'padding: var(--space-5) var(--space-6)'] as $d) {
            $this->assertDeclara('.tabla-tarjetas tr', $d);
        }
        $this->assertDeclara('.tabla-tarjetas tr + tr', 'margin-top: var(--space-5)');

        foreach (['padding: var(--space-1) 0', 'border-bottom: 0', 'display: grid', 'grid-column: 1 / -1',
            'grid-template-columns: var(--tarjeta-etiqueta-w) minmax(0, 1fr)', 'overflow-wrap: break-word'] as $d) {
            $this->assertDeclara('.tabla-tarjetas td', $d);
        }
        foreach (['content: attr(data-label)', 'font-size: var(--text-small)', 'font-weight: var(--weight-strong)',
            'text-transform: uppercase', 'color: var(--p600)'] as $d) {
            $this->assertDeclara('.tabla-tarjetas td::before', $d);
        }
        $this->assertStringNotContainsString('letter-spacing', $this->movil('.tabla-tarjetas td::before'), 'La etiqueta sin letter-spacing (A1).');

        // Cabecera arriba a la izquierda y estado arriba a la derecha, sea cual sea su columna (A37).
        $this->assertDeclara('.tabla-tarjetas .celda-cab', 'grid-row: 1');
        $this->assertDeclara('.tabla-tarjetas .celda-cab', 'grid-column: 1');
        $this->assertDeclara('.tabla-tarjetas .celda-estado', 'grid-row: 1');
        $this->assertDeclara('.tabla-tarjetas .celda-estado', 'grid-column: 2');
        foreach (['.tabla-tarjetas .celda-cab', '.tabla-tarjetas .celda-estado', '.tabla-tarjetas .celda-acciones'] as $sin) {
            $this->assertDeclara($sin, 'display: block');
            $this->assertDeclara("{$sin}::before", 'content: none');
        }
        // Celdas sin etiqueta: relleno y borde con tokens; el pie de acciones con su separador (A65).
        foreach (['margin-top: var(--space-3)', 'padding-top: var(--space-3)', 'border-top: var(--border-width) solid var(--border)'] as $d) {
            $this->assertDeclara('.tabla-tarjetas .celda-acciones', $d);
        }
        foreach (['.tabla-tarjetas .celda-acciones > div', '.tabla-tarjetas .celda-acciones .acciones-fila'] as $envoltorio) {
            $this->assertDeclara($envoltorio, 'display: flex');
            $this->assertDeclara($envoltorio, 'flex-wrap: wrap');
            $this->assertDeclara($envoltorio, 'gap: var(--space-3)');
        }
        $this->assertDeclara('.tabla-tarjetas .celda-acciones .btn', 'flex: 1 1 auto');
        // El texto de los botones, centrado como en la opción A (decisión del usuario, visto en T024).
        $this->assertDeclara('.tabla-tarjetas .celda-acciones .btn', 'justify-content: center');

        // La cita de hoy: tarjeta blanca con barra, y sin el fondo del :hover de app.css (A57).
        $this->assertDeclara('.tabla-tarjetas .fila-hoy', 'background: var(--white)');
        $this->assertDeclara('.tabla-tarjetas .fila-hoy', 'box-shadow: inset var(--border-width-strong) 0 0 var(--p400)');
        $this->assertDeclara('.tabla-tarjetas tr:hover td', 'background: transparent');

        $this->assertDeclara('.solo-escritorio', 'display: none');
        $this->assertDeclara('.solo-movil', 'display: inline');
        $this->assertDeclara('.stats-grid', 'grid-template-columns: repeat(2, minmax(0, 1fr))');
    }

    /** D5b, D6, D7 y D8 en el móvil (A33, A34, A55, A56, A69). */
    public function test_movil_css_adapta_filtro_detalle_y_panel_de_la_dentista(): void
    {
        // Panel de la dentista (D5b).
        $this->assertDeclara('.btn-icono', 'min-width: var(--touch-min)');
        $this->assertDeclara('.btn-icono', 'justify-content: center');
        $this->assertDeclara('.timeline-item', 'flex-wrap: wrap');
        $this->assertDeclara('.timeline-acciones', 'flex-basis: 100%');
        $this->assertDeclara('.timeline-acciones', 'justify-content: flex-end');

        // Filtro en columna: buscador a lo ancho, atajos y panel (A55); atajos que se reparten el ancho (A69).
        $this->assertDeclara('.filtro-citas', 'flex-direction: column');
        $this->assertDeclara('.filtro-citas', 'align-items: stretch');
        $this->assertDeclara('.filtro-buscar', 'flex: none');
        $this->assertDeclara('.filtro-buscar', 'min-width: 0');
        foreach (['order: 0', 'margin-left: 0', 'flex-wrap: wrap', 'gap: var(--space-3)'] as $d) {
            $this->assertDeclara('.filtro-atajos', $d);
        }
        $this->assertDeclara('.filtro-atajos .btn', 'flex: 1');
        foreach (['.filtro-fecha', '.filtro-dentista', '.filtro-estado'] as $campo) {
            $this->assertDeclara($campo, 'min-width: 0');
        }
        $this->assertDeclara('.filtro-tarjeta', 'padding: var(--space-5)');
        $this->assertDeclara('.filtro-panel', 'display: grid');
        $this->assertDeclara('.filtro-panel', 'grid-template-columns: minmax(0, 1fr) minmax(0, 1fr)');
        $this->assertDeclara('.filtro-panel', 'gap: var(--space-4)');
        $this->assertDeclara('.filtro-panel .filtro-dentista', 'grid-column: 1 / -1');
        $this->assertDeclara('.filtro-panel .filtro-estado', 'grid-column: 1 / -1');
        // Con JS, «Filtros» se muestra y el panel se pliega mientras el formulario no esté abierto.
        $this->assertDeclara('html.filtros-js .filtro-btn', 'display: inline-flex');
        $this->assertDeclara('html.filtros-js .filtro-citas:not(.abierto) .filtro-panel', 'display: none');

        // Detalle en una columna (D7, A54).
        $this->assertDeclara('.detalle-cita', 'display: flex');
        $this->assertDeclara('.detalle-cita', 'flex-direction: column');
        $this->assertDeclara('.detalle-cita', 'gap: var(--space-detalle)');
        $this->assertDeclara('.detalle-control', 'margin-top: 0');
        $this->assertDeclara('.detalle-historial', 'margin-top: 0');
        $this->assertDeclara('.detalle-datos-rejilla', 'grid-template-columns: minmax(0, 1fr)');

        // Textos de datos en filas o rejillas flexibles (D8, A34, A56).
        foreach (['.welcome-title', '.timeline-title', '.timeline-sub', '.resumen-cita dd'] as $texto) {
            $this->assertDeclara($texto, 'overflow-wrap: anywhere');
        }
    }

    /** D9 y D10: botón «Menú» solo con icono y logo con máscara. */
    public function test_movil_css_cambia_el_boton_menu_y_la_marca(): void
    {
        $this->assertDeclara('.menu-btn', 'width: var(--touch-min)');
        $this->assertDeclara('.menu-btn', 'height: var(--touch-min)');
        $this->assertDeclara('.menu-btn', 'padding: 0');
        foreach (['width: var(--logo-max-w)', 'height: var(--logo-movil-h)', 'background: var(--p600)',
            'mask: url("../images/logo-icon.png") left center / contain no-repeat',
            '-webkit-mask: url("../images/logo-icon.png") left center / contain no-repeat'] as $d) {
            $this->assertDeclara('.sidebar-marca', $d);
        }
    }

    /** Bloque 034 de app.css: mismas medidas que los style="" de hoy, solo con tokens (D6, D7, D10b). */
    public function test_el_bloque_034_de_app_css_reproduce_los_estilos_con_tokens(): void
    {
        // Control positivo: literales() encuentra lo que debe.
        $this->assertCount(3, $this->literales('margin-bottom: 20px; color: #fff; font-weight: 600;'));
        foreach ($this->analizarCss($this->bloque034()) as [$selector, $cuerpo, $condiciones]) {
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
            $this->assertSame([], $condiciones, "{$selector}: fuera de toda media query.");
        }

        $esperadas = [
            '.stats-grid-2' => ['grid-template-columns: repeat(2, 1fr)'],
            '.stats-grid-3' => ['grid-template-columns: repeat(3, 1fr)'],
            '.filtro-citas' => ['display: flex', 'flex-wrap: wrap', 'gap: var(--space-4)', 'align-items: flex-end'],
            '.filtro-tarjeta' => ['margin-bottom: var(--space-7)', 'padding: var(--space-6) var(--space-7)'],
            '.filtro-buscar' => ['flex: 1', 'min-width: var(--filtro-buscar-min)'],
            '.filtro-fecha' => ['min-width: var(--filtro-fecha-min)'],
            '.filtro-dentista' => ['min-width: var(--filtro-dentista-min)'],
            '.filtro-estado' => ['min-width: var(--filtro-estado-min)'],
            '.filtro-atajos' => ['margin-left: auto', 'display: flex', 'gap: var(--space-2)', 'order: 1'],
            '.filtro-citas .field' => ['border-color: var(--border-control)'],
            '.filtro-btn' => ['border-color: var(--border-control)'],
            '.filtro-btn[aria-expanded="true"]' => ['background: var(--p100)', 'color: var(--p600)'],
            '.filtro-contador' => ['background: var(--p500)', 'color: var(--white)', 'border-radius: var(--radius-lg)', 'padding: 0 var(--space-2)', 'font-size: var(--text-small)'],
            '.fila-hoy' => ['background: var(--p50)'],
            '.btn-icono' => ['padding: var(--space-1) var(--space-3)'],
            '.timeline-acciones' => ['display: flex', 'align-items: center', 'gap: var(--space-3)'],
            '.detalle-datos-rejilla' => ['grid-template-columns: repeat(3, 1fr)'],
            '.detalle-cita' => ['display: grid', 'grid-template-columns: 1fr var(--detalle-lateral-w)', 'grid-template-rows: auto auto auto 1fr',
                'column-gap: var(--space-7)', 'align-items: start'],
            '.detalle-lateral' => ['grid-column: 2', 'grid-row: 1 / -1', 'display: flex', 'flex-direction: column', 'gap: var(--space-detalle)'],
            '.detalle-control' => ['margin-top: var(--space-detalle)'],
            '.detalle-historial' => ['margin-top: var(--space-detalle)'],
        ];
        foreach ($esperadas as $selector => $declaraciones) {
            $cuerpo = $this->app034($selector);
            $this->assertNotSame('', $cuerpo, "Falta {$selector} en el bloque /* Spec 034 */ de app.css.");
            foreach ($declaraciones as $declaracion) {
                [$propiedad, $valor] = array_map('trim', explode(':', $declaracion, 2));
                $this->assertMatchesRegularExpression('/(^|;)\s*'.preg_quote($propiedad, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/', $cuerpo,
                    "{$selector} declara «{$declaracion}» en el bloque 034.");
            }
        }
        $this->assertStringNotContainsString('row-gap', $this->app034('.detalle-cita'), 'La rejilla del detalle, sin row-gap (A9).');
        $this->assertStringNotContainsString(' gap:', ' '.$this->app034('.detalle-cita'), 'La rejilla del detalle, sin gap (A9).');

        // Foco con los tokens del sistema en todo lo que la spec añade o cambia (A60, A68).
        foreach (['.filtro-citas .field:focus-visible', '.filtro-citas .btn:focus-visible', '.tabla-tarjetas a:focus-visible',
            '.tabla-tarjetas .btn:focus-visible', '.btn-icono:focus-visible'] as $foco) {
            $cuerpo = $this->app034($foco);
            $this->assertStringContainsString('outline: var(--focus-ring-width) solid var(--focus-ring);', $cuerpo, "{$foco}: anillo del sistema.");
            $this->assertStringContainsString('outline-offset: var(--focus-ring-offset);', $cuerpo, "{$foco}: separación del sistema.");
        }
    }

    public function test_los_tokens_034_coinciden_con_el_sistema(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);
        $tokens = [
            '--tarjeta-etiqueta-w' => '88px', '--logo-movil-h' => '40px', '--space-detalle' => '18px', '--detalle-lateral-w' => '360px',
            '--filtro-buscar-min' => '180px', '--filtro-fecha-min' => '130px', '--filtro-dentista-min' => '150px', '--filtro-estado-min' => '140px',
        ];
        foreach ($tokens as $token => $valor) {
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'`[^\n]{0,8}'.preg_quote($valor, '/').'/', $sistema, "system.md documenta {$token} = {$valor}.");
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/', $root[1] ?? '', "app.css define {$token}: {$valor}.");
        }
    }

    public function test_los_pares_nuevos_cumplen_aa(): void
    {
        $t = $this->tokensDeColor();
        foreach ([
            ['--p600', '--white', 4.5, 'Etiqueta de la tarjeta'],
            ['--white', '--p500', 4.5, 'Contador de «Filtros»'],
            ['--p600', '--p100', 4.5, '«Filtros» desplegado'],
            ['--p600', '--white', 4.5, '«Filtros» plegado (btn-outline)'],
            ['--border-control', '--white', 3.0, 'Borde de «Filtros» y de los campos, en los dos modos (A36, A58)'],
            ['--focus-ring', '--white', 3.0, 'Anillo de foco'],
            ['--p600', '--white', 3.0, 'Logo del menú (máscara en --p600)'],
        ] as [$color, $fondo, $minimo, $uso]) {
            $this->assertArrayHasKey($color, $t, "{$uso}: existe {$color}.");
            $this->assertGreaterThanOrEqual($minimo, $this->contraste($t[$color], $t[$fondo]), "{$uso}: {$color} sobre {$fondo}.");
        }
    }

    /**
     * Solo comprueba el texto del contrato de `filtros.js` (plan, «Script filtros.js»): una lógica
     * rota con las mismas palabras seguiría pasando. El comportamiento (plegado al cargar,
     * despliegue, `aria-expanded`, sin JavaScript) lo mide `capturar.mjs --medir`.
     */
    public function test_filtros_js_cumple_su_contrato(): void
    {
        $archivo = public_path('js/filtros.js');
        $this->assertFileExists($archivo);
        $js = file_get_contents($archivo);

        $this->assertMatchesRegularExpression('/document\.documentElement\.classList\.add\(\s*[\'"]filtros-js[\'"]\s*\)/', $js, 'Marca html.filtros-js al cargar.');
        $this->assertMatchesRegularExpression("/addEventListener\\(\\s*'DOMContentLoaded'/", $js, 'Espera a DOMContentLoaded.');
        $this->assertMatchesRegularExpression("/querySelector\\(\\s*'\\.filtro-citas'\\s*\\)/", $js, 'Busca .filtro-citas.');
        $this->assertMatchesRegularExpression('/if\s*\(\s*!\s*\w+\s*\)\s*return/', $js, 'Sale si no hay filtro.');
        foreach (["'.filtro-btn'", "'click'", "'abierto'", "'aria-expanded'", 'classList.toggle('] as $pieza) {
            $this->assertStringContainsString($pieza, $js, "filtros.js usa {$pieza}.");
        }
        foreach (['innerHTML', 'outerHTML', 'insertAdjacentHTML', 'eval(', 'new Function', 'max-width'] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $js, "filtros.js no usa {$prohibido}.");
        }
    }

    /**
     * Pantallas con tabla incluida: [usuario, url, tipo de D2].
     *
     * @return array<int, array{0: Usuario, 1: string, 2: string}>
     */
    private function pantallas(): array
    {
        return [
            [$this->paciente1, '/cliente/citas', 'mis-citas'],
            [$this->paciente1, '/cliente/citas?filtro=pasadas', 'mis-citas'],
            [$this->paciente1, '/cliente', 'panel-paciente'],
            [$this->admin, '/admin/citas', 'citas-personal'],
            [$this->dentista1, '/dentista/citas', 'citas-personal'],
            [$this->asistente, '/asistente/citas', 'citas-personal'],
            [$this->admin, '/admin', 'panel-personal'],
            [$this->asistente, '/asistente', 'panel-personal'],
            [$this->admin, '/admin/pacientes', 'pacientes'],
            [$this->dentista1, '/dentista/pacientes', 'pacientes'],
            [$this->asistente, '/asistente/pacientes', 'pacientes'],
            [$this->admin, '/admin/servicios', 'servicios'],
        ];
    }

    /** @return array<int, array{0: string, 1: DOMElement}> texto y elemento de cada `<th>` */
    private function cabeceras(DOMXPath $xpath, DOMElement $tabla): array
    {
        $cabeceras = [];
        foreach ($xpath->query('./thead/tr/th', $tabla) as $th) {
            $cabeceras[] = [trim(preg_replace('/\s+/', ' ', $th->textContent)), $th];
        }

        return $cabeceras;
    }

    /** Filas del cuerpo con datos (sin la del estado vacío, que ocupa todas las columnas). @return DOMElement[] */
    private function filas(DOMXPath $xpath, DOMElement $tabla): array
    {
        return array_values(array_filter(
            iterator_to_array($xpath->query('./tbody/tr', $tabla)),
            fn (DOMElement $tr) => $xpath->query('./td[@colspan]', $tr)->length === 0
        ));
    }

    private function filaDeLaCita(DOMXPath $xpath, string $cita): DOMElement
    {
        $fila = $xpath->query("//table/tbody/tr[.//a[contains(@href, '/citas/{$cita}')]]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $fila, "La lista tiene la fila de la cita {$cita}.");

        return $fila;
    }

    /** @return string[] */
    private function clases(DOMElement $elemento): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($elemento->getAttribute('class')))));
    }

    private function columnasEnLinea(string $estilo): bool
    {
        return (bool) preg_match('/(^|;)\s*grid-template-columns\s*:/', $estilo);
    }

    /** Cuerpo de las reglas de `movil.css` con ese selector (dentro de una lista) y esas condiciones. */
    private function movil(string $selector, array $condiciones = self::MOVIL): string
    {
        $this->assertFileExists(public_path('css/movil.css'));

        return $this->cuerpos($this->reglasCss('css/movil.css'), $selector, $condiciones);
    }

    private function assertDeclara(string $selector, string $declaracion, array $condiciones = self::MOVIL): void
    {
        [$propiedad, $valor] = array_map('trim', explode(':', $declaracion, 2));
        $this->assertMatchesRegularExpression(
            '/(^|;)\s*'.preg_quote($propiedad, '/').'\s*:\s*'.preg_quote($valor, '/').'\s*;/',
            $this->movil($selector, $condiciones),
            "{$selector} debe declarar «{$declaracion}» en movil.css."
        );
    }

    /** Cuerpo de las reglas globales del bloque `/* Spec 034 *\/` de app.css con ese selector. */
    private function app034(string $selector): string
    {
        return $this->cuerpos($this->analizarCss($this->bloque034()), $selector, []);
    }

    private function cuerpos(array $reglas, string $selector, array $condiciones): string
    {
        $cuerpos = [];
        foreach ($reglas as [$selectores, $cuerpo, $suyas]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if ($suyas === $condiciones && in_array($selector, $lista, true)) {
                $cuerpos[] = trim(preg_replace('/\s+/', ' ', $cuerpo));
            }
        }

        return implode(' ', $cuerpos);
    }

    /** Bloque `/* Spec 034 *\/` de app.css, del comentario al final del archivo. */
    private function bloque034(): string
    {
        $css = file_get_contents(public_path('css/app.css'));
        $inicio = strpos($css, '/* Spec 034');
        $this->assertNotFalse($inicio, 'app.css debe tener el bloque /* Spec 034 */.');

        return substr($css, $inicio);
    }

    /** Declaraciones de maquetación que D10b saca de los style="" del filtro (A33). */
    private function estiloDeMaqueta(string $estilo): bool
    {
        return (bool) preg_match('/(^|;)\s*(min-width|flex|margin(-[a-z]+)?|padding|display|gap)\s*:/', $estilo);
    }

    private function crearServicio(): void
    {
        DB::table('servicios')->insert([
            'id' => (string) Str::uuid(),
            'nombre' => 'Servicio móvil '.Str::random(6),
            'descripcion' => 'Fila creada por MovilPersonalTest.',
            'categoria' => 'Preventiva',
            'duracion_min' => 30,
            'precio' => 500.00,
            'color_agenda' => '#ED93B1',
            'activo' => 1,
        ]);
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
