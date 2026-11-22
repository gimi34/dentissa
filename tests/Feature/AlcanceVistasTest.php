<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\TestCase;

/**
 * Spec 014: lo que cada rol ve en las pantallas según su alcance: el filtro «Doctor», el directorio vacío y el botón
 * «Editar» de la ficha (CA1–CA3); la edición del expediente solo para su autor y el autor fijo del dentista en el alta
 * (CA4, CA6, CA7). Los elementos nuevos no llevan `style=""` (P9).
 */
class AlcanceVistasTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioAlcanceClinico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenarioAlcance();
    }

    // ── T015 · pacientes ──────────────────────────────────────────────────────────────

    public function test_el_filtro_de_doctor_segun_el_rol(): void
    {
        $admin = $this->opcionesDelFiltro($this->actingAs($this->admin)->get('/admin/pacientes')->getContent());
        $this->assertContains($this->d1, $admin);
        $this->assertContains($this->d2, $admin);

        $asistente = $this->opcionesDelFiltro($this->actingAs($this->asistente)->get('/asistente/pacientes')->getContent());
        $this->assertSame([$this->d1], $asistente, 'El asistente filtra solo por sus dentistas.');

        $dentista = $this->xpath($this->actingAs($this->dentista1)->get('/dentista/pacientes')->assertOk()->getContent());
        $this->assertSame(0, $dentista->query('//select[@name="dentista_id"]')->length, 'El dentista no tiene filtro «Doctor».');
    }

    public function test_el_directorio_vacio_segun_el_rol(): void
    {
        [$sinPacientes] = $this->crearPersonal('dentista', 'Dino', 'Sinpacientes');

        $html = $this->actingAs($sinPacientes)->get('/dentista/pacientes')->assertOk()->getContent();
        $this->assertSame('No tienes pacientes asignados', $this->textoDelVacio($html));
        $this->assertStringNotContainsString('Registrar primer paciente', $html, 'Sin botón de alta.');

        // Con una búsqueda sin coincidencias, el mensaje de siempre (/analyze A18).
        $html = $this->actingAs($this->dentista1)->get('/dentista/pacientes?buscar=NadieSeLlamaAsi')->assertOk()->getContent();
        $this->assertSame('No se encontraron pacientes', $this->textoDelVacio($html));
    }

    /** Caracterización: el asistente ve la ficha sin «Editar»; admin y dentista, con él. */
    public function test_editar_en_la_ficha_segun_el_rol(): void
    {
        foreach ([[$this->admin, 'admin', true], [$this->dentista1, 'dentista', true], [$this->asistente, 'asistente', false]] as [$usuario, $rol, $conBoton]) {
            $x = $this->xpath($this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p1}")->assertOk()->getContent());
            $editar = $x->query('//a[contains(@href, "/pacientes/'.$this->p1.'/edit")]')->length;
            $this->assertSame($conBoton ? 1 : 0, $editar, "{$rol}: ".($conBoton ? 'con' : 'sin').' «Editar».');
        }
    }

    public function test_los_elementos_nuevos_no_llevan_estilos_en_linea(): void
    {
        [$sinPacientes] = $this->crearPersonal('dentista', 'Dino', 'Sinpacientes');
        $x = $this->xpath($this->actingAs($sinPacientes)->get('/dentista/pacientes')->assertOk()->getContent());

        $vacio = $x->query('//*[contains(concat(" ", normalize-space(@class), " "), " empty-state ")]')->item(0);
        $this->assertNotNull($vacio);
        $this->assertSame(0, $x->query('.//*[@style]', $vacio)->length + ($vacio->hasAttribute('style') ? 1 : 0), 'El directorio vacío sin style="".');
    }

    // ── T018 · expedientes ────────────────────────────────────────────────────────────

    /** CA6: el formulario solo a su autor y al admin; el resto lo ve en solo lectura, con quién lo firmó. */
    public function test_editar_el_expediente_solo_el_autor(): void
    {
        $casos = [
            'autor (D1 en E1)' => [$this->dentista1, 'dentista', $this->e1, true],
            'admin (E2)' => [$this->admin, 'admin', $this->e2, true],
            'otro dentista (D1 en E2)' => [$this->dentista1, 'dentista', $this->e2, false],
            'asistente (E1)' => [$this->asistente, 'asistente', $this->e1, false],
        ];

        foreach ($casos as $caso => [$usuario, $rol, $expediente, $edita]) {
            $x = $this->xpath($this->actingAs($usuario)->get("/{$rol}/expedientes/{$expediente}")->assertOk()->getContent());
            $formularios = $x->query('//form[contains(@action, "/expedientes/'.$expediente.'")]')->length;
            $firma = $this->firma($x);

            if ($edita) {
                $this->assertSame(2, $formularios, "{$caso}: edición y próxima visita.");
                $this->assertNull($firma, "{$caso}: sin «Firmado por».");
            } else {
                $this->assertSame(0, $formularios, "{$caso}: sin formularios.");
                $this->assertNotNull($firma, "{$caso}: con «Firmado por».");
                $this->assertSame('Firmado por '.$this->nombreDelDentista($expediente), trim(preg_replace('/\s+/', ' ', $firma->textContent)));
            }
        }
    }

    /** CA7: el dentista firma como él mismo (fijo); el admin elige el autor. */
    public function test_el_autor_del_expediente_segun_el_rol(): void
    {
        $x = $this->xpath($this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$this->p1}")->assertOk()->getContent());
        $this->assertSame(0, $x->query('//select[@name="dentista_id"]')->length, 'El dentista no elige autor.');
        $oculto = $x->query('//input[@type="hidden"][@name="dentista_id"]')->item(0);
        $this->assertNotNull($oculto);
        $this->assertSame($this->d1, $oculto->getAttribute('value'));
        $this->assertNotNull($this->autorFijo($x), 'Y ve su nombre como autor.');

        $x = $this->xpath($this->actingAs($this->admin)->get("/admin/expedientes/crear?paciente_id={$this->p1}")->assertOk()->getContent());
        $opciones = [];
        foreach ($x->query('//select[@name="dentista_id"]/option[@value!=""]') as $opcion) {
            $opciones[] = $opcion->getAttribute('value');
        }
        $this->assertContains($this->d1, $opciones);
        $this->assertContains($this->d2, $opciones);
    }

    public function test_las_piezas_del_expediente_no_llevan_estilos_en_linea(): void
    {
        $firma = $this->firma($this->xpath($this->actingAs($this->dentista1)->get("/dentista/expedientes/{$this->e2}")->getContent()));
        $this->assertNotNull($firma);
        $this->assertFalse($firma->hasAttribute('style'), '«Firmado por» sin style="".');

        $autor = $this->autorFijo($this->xpath($this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$this->p1}")->getContent()));
        $this->assertNotNull($autor);
        $this->assertFalse($autor->hasAttribute('style'), 'El autor fijo sin style="".');
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    private function firma(DOMXPath $x): ?\DOMElement
    {
        return $x->query('//*[contains(concat(" ", normalize-space(@class), " "), " firma-expediente ")]')->item(0);
    }

    private function autorFijo(DOMXPath $x): ?\DOMElement
    {
        return $x->query('//*[contains(concat(" ", normalize-space(@class), " "), " autor-expediente ")]')->item(0);
    }

    private function nombreDelDentista(string $expediente): string
    {
        $fila = DB::table('expedientes')->join('personal', 'personal.id', '=', 'expedientes.dentista_id')
            ->join('usuarios', 'usuarios.id', '=', 'personal.usuario_id')
            ->where('expedientes.id', $expediente)->first(['usuarios.nombre', 'usuarios.apellido']);

        return $fila->nombre.' '.$fila->apellido;
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new DOMXPath($documento);
    }

    /** Ids de las opciones del filtro «Doctor», sin la de «Todos». */
    private function opcionesDelFiltro(string $html): array
    {
        $ids = [];
        foreach ($this->xpath($html)->query('//select[@name="dentista_id"]/option[@value!=""]') as $opcion) {
            $ids[] = $opcion->getAttribute('value');
        }

        return $ids;
    }

    private function textoDelVacio(string $html): string
    {
        $p = $this->xpath($html)->query('//*[contains(concat(" ", normalize-space(@class), " "), " empty-state ")]/p')->item(0);
        $this->assertNotNull($p, 'El directorio muestra el estado vacío.');

        return trim($p->textContent);
    }
}
