<?php

namespace Tests\Feature;

use App\Auditoria\AlmacenAuditoria;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 014: directorio, ficha, edición y alta de pacientes según el alcance clínico (CA1–CA3, CA8, CA10–CA12, CA14).
 * Fuera del alcance, 404 auditado que no confirma que el paciente exista; cada lectura permitida, `paciente_leido`.
 */
class AlcancePacientesTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioAlcanceClinico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenarioAlcance();
    }

    // ── T012 · el directorio ──────────────────────────────────────────────────────────

    public function test_el_directorio_del_dentista_es_su_alcance(): void
    {
        $this->assertSame($this->ordenados([$this->p1, $this->p3]), $this->idsDelDirectorio($this->dentista1, 'dentista'));

        // Un dentista sin pacientes en su alcance ve la lista vacía.
        [$sinPacientes] = $this->crearPersonal('dentista', 'Dino', 'Sinpacientes');
        $this->assertSame([], $this->idsDelDirectorio($sinPacientes, 'dentista'));
    }

    public function test_el_directorio_del_asistente_es_el_de_sus_dentistas(): void
    {
        $this->assertSame($this->ordenados([$this->p1, $this->p3]), $this->idsDelDirectorio($this->asistente, 'asistente'));
    }

    public function test_el_admin_ve_a_todos(): void
    {
        $nuestros = [$this->p1, $this->p2, $this->p3, $this->p4, $this->p5];
        $ids = collect($nuestros)->filter(fn ($id) => in_array($id, $this->idsBuscando($this->admin, 'admin', $id), true));

        $this->assertCount(5, $ids, 'El admin encuentra a los cinco pacientes del escenario.');
    }

    /** TM12: el total y las ciudades tampoco delatan pacientes fuera del alcance. */
    public function test_el_total_y_las_ciudades_son_del_alcance(): void
    {
        DB::table('pacientes')->where('id', $this->p1)->update(['ciudad' => 'Acuitzio']);
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => 'Aguililla']);

        $respuesta = $this->actingAs($this->dentista1)->get('/dentista/pacientes')->assertOk();

        $this->assertSame(2, $respuesta->viewData('totalPacientes'));
        $this->assertSame(['Acuitzio'], $respuesta->viewData('ciudades')->values()->all());
    }

    // ── T013 · la ficha y su edición ──────────────────────────────────────────────────

    public function test_la_ficha_fuera_del_alcance_es_un_404_auditado(): void
    {
        foreach ([[$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p2}")->assertNotFound()->assertDontSee('Paula');
            $this->assertDenegado('denegado_404', 'pacientes', $this->p2, "{$rol}.pacientes.show", $usuario->id);
        }
    }

    public function test_editar_una_ficha_fuera_del_alcance_es_un_404(): void
    {
        $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p2}/edit")->assertNotFound()->assertDontSee('Paula');
        $this->assertDenegado('denegado_404', 'pacientes', $this->p2, 'dentista.pacientes.edit', $this->dentista1->id);
    }

    public function test_guardar_una_ficha_fuera_del_alcance_no_cambia_nada(): void
    {
        $this->actingAs($this->dentista1)
            ->put("/dentista/pacientes/{$this->p2}", $this->datosDeFicha(['nombre' => 'Cambiado']))->assertNotFound();

        $this->assertSame('Paula', DB::table('usuarios')->where('id', $this->paciente2->id)->value('nombre'));
        $this->assertDenegado('denegado_404', 'pacientes', $this->p2, 'dentista.pacientes.update', $this->dentista1->id);
    }

    /** TM9 y RNF: un paciente fuera del alcance y uno que no existe responden igual, también al guardar datos inválidos. */
    public function test_un_paciente_ajeno_y_uno_inexistente_responden_igual(): void
    {
        $inexistente = (string) Str::uuid();

        foreach ([$this->p2, $inexistente] as $id) {
            $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$id}")->assertNotFound()->assertDontSee('Paula');
            // Con datos inválidos: el Gate va antes de la validación (D17), así que tampoco hay 422 que los distinga.
            $this->actingAs($this->dentista1)->put("/dentista/pacientes/{$id}", ['nombre' => ''])->assertNotFound();
        }
    }

    /** Caracterización (defensa en profundidad): el asistente no edita fichas; el 403 lo da el rol de la ruta. */
    public function test_el_asistente_no_edita_fichas(): void
    {
        $this->actingAs($this->asistente)->get("/dentista/pacientes/{$this->p1}/edit")->assertForbidden();
        $this->actingAs($this->asistente)->put("/dentista/pacientes/{$this->p1}", $this->datosDeFicha(['nombre' => 'Cambiado']))->assertForbidden();
        $this->assertSame('Pablo', DB::table('usuarios')->where('id', $this->paciente1->id)->value('nombre'));
    }

    /** CA12: cada lectura permitida de la ficha queda registrada, sin datos del paciente. */
    public function test_leer_una_ficha_queda_registrado(): void
    {
        foreach ([[$this->admin, 'admin'], [$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->get("/{$rol}/pacientes/{$this->p1}")->assertOk();

            $evento = $this->ultimoEvento('paciente_leido', $this->p1);
            $this->assertNotNull($evento, "{$rol}: la lectura queda registrada.");
            $this->assertSame([$usuario->id, 'pacientes', 'exito'], [$evento->actor_id, $evento->recurso_tipo, $evento->resultado]);
            $this->assertStringNotContainsString('Pablo', (string) $evento->detalle.(string) $evento->campos, 'Sin datos del paciente.');
        }
    }

    public function test_abrir_la_edicion_de_la_ficha_queda_registrado(): void
    {
        $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p1}/edit")->assertOk();

        $evento = $this->ultimoEvento('paciente_leido', $this->p1);
        $this->assertNotNull($evento, 'Abrir la edición también es una lectura de la ficha.');
        $this->assertSame($this->dentista1->id, $evento->actor_id);
    }

    /** CA12, TM10: modo crítico; sin registro no hay lectura. */
    public function test_sin_auditoria_no_se_muestra_la_ficha(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));

        $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p1}")->assertStatus(503)->assertDontSee('Pablo');
    }

    /** CA10, D10: si el dentista reasigna al paciente fuera de su alcance, vuelve al directorio con un aviso. */
    public function test_el_dentista_que_reasigna_fuera_vuelve_al_directorio(): void
    {
        $this->actingAs($this->dentista1)
            ->put("/dentista/pacientes/{$this->p1}", $this->datosDeFicha(['dentista_id' => $this->d2]))
            ->assertRedirect(route('dentista.pacientes'))
            ->assertSessionHas('success', 'El paciente quedó asignado a otro dentista.');

        $this->assertSame($this->d2, DB::table('pacientes')->where('id', $this->p1)->value('dentista_id'));
        $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p1}")->assertNotFound();
    }

    /** D15: el doctor asignado es un dentista activo; con un asistente, el paciente quedaría fuera del alcance de todos. */
    public function test_asignar_un_asistente_como_doctor_se_rechaza(): void
    {
        $asistenteId = DB::table('personal')->where('usuario_id', $this->asistente->id)->value('id');

        $this->actingAs($this->admin)->from("/admin/pacientes/{$this->p1}/edit")
            ->put("/admin/pacientes/{$this->p1}", $this->datosDeFicha(['dentista_id' => $asistenteId]))
            ->assertSessionHasErrors('dentista_id');

        $this->assertSame($this->d1, DB::table('pacientes')->where('id', $this->p1)->value('dentista_id'));
    }

    // ── T014 · el alta del asistente y el aviso de duplicado ──────────────────────────

    public function test_el_asistente_asigna_uno_de_sus_dentistas(): void
    {
        foreach (['sin doctor' => null, 'con un doctor que no apoya' => $this->d2] as $caso => $doctor) {
            $nombre = 'Alta'.Str::random(6);
            $this->actingAs($this->asistente)->from('/asistente/pacientes/crear')
                ->post('/asistente/pacientes', $this->datosDeAlta($nombre, $doctor))
                ->assertSessionHasErrors('dentista_id');
            $this->assertSame(0, DB::table('usuarios')->where('nombre', $nombre)->count(), "{$caso}: no se crea nada.");
        }

        $nombre = 'Alta'.Str::random(6);
        $this->actingAs($this->asistente)->post('/asistente/pacientes', $this->datosDeAlta($nombre, $this->d1))->assertSessionHasNoErrors();
        $this->assertSame($this->d1, $this->dentistaDe($nombre), 'Con uno de los suyos, se crea asignado a él.');

        $nombre = 'Alta'.Str::random(6);
        $this->actingAs($this->admin)->post('/admin/pacientes', $this->datosDeAlta($nombre, null))->assertSessionHasNoErrors();
        $this->assertNull($this->dentistaDe($nombre), 'El admin puede dejarlo sin doctor.');
    }

    /** CA14, TM14: el aviso muestra el nombre del duplicado, pero solo enlaza la ficha si se puede ver. */
    public function test_el_aviso_de_duplicado_solo_enlaza_lo_que_se_puede_ver(): void
    {
        DB::table('usuarios')->where('id', $this->paciente2->id)->update(['telefono' => '4435559999']);
        $datos = ['nombre' => 'Paula', 'apellido' => 'Otra', 'telefono' => '4435559999'];

        foreach ([[$this->asistente, 'asistente', false], [$this->admin, 'admin', true]] as [$usuario, $rol, $conEnlace]) {
            $this->actingAs($usuario)->from("/{$rol}/pacientes/crear")
                ->post("/{$rol}/pacientes", array_merge($this->datosDeAlta('x', $this->d1), $datos));
            $aviso = $this->aviso($this->actingAs($usuario)->get("/{$rol}/pacientes/crear")->assertOk()->getContent());

            $this->assertStringContainsString('Paula Otra', $aviso->textContent, "{$rol}: el aviso muestra el nombre.");
            $enlaces = (new DOMXPath($aviso->ownerDocument))->query('.//a', $aviso);
            $this->assertSame($conEnlace ? 1 : 0, $enlaces->length, "{$rol}: ".($conEnlace ? 'con' : 'sin').' enlace a la ficha.');
            if ($conEnlace) {
                $this->assertSame(route('admin.pacientes.show', $this->p2), $enlaces->item(0)->getAttribute('href'));
            }
        }
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    /** Ids del escenario que salen en el directorio (página 1 basta: el alcance solo tiene pacientes del escenario). */
    private function idsDelDirectorio($usuario, string $rol): array
    {
        $ids = $this->actingAs($usuario)->get("/{$rol}/pacientes")->assertOk()->viewData('pacientes')->pluck('id')->all();

        return $this->ordenados($ids);
    }

    private function idsBuscando($usuario, string $rol, string $pacienteId): array
    {
        $nombre = DB::table('pacientes')->join('usuarios', 'usuarios.id', '=', 'pacientes.usuario_id')
            ->where('pacientes.id', $pacienteId)->value('usuarios.nombre');

        return $this->actingAs($usuario)->get("/{$rol}/pacientes?buscar=".urlencode($nombre))
            ->assertOk()->viewData('pacientes')->pluck('id')->all();
    }

    private function ordenados(array $ids): array
    {
        return collect($ids)->sort()->values()->all();
    }

    private function datosDeFicha(array $cambios = []): array
    {
        // P1 tiene correo, y quitarlo al guardar no se permite (spec 054): se envía el que ya tiene.
        return array_merge([
            'nombre' => 'Pablo', 'apellido' => 'Paciente', 'email' => $this->paciente1->email,
            'telefono' => '4435550000', 'dentista_id' => $this->d1,
        ], $cambios);
    }

    private function datosDeAlta(string $nombre, ?string $doctor): array
    {
        return array_filter([
            'nombre' => $nombre, 'apellido' => 'Alcance', 'telefono' => '44355'.random_int(10000, 99999),
            'dentista_id' => $doctor,
        ], fn ($valor) => $valor !== null);
    }

    private function dentistaDe(string $nombre): ?string
    {
        return DB::table('pacientes')->join('usuarios', 'usuarios.id', '=', 'pacientes.usuario_id')
            ->where('usuarios.nombre', $nombre)->value('pacientes.dentista_id');
    }

    private function aviso(string $html): \DOMElement
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $aviso = (new DOMXPath($documento))->query('//*[contains(concat(" ", normalize-space(@class), " "), " aviso-duplicado ")]')->item(0);
        $this->assertNotNull($aviso, 'La pantalla muestra el aviso de duplicado.');

        return $aviso;
    }
}
