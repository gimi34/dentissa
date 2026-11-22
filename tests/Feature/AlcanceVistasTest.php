<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\TestCase;

/**
 * Spec 014: lo que cada rol ve en las pantallas según su alcance (CA1–CA3): el filtro «Doctor», el directorio vacío
 * y el botón «Editar» de la ficha. Los elementos nuevos no llevan `style=""` (P9).
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

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

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
