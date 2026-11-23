<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\Feature\Concerns\ReglasCss;
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
    use ReglasCss;

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

    /** CA15 (/review R5): quien no puede reasignar ve el doctor como texto fijo, con un campo oculto. */
    public function test_el_doctor_es_fijo_para_quien_no_puede_reasignar(): void
    {
        $x = $this->xpath($this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p3}/edit")->assertOk()->getContent());
        $this->assertSame(0, $x->query('//select[@name="dentista_id"]')->length, 'D1 no elige doctor para P3.');
        $oculto = $x->query('//input[@type="hidden"][@name="dentista_id"]')->item(0);
        $this->assertNotNull($oculto);
        $this->assertSame($this->d2, $oculto->getAttribute('value'));
        $fijo = $x->query('//input[@id="campo-dentista_fijo"][@readonly]')->item(0);
        $this->assertNotNull($fijo, 'El doctor, en un campo de solo lectura.');
        $this->assertSame('Dora Segunda', $fijo->getAttribute('value'));
        $this->assertSame(1, $x->query('//label[@for="campo-dentista_fijo"]')->length, 'Con su etiqueta.');

        foreach ([[$this->dentista2, 'dentista'], [$this->admin, 'admin']] as [$usuario, $rol]) {
            $x = $this->xpath($this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p3}/edit")->assertOk()->getContent());
            $this->assertSame(1, $x->query('//select[@name="dentista_id"]')->length, "{$rol}: elige doctor.");
        }
    }

    /** CA8 (/review R6): el doctor obligatorio del asistente se marca como obligatorio y su ayuda se anuncia. */
    public function test_el_doctor_obligatorio_del_asistente_es_accesible(): void
    {
        $x = $this->xpath($this->actingAs($this->asistente)->get('/asistente/pacientes/crear')->assertOk()->getContent());
        $marca = $x->query('//label[@for="campo-dentista_id"]//*[contains(concat(" ", normalize-space(@class), " "), " marca-obligatorio ")]')->item(0);
        $this->assertNotNull($marca, 'La etiqueta lleva la marca de obligatorio.');
        $this->assertFalse($marca->hasAttribute('style'), 'La marca, sin style="".');
        $this->assertNotNull($x->query('//*[@id="ayuda-dentista_id"]')->item(0), 'La ayuda tiene id.');
        $this->assertSame(['ayuda-dentista_id'], $this->describe($x), 'El select anuncia la ayuda.');

        // Con un error, el select anuncia el error y la ayuda.
        $this->actingAs($this->asistente)->from('/asistente/pacientes/crear')
            ->post('/asistente/pacientes', ['nombre' => 'Sin', 'apellido' => 'Doctor', 'telefono' => '4435550123']);
        $x = $this->xpath($this->actingAs($this->asistente)->get('/asistente/pacientes/crear')->assertOk()->getContent());
        $this->assertSame(['error-dentista_id', 'ayuda-dentista_id'], $this->describe($x));
        // /review R30: el mensaje de error no repite la ayuda (el lector de pantalla lee los dos).
        $texto = fn (string $id) => trim($x->query("//*[@id=\"{$id}\"]")->item(0)?->textContent ?? '');
        $this->assertNotSame('', $texto('error-dentista_id'));
        $this->assertNotSame($texto('ayuda-dentista_id'), $texto('error-dentista_id'), 'El error dice algo distinto de la ayuda.');
    }

    /** CA6, CA7 (/review R7, R12): «Firmado por» y la ayuda del autor fijo, con regla propia y contraste AA. */
    public function test_la_firma_del_expediente_cumple_el_contraste(): void
    {
        $tokens = $this->tokensDeColor();
        foreach (['.firma-expediente', '.autor-expediente-ayuda', '.autor-expediente'] as $selector) {
            $cuerpo = $this->declaraciones($selector);
            $this->assertNotSame('', $cuerpo, "{$selector} tiene su regla en app.css.");
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
            $this->assertSame(1, preg_match('/color:\s*var\((--[\w-]+)\)/', $cuerpo, $color), "{$selector}: color con un token.");
            foreach (['--white', '--bg'] as $fondo) {
                $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens[$color[1]], $tokens[$fondo]), "{$selector}: {$color[1]} sobre {$fondo}.");
            }
        }

        $firma = $this->firma($this->xpath($this->actingAs($this->dentista1)->get("/dentista/expedientes/{$this->e2}")->getContent()));
        $this->assertStringNotContainsString('field-hint', $firma->getAttribute('class'), '«Firmado por» no es una ayuda de campo.');

        $x = $this->xpath($this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$this->p1}")->getContent());
        $ayuda = $x->query('//*[contains(concat(" ", normalize-space(@class), " "), " autor-expediente-ayuda ")]')->item(0);
        $this->assertNotNull($ayuda, 'La ayuda del autor fijo usa su clase.');
        $this->assertStringNotContainsString('field-hint', $ayuda->getAttribute('class'));
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

    /** Los ids de `aria-describedby` del select del doctor. */
    private function describe(DOMXPath $x): array
    {
        $select = $x->query('//select[@name="dentista_id"]')->item(0);
        $this->assertNotNull($select);

        return array_values(array_filter(explode(' ', $select->getAttribute('aria-describedby'))));
    }

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
