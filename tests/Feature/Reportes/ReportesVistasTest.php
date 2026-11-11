<?php

namespace Tests\Feature\Reportes;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 047, CA1–CA8 y CA10 (TM4, TM5) y accesibilidad: el HTML de «Reportes».
 *
 * Fechas de 2031, sin citas del volcado (plan D12). «Hoy» es el 15 de marzo de 2031 en la clínica.
 */
class ReportesVistasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const MARZO = '/admin/reportes?desde=2031-03-01&hasta=2031-03-31';

    private const VACIO = '/admin/reportes?desde=2031-09-01&hasta=2031-09-30';

    private const TELEFONO_PACIENTE = '5550001122';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2031-03-15 18:00:00');
        $this->crearEscenario();
        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['telefono' => self::TELEFONO_PACIENTE]);

        $this->assertSame(0, DB::table('citas')->whereBetween('fecha_hora', ['2031-01-01 00:00:00', '2031-12-31 23:59:59'])->count(),
            'El periodo de prueba no tiene citas antes de sembrar.');
        $this->sembrarMarzo();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Las mismas nueve citas que `ReportesCitasTest`: seis estados, tres dentistas, dos servicios. */
    private function sembrarMarzo(): void
    {
        $limpieza = (string) Str::uuid();
        $blanqueamiento = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $limpieza, 'nombre' => 'Limpieza de prueba', 'activo' => 1]);
        DB::table('servicios')->insert(['id' => $blanqueamiento, 'nombre' => 'Blanqueamiento de prueba', 'activo' => 0]);
        [, $d3] = $this->crearPersonal('dentista', 'Ivan', 'Inactivo');

        $citas = [
            [$this->d1, 'completada', '2031-03-03', $limpieza],
            [$this->d1, 'completada', '2031-03-04', $limpieza],
            [$this->d1, 'cancelada', '2031-03-05', $limpieza],
            [$this->d1, 'no_asistio', '2031-03-06', $blanqueamiento],
            [$this->d1, 'agendada', '2031-03-20', null],
            [$this->d2, 'confirmada', '2031-03-07', $blanqueamiento],
            [$this->d2, 'en_curso', '2031-03-08', $limpieza],
            [$this->d2, 'cancelada', '2031-03-09', null],
            [$d3, 'completada', '2031-03-10', null],
        ];
        foreach ($citas as [$dentista, $estado, $dia, $servicio]) {
            DB::table('citas')->insert([
                'id' => (string) Str::uuid(),
                'paciente_id' => $this->p1,
                'dentista_id' => $dentista,
                'servicio_id' => $servicio,
                'fecha_hora' => "{$dia} 10:47:00",
                'duracion_min' => 30,
                'estado' => $estado,
                'notas_paciente' => 'Nota del paciente que no debe salir',
                'notas_internas' => 'Nota interna que no debe salir',
            ]);
        }
    }

    private function pagina(string $direccion = self::MARZO): TestResponse
    {
        return $this->actingAs($this->admin)->get($direccion);
    }

    private function xpath(TestResponse $respuesta): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function texto(?DOMNode $nodo): string
    {
        return $nodo === null ? '' : trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
    }

    private function porId(DOMXPath $xpath, string $id): ?DOMNode
    {
        return $xpath->query("//*[@id='{$id}']")->item(0);
    }

    public function test_la_pagina_muestra_los_tres_reportes_y_el_periodo(): void
    {
        $respuesta = $this->pagina()->assertOk();
        $xpath = $this->xpath($respuesta);

        $respuesta->assertDontSee('en construcción');
        $this->assertSame('Del 1 al 31 de marzo de 2031', $this->texto($this->porId($xpath, 'reporte-periodo')));

        $titulos = [];
        foreach ($xpath->query('//*[@id="reporte-estados" or @id="reporte-dentistas" or @id="reporte-servicios"]//h2') as $h2) {
            $titulos[] = $this->texto($h2);
        }
        $this->assertSame(['Citas por estado', 'Citas por dentista', 'Servicios más solicitados'], $titulos);
        $this->assertSame(1, $xpath->query('//h1')->length, 'La página tiene un solo encabezado principal.');
    }

    public function test_el_formulario_envia_el_periodo_por_la_direccion(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $formulario = $this->porId($xpath, 'reporte-filtro');
        $this->assertNotNull($formulario);
        $this->assertSame('get', strtolower($formulario->getAttribute('method')));
        $this->assertSame('/admin/reportes', $formulario->getAttribute('action'));

        foreach (['desde' => 'Desde', 'hasta' => 'Hasta'] as $nombre => $etiqueta) {
            $campo = $xpath->query("//form[@id='reporte-filtro']//input[@name='{$nombre}']")->item(0);
            $this->assertNotNull($campo, "El formulario tiene el campo {$nombre}.");
            $this->assertSame('date', $campo->getAttribute('type'));
            $label = $xpath->query("//label[@for='{$campo->getAttribute('id')}']")->item(0);
            $this->assertSame($etiqueta, $this->texto($label));
        }
    }

    public function test_el_resumen_muestra_el_total_y_las_dos_tasas_con_su_texto(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $this->assertSame('9', $this->texto($this->porId($xpath, 'resumen-total-cifra')));
        $this->assertStringContainsString('Citas en el periodo', $this->texto($this->porId($xpath, 'resumen-total')));

        $cancelacion = $this->texto($this->porId($xpath, 'resumen-cancelacion'));
        $this->assertStringContainsString('Cancelación', $cancelacion);
        $this->assertStringContainsString('22,2 %', $cancelacion);
        $this->assertStringContainsString('2 de 9 citas', $cancelacion);

        $inasistencia = $this->texto($this->porId($xpath, 'resumen-inasistencia'));
        $this->assertStringContainsString('Inasistencia', $inasistencia);
        $this->assertStringContainsString('11,1 %', $inasistencia);
        $this->assertStringContainsString('1 de 9 citas', $inasistencia);
    }

    public function test_los_porcentajes_van_con_coma_y_un_decimal(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $estados = [];
        foreach ($xpath->query('//*[@id="reporte-estados"]//tbody/tr') as $fila) {
            $celdas = $xpath->query('./th|./td', $fila);
            $estados[$this->texto($celdas->item(0))] = [$this->texto($celdas->item(1)), $this->texto($celdas->item(2))];
        }

        $this->assertSame([
            'Agendada' => ['1', '11,1 %'],
            'Confirmada' => ['1', '11,1 %'],
            'En curso' => ['1', '11,1 %'],
            'Completada' => ['3', '33,3 %'],
            'Cancelada' => ['2', '22,2 %'],
            'No asistió' => ['1', '11,1 %'],
        ], $estados);

        // Ningún porcentaje con punto decimal en toda la página.
        $this->assertDoesNotMatchRegularExpression('/\d\.\d\s*%/', $this->texto($this->porId($xpath, 'reporte-contenido')));
    }

    public function test_la_tabla_por_dentista_tiene_fila_de_totales(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $filas = [];
        foreach ($xpath->query('//*[@id="reporte-dentistas"]//tbody/tr') as $fila) {
            $filas[] = array_map(fn ($celda) => $this->texto($celda), iterator_to_array($xpath->query('./th|./td', $fila)));
        }
        $this->assertSame([
            ['Diego Primero', '5', '2', '1', '1'],
            ['Dora Segunda', '3', '0', '1', '0'],
            ['Ivan Inactivo', '1', '1', '0', '0'],
        ], $filas);

        $pie = $xpath->query('//*[@id="reporte-dentistas"]//tfoot/tr')->item(0);
        $this->assertNotNull($pie, 'La tabla por dentista tiene fila de totales.');
        $encabezado = $xpath->query('./th[@scope="row"]', $pie)->item(0);
        $this->assertSame('Total', $this->texto($encabezado));
        $this->assertSame(['9', '3', '2', '1'], array_map(fn ($celda) => $this->texto($celda), iterator_to_array($xpath->query('./td', $pie))));
    }

    public function test_la_tabla_de_servicios_muestra_sin_servicio(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $filas = [];
        foreach ($xpath->query('//*[@id="reporte-servicios"]//tbody/tr') as $fila) {
            $filas[] = array_map(fn ($celda) => $this->texto($celda), iterator_to_array($xpath->query('./th|./td', $fila)));
        }

        $this->assertSame([
            ['1', 'Limpieza de prueba', '3', '42,9 %'],
            ['2', 'Blanqueamiento de prueba', '2', '28,6 %'],
            ['3', 'Sin servicio', '2', '28,6 %'],
        ], $filas);
    }

    public function test_un_periodo_sin_citas_muestra_el_estado_vacio_y_el_filtro(): void
    {
        $respuesta = $this->pagina(self::VACIO)->assertOk();
        $xpath = $this->xpath($respuesta);

        $this->assertSame(1, substr_count($respuesta->getContent(), 'No hay citas en este periodo'), 'El mensaje aparece una sola vez.');
        $vacio = $this->porId($xpath, 'reporte-vacio');
        $this->assertNotNull($vacio);
        $this->assertSame('status', $vacio->getAttribute('role'));

        $this->assertNull($this->porId($xpath, 'reporte-resumen'), 'Sin resumen.');
        $this->assertSame(0, $xpath->query('//*[@id="reporte-contenido"]//table')->length, 'Sin tablas vacías.');
        $this->assertStringNotContainsString('%', $this->texto($this->porId($xpath, 'reporte-contenido')));

        $this->assertNotNull($this->porId($xpath, 'reporte-filtro'), 'El selector de periodo sigue disponible.');
        $this->assertSame('Del 1 al 30 de septiembre de 2031', $this->texto($this->porId($xpath, 'reporte-periodo')));
    }

    public function test_el_error_va_junto_a_su_campo_y_no_hay_cifras(): void
    {
        $respuesta = $this->actingAs($this->admin)->followingRedirects()
            ->get('/admin/reportes?desde=2031-03-10&hasta=2031-03-09')->assertOk();
        $xpath = $this->xpath($respuesta);

        $aviso = $xpath->query('//*[@id="reporte-contenido"]//*[@role="alert"]')->item(0);
        $this->assertNotNull($aviso, 'Hay un aviso que se anuncia.');

        $hasta = $xpath->query('//input[@name="hasta"]')->item(0);
        $this->assertSame('true', $hasta->getAttribute('aria-invalid'));
        $error = $this->porId($xpath, $hasta->getAttribute('aria-describedby'));
        $this->assertSame('«Hasta» no puede ser anterior a «Desde».', $this->texto($error));
        $this->assertSame('2031-03-09', $hasta->getAttribute('value'), 'El campo conserva lo escrito.');

        $desde = $xpath->query('//input[@name="desde"]')->item(0);
        $this->assertSame('', $desde->getAttribute('aria-invalid'), 'El campo correcto no se marca.');

        // Sin cifras: ni las del periodo pedido ni las del mes en curso.
        $this->assertNull($this->porId($xpath, 'reporte-resumen'));
        $this->assertNull($this->porId($xpath, 'reporte-periodo'));
        $this->assertNull($this->porId($xpath, 'reporte-vacio'));
        $this->assertSame(0, $xpath->query('//*[@id="reporte-contenido"]//table')->length);
    }

    public function test_el_valor_rechazado_vuelve_escapado(): void
    {
        $ataque = '"><script>alert(1)</script>';

        $respuesta = $this->actingAs($this->admin)->followingRedirects()
            ->get('/admin/reportes?'.http_build_query(['desde' => $ataque, 'hasta' => '2031-03-31']))->assertOk();

        $this->assertStringNotContainsString('<script>alert(1)', $respuesta->getContent());
        $desde = $this->xpath($respuesta)->query('//input[@name="desde"]')->item(0);
        $this->assertSame($ataque, $desde->getAttribute('value'), 'El valor vuelve como texto del campo.');
    }

    public function test_un_valor_rechazado_que_es_una_lista_no_rompe_la_pagina(): void
    {
        $respuesta = $this->actingAs($this->admin)->followingRedirects()
            ->get('/admin/reportes?'.http_build_query(['desde' => ['2031-03-01'], 'hasta' => '2031-03-31']))->assertOk();

        $desde = $this->xpath($respuesta)->query('//input[@name="desde"]')->item(0);
        $this->assertSame('', $desde->getAttribute('value'), 'En el campo solo se repone texto.');
    }

    public function test_la_cabecera_impresa_lleva_clinica_periodo_y_fecha(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $cabecera = $this->porId($xpath, 'reporte-cabecera-impresa');
        $this->assertNotNull($cabecera);
        $this->assertContains('solo-impresion', explode(' ', $cabecera->getAttribute('class')));

        $this->assertSame('Melissa López N. · Odontología integral', config('clinica.nombre'));
        $texto = $this->texto($cabecera);
        $this->assertStringContainsString('Melissa López N. · Odontología integral', $texto);
        $this->assertStringContainsString('Reportes', $texto);
        $this->assertStringContainsString('Del 1 al 31 de marzo de 2031', $texto);
        $this->assertStringContainsString('Generado el 15 de marzo de 2031', $texto);
        $this->assertSame(0, $xpath->query('.//img|.//svg', $cabecera)->length, 'La cabecera impresa es solo texto.');
    }

    public function test_el_boton_imprimir_nace_oculto_y_sin_codigo_en_linea(): void
    {
        $respuesta = $this->pagina()->assertOk();
        $xpath = $this->xpath($respuesta);

        $boton = $this->porId($xpath, 'reporte-imprimir');
        $this->assertNotNull($boton);
        $this->assertTrue($boton->hasAttribute('hidden'), 'Sin JavaScript, el botón no se muestra.');
        $this->assertSame('button', $boton->getAttribute('type'));
        $this->assertFalse($boton->hasAttribute('onclick'));

        $script = $xpath->query('//script[contains(@src, "js/reportes.js")]')->item(0);
        $this->assertNotNull($script, 'La página enlaza reportes.js.');
        $this->assertTrue($script->hasAttribute('defer'));
        $this->assertSame(0, $xpath->query('//*[@id="reporte-contenido"]//script[not(@src)]')->length);
    }

    public function test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta(): void
    {
        $respuesta = $this->pagina()->assertOk();
        $contenido = $this->texto($this->porId($this->xpath($respuesta), 'reporte-contenido'));

        // Control positivo: las cifras y los nombres de dentistas y servicios sí están.
        $this->assertStringContainsString('Diego Primero', $contenido);
        $this->assertStringContainsString('Limpieza de prueba', $contenido);
        $this->assertStringContainsString('Citas en el periodo', $contenido);

        // Nada del paciente ni de una cita concreta, en toda la respuesta.
        $respuesta->assertDontSee('Pablo');
        $respuesta->assertDontSee($this->paciente1->email);
        $respuesta->assertDontSee(self::TELEFONO_PACIENTE);
        $respuesta->assertDontSee('Nota del paciente que no debe salir');
        $respuesta->assertDontSee('Nota interna que no debe salir');
        $respuesta->assertDontSee('10:47');
        $respuesta->assertDontSee('2031-03-06');
    }

    public function test_las_tablas_tienen_titulo_y_encabezados_de_fila_y_columna(): void
    {
        $xpath = $this->xpath($this->pagina()->assertOk());

        $tablas = $xpath->query('//*[@id="reporte-contenido"]//table');
        $this->assertSame(3, $tablas->length);

        foreach ($tablas as $tabla) {
            $titulo = $this->texto($xpath->query('./caption', $tabla)->item(0));
            $this->assertNotSame('', $titulo, 'Cada tabla tiene título.');
            $this->assertContains('tabla-tarjetas', explode(' ', $tabla->getAttribute('class')), "{$titulo}: pasa a tarjetas en el móvil.");

            $columnas = $xpath->query('./thead/tr/th', $tabla);
            $this->assertGreaterThanOrEqual(3, $columnas->length, $titulo);
            foreach ($columnas as $th) {
                $this->assertSame('col', $th->getAttribute('scope'), $titulo);
            }

            foreach ($xpath->query('./tbody/tr|./tfoot/tr', $tabla) as $fila) {
                $this->assertSame(1, $xpath->query('./th[@scope="row"]', $fila)->length, "{$titulo}: cada fila tiene su encabezado.");
                // En tarjetas, cada cifra lleva su etiqueta.
                foreach ($xpath->query('./td', $fila) as $celda) {
                    $this->assertNotSame('', $celda->getAttribute('data-label'), "{$titulo}: cada cifra lleva su etiqueta.");
                }
            }
        }
    }
}
