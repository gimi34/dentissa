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
 * Móvil de pacientes y expedientes del personal (spec 035): marcado de las fichas, el historial y la
 * lista de expedientes en tarjetas, el perfil compacto, las migas, las acciones y los campos (P9).
 *
 * El comportamiento en un navegador real (anchos, solapes, toque, foco, palabras largas, línea base
 * del escritorio) lo comprueba `docs/design/capturar.mjs --medir` (plan, Estrategia de pruebas).
 */
class MovilPacientesExpedientesTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss;

    private const ROLES = ['admin', 'dentista', 'asistente'];

    private string $expediente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        $this->crearEscenario();

        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['telefono' => '5550102']);
        DB::table('pacientes')->where('id', $this->p1)->update([
            'ciudad' => 'Ciudad de prueba', 'direccion' => 'Calle de prueba',
            'contacto_emergencia' => 'Contacto de prueba', 'telefono_emergencia' => '5550199',
            'tipo_sangre' => 'A+', 'alergias' => 'Alergia de prueba', 'fecha_nacimiento' => '2005-11-15',
        ]);
        $this->cita($this->d1, $this->p1, 'agendada', '2026-10-20 09:00');
        $this->cita($this->d1, $this->p1, 'completada', '2026-10-01 10:00');
        $this->expediente = $this->crearExpediente('Diagnóstico de prueba');
    }

    public function test_el_historial_de_citas_de_la_ficha_va_en_tarjetas(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p1}")->assertOk()->getContent());
            $tabla = $this->unaTabla($xpath, "{$rol} ficha");
            $this->assertTarjetas($xpath, $tabla, ['Fecha' => 'celda-cab', 'Estado' => 'celda-estado', 'Acciones' => 'celda-acciones'], "{$rol} ficha");

            $ver = $xpath->query(".//td[contains(@class,'celda-acciones')]//a", $tabla);
            $this->assertGreaterThan(0, $ver->length, "{$rol}: hay «Ver detalle».");
            foreach ($ver as $enlace) {
                $this->assertSame('Ver detalle', $enlace->getAttribute('aria-label'), "{$rol}: «Ver detalle» con aria-label.");
                $this->assertSame('Ver detalle', trim($xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' solo-movil ')]", $enlace)->item(0)?->textContent ?? ''), "{$rol}: su texto en .solo-movil.");
                $this->assertSame('true', $xpath->query(".//*[local-name()='svg']", $enlace)->item(0)?->getAttribute('aria-hidden'), "{$rol}: su icono con aria-hidden.");
            }
        }

        // Sin citas, el aviso de hoy (paciente 2 sin citas).
        $html = $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p2}")->assertOk()->getContent();
        $this->assertStringContainsString('Sin citas registradas', $html);
    }

    public function test_la_lista_de_expedientes_va_en_tarjetas_con_su_paginacion(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $this->crearExpediente("Expediente {$i}");
        }
        $xpath = $this->xpath($this->actingAs($this->dentista1)->get('/dentista/expedientes')->assertOk()->getContent());
        $tabla = $this->unaTabla($xpath, 'expedientes');
        $this->assertTarjetas($xpath, $tabla, ['Fecha' => 'celda-cab', 'Acciones' => 'celda-acciones'], 'expedientes');

        $paginacion = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' paginacion ')]");
        $this->assertSame(1, $paginacion->length, 'La paginación lleva .paginacion.');
        $this->assertSame('', $paginacion->item(0)->getAttribute('style'), '.paginacion sin style="".');
    }

    /** CA1 y CA4: las dos fichas en una columna, en el orden de la spec (D1, D2). */
    public function test_las_fichas_van_en_una_columna_en_el_orden_de_la_spec(): void
    {
        $this->assertTrue($this->columnasEnLinea('display:grid;grid-template-columns:320px 1fr'), 'Control positivo del detector.');
        $this->assertFalse($this->columnasEnLinea('display:flex;gap:16px'));

        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p1}")->assertOk()->getContent());
            $ficha = $this->unaClase($xpath, 'ficha-paciente', "{$rol} ficha del paciente");
            $this->assertFalse($this->columnasEnLinea($ficha->getAttribute('style')), "{$rol}: .ficha-paciente sin columnas en línea.");
            $hijos = $this->hijos($ficha);
            $this->assertCount(2, $hijos, "{$rol}: la ficha tiene la columna del perfil y la de expedientes y citas.");
            $this->assertSame(1, $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' perfil-identidad ')]", $hijos[0])->length, "{$rol}: primero el perfil.");
            $this->assertStringContainsString('Expedientes clínicos', $hijos[1]->textContent, "{$rol}: después los expedientes.");
            $this->assertLessThan(strpos($hijos[1]->textContent, 'Historial de citas'), strpos($hijos[1]->textContent, 'Expedientes clínicos'), "{$rol}: y después el historial.");

            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/expedientes/{$this->expediente}")->assertOk()->getContent());
            $ficha = $this->unaClase($xpath, 'ficha-expediente', "{$rol} ficha del expediente");
            $this->assertFalse($this->columnasEnLinea($ficha->getAttribute('style')), "{$rol}: .ficha-expediente sin columnas en línea.");
            $hijos = $this->hijos($ficha);
            $this->assertCount(2, $hijos, "{$rol}: el contenido y la columna lateral.");
            $cabecera = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' expediente-cabecera ')]", $hijos[0]);
            $this->assertSame(1, $cabecera->length, "{$rol}: primero el contenido, con .expediente-cabecera.");
            $this->assertSame('', $cabecera->item(0)->getAttribute('style'), "{$rol}: .expediente-cabecera sin style=\"\".");
            $this->assertSame(1, $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' texto-partible ')][contains(., 'Expediente de')]", $cabecera->item(0))->length, "{$rol}: el título con texto-partible.");
            $this->assertStringContainsString('Próxima visita', $hijos[1]->textContent, "{$rol}: después la próxima visita.");
        }
    }

    /** CA7: acciones de la barra de la ficha (D4) y de los formularios (D5). */
    public function test_las_acciones_de_la_barra_y_de_los_formularios(): void
    {
        foreach (['admin' => 2, 'dentista' => 2, 'asistente' => 1] as $rol => $enlaces) {
            $xpath = $this->xpath($this->actingAs($this->usuarios()[$rol])->get("/{$rol}/pacientes/{$this->p1}")->assertOk()->getContent());
            $acciones = $xpath->query("//header[contains(@class,'topbar')]//*[contains(concat(' ', normalize-space(@class), ' '), ' acciones-ficha ')]");
            $this->assertSame(1, $acciones->length, "{$rol}: .acciones-ficha en la barra.");
            $this->assertSame($enlaces, $xpath->query('./a', $acciones->item(0))->length, "{$rol}: {$enlaces} acciones en la fila.");
        }

        $formularios = [
            [$this->admin, '/admin/pacientes/crear'],
            [$this->asistente, '/asistente/pacientes/crear'],
            [$this->admin, "/admin/pacientes/{$this->p1}/edit"],
            [$this->dentista1, "/dentista/pacientes/{$this->p1}/edit"],
            [$this->admin, "/admin/expedientes/crear?paciente_id={$this->p1}"],
            [$this->dentista1, "/dentista/expedientes/{$this->expediente}"],
        ];
        foreach ($formularios as [$usuario, $url]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            $fila = $xpath->query("//form//*[contains(concat(' ', normalize-space(@class), ' '), ' acciones-formulario ')]");
            $this->assertGreaterThan(0, $fila->length, "{$url}: .acciones-formulario.");
            $this->assertSame('', $fila->item(0)->getAttribute('style'), "{$url}: .acciones-formulario sin style=\"\".");
        }
    }

    /** CA8 (034/R8): textos de filas y rejillas flexibles con texto-partible y el correo entero en el móvil (D6). */
    public function test_los_textos_largos_de_la_ficha_parten(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', '2026-10-22 11:00');
        $xpath = $this->xpath($this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}")->assertOk()->getContent());
        $partible = fn (string $texto) => $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' texto-partible ')][contains(., '{$texto}')]")->length;

        $this->assertGreaterThan(0, $partible($this->paciente1->nombre.' '.$this->paciente1->apellido), 'El nombre del perfil parte.');
        $this->assertGreaterThan(0, $partible('Contacto de prueba'), 'El contacto de emergencia parte.');
        $this->assertGreaterThan(0, $partible('Consulta'), 'El servicio de la próxima cita parte (sin servicio, «Consulta»).');
        $this->assertGreaterThan(0, $partible('Diagnóstico de prueba'), 'La cabecera del expediente parte.');
        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' perfil-dato ')]") as $dato) {
            $this->assertContains('texto-partible', $this->clases($dato), 'Cada dato de contacto parte.');
        }

        $correo = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' dato-recortado ')][contains(., '{$this->paciente1->email}')]");
        $this->assertSame(1, $correo->length, 'El correo lleva .dato-recortado.');
        $this->assertStringNotContainsString('white-space', $correo->item(0)->getAttribute('style'), 'Sin white-space en línea.');
    }

    /** CA9 (D14, /analyze A4 y A25): migas con el componente de la 033. */
    public function test_las_migas_usan_el_componente_de_la_033(): void
    {
        $vistas = [
            [$this->admin, "/admin/pacientes/{$this->p1}"],
            [$this->admin, '/admin/pacientes/crear'],
            [$this->admin, "/admin/pacientes/{$this->p1}/edit"],
            [$this->dentista1, "/dentista/expedientes/{$this->expediente}"],
            [$this->admin, "/admin/expedientes/crear?paciente_id={$this->p1}"],
        ];
        foreach ($vistas as [$usuario, $url]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            $migas = $xpath->query("//div[@id='migas'][contains(concat(' ', normalize-space(@class), ' '), ' migas ')]");
            $this->assertSame(1, $migas->length, "{$url}: las migas son div#migas.migas.");
            $this->assertSame(0, $xpath->query("//*[@id='migas']/descendant-or-self::*[@style]")->length, "{$url}: migas sin style=\"\".");
            $this->assertSame(1, $xpath->query("//*[@id='migas']//*[contains(@class,'migas-actual')]")->length, "{$url}: página actual en .migas-actual.");
        }
    }

    /** CA1, CA7 (D12, /analyze A2 y A10): perfil compacto del paciente. */
    public function test_el_perfil_compacto_del_paciente(): void
    {
        foreach ($this->usuarios() as $rol => $usuario) {
            $xpath = $this->xpath($this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p1}")->assertOk()->getContent());
            $perfil = $this->unaClase($xpath, 'perfil-identidad', "{$rol} perfil");
            $this->assertStringNotContainsString('text-align', $perfil->getAttribute('style'), "{$rol}: sin text-align en línea.");

            $cab = $this->unaClase($xpath, 'perfil-cab', "{$rol} cabecera del perfil");
            $hijos = $this->hijos($cab);
            $this->assertCount(2, $hijos, "{$rol}: .perfil-cab con el avatar y un solo bloque de texto.");
            $this->assertContains('perfil-avatar', $this->clases($hijos[0]), "{$rol}: primero el avatar.");
            $this->assertSame('true', $hijos[0]->getAttribute('aria-hidden'), "{$rol}: las iniciales con aria-hidden.");
            $this->assertDoesNotMatchRegularExpression('/(^|;)\s*(width|height|font-size|font-weight)\s*:/', $hijos[0]->getAttribute('style'), "{$rol}: el avatar sin tamaño en línea.");
            $this->assertContains('perfil-texto', $this->clases($hijos[1]), "{$rol}: después .perfil-texto.");
            $nombre = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' perfil-nombre ')]", $hijos[1]);
            $this->assertSame(1, $nombre->length, "{$rol}: el nombre en .perfil-nombre dentro del bloque de texto.");
            $this->assertStringNotContainsString('font-size', $nombre->item(0)->getAttribute('style'), "{$rol}: el nombre sin tamaño en línea.");
            $this->assertStringContainsString('20 años', $hijos[1]->textContent, "{$rol}: la edad en el bloque de texto.");
            $this->assertStringContainsString('A+', $hijos[1]->textContent, "{$rol}: el tipo de sangre en el bloque de texto.");

            $etiquetas = [];
            foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' perfil-dato ')]") as $dato) {
                $this->assertDoesNotMatchRegularExpression('/(^|;)\s*display\s*:/', $dato->getAttribute('style'), "{$rol}: .perfil-dato sin display en línea.");
                $etiqueta = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' perfil-etiqueta ')][contains(concat(' ', normalize-space(@class), ' '), ' solo-movil ')]", $dato);
                $this->assertSame(1, $etiqueta->length, "{$rol}: cada dato con su etiqueta en .solo-movil.");
                $etiquetas[] = trim($etiqueta->item(0)->textContent);
                $this->assertSame('true', $xpath->query(".//*[local-name()='svg']", $dato)->item(0)?->getAttribute('aria-hidden'), "{$rol}: el icono con aria-hidden.");
            }
            $this->assertSame(['Correo', 'Teléfono', 'Ciudad', 'Doctor'], $etiquetas, "{$rol}: los cuatro datos de contacto con su etiqueta.");
        }
    }

    /** CA5: el móvil abre el teclado o el selector de cada campo (ya se cumple: comprobado rompiendo). */
    public function test_los_campos_tienen_su_tipo(): void
    {
        $tipos = [
            [$this->admin, '/admin/pacientes/crear', ['email' => 'email', 'telefono' => 'tel', 'telefono_emergencia' => 'tel', 'fecha_nacimiento' => 'date']],
            [$this->admin, "/admin/expedientes/crear?paciente_id={$this->p1}", ['proxima_visita' => 'date']],
            [$this->dentista1, "/dentista/expedientes/{$this->expediente}", ['proxima_visita' => 'date']],
        ];
        foreach ($tipos as [$usuario, $url, $campos]) {
            $xpath = $this->xpath($this->actingAs($usuario)->get($url)->assertOk()->getContent());
            foreach ($campos as $nombre => $tipo) {
                $this->assertSame($tipo, $xpath->query("//input[@name='{$nombre}']")->item(0)?->getAttribute('type'), "{$url}: «{$nombre}» de tipo {$tipo}.");
            }
        }
    }

    /** @return array<string, Usuario> */
    private function usuarios(): array
    {
        return ['admin' => $this->admin, 'dentista' => $this->dentista1, 'asistente' => $this->asistente];
    }

    private function crearExpediente(string $diagnostico): string
    {
        $id = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id' => $id, 'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'diagnostico' => $diagnostico,
            'tratamiento' => 'Tratamiento de prueba', 'creado_en' => now(),
        ]);

        return $id;
    }

    private function unaTabla(DOMXPath $xpath, string $donde): DOMElement
    {
        $tablas = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' tabla-tarjetas ')]");
        $this->assertSame(1, $tablas->length, "{$donde}: una tabla con tabla-tarjetas.");

        return $tablas->item(0);
    }

    /** Cada celda con el data-label de su cabecera y sus mismas clases; las clases de celda donde dice el plan (D3). */
    private function assertTarjetas(DOMXPath $xpath, DOMElement $tabla, array $clases, string $donde): void
    {
        $cabeceras = [];
        foreach ($xpath->query('./thead/tr/th', $tabla) as $th) {
            $cabeceras[] = [trim($th->textContent), $th];
        }
        foreach ($clases as $texto => $clase) {
            $th = collect($cabeceras)->first(fn ($c) => $c[0] === $texto);
            $this->assertNotNull($th, "{$donde}: existe la columna «{$texto}».");
            $this->assertContains($clase, $this->clases($th[1]), "{$donde}: «{$texto}» lleva {$clase}.");
        }
        $filas = $xpath->query('./tbody/tr', $tabla);
        $this->assertGreaterThan(0, $filas->length, "{$donde}: hay filas.");
        foreach ($filas as $fila) {
            $celdas = $xpath->query('./td', $fila);
            $this->assertSame(count($cabeceras), $celdas->length, "{$donde}: una celda por columna.");
            foreach ($cabeceras as $i => [$texto, $th]) {
                $this->assertSame($texto, $celdas->item($i)->getAttribute('data-label'), "{$donde}: data-label de «{$texto}».");
                $this->assertEqualsCanonicalizing($this->clases($th), $this->clases($celdas->item($i)), "{$donde}: «{$texto}» con las clases de su <th>.");
            }
        }
    }

    private function unaClase(DOMXPath $xpath, string $clase, string $donde): DOMElement
    {
        $nodos = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$clase} ')]");
        $this->assertSame(1, $nodos->length, "{$donde}: un .{$clase}.");

        return $nodos->item(0);
    }

    /** @return list<DOMElement> */
    private function hijos(DOMElement $elemento): array
    {
        return array_values(array_filter(iterator_to_array($elemento->childNodes), fn ($n) => $n instanceof DOMElement));
    }

    private function clases(DOMElement $elemento): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($elemento->getAttribute('class')))));
    }

    private function columnasEnLinea(string $estilo): bool
    {
        return (bool) preg_match('/(^|;)\s*grid-template-columns\s*:/', $estilo);
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
