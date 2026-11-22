<?php

namespace Tests\Feature;

use App\Auditoria\AlmacenAuditoria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 014: expedientes según el alcance clínico (CA4–CA7, CA11, CA12). El personal del alcance lee todos los del
 * paciente; solo su autor (o el admin) los modifica; fuera del alcance, 404 auditado. Cada lectura, `expediente_leido`.
 */
class AlcanceExpedientesTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioAlcanceClinico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenarioAlcance();
    }

    // ── T016 · leer y modificar ───────────────────────────────────────────────────────

    /** Caracterización: el alcance es del paciente, no del autor. */
    public function test_el_dentista_lee_el_expediente_de_otro_autor(): void
    {
        $this->actingAs($this->dentista1)->get("/dentista/expedientes/{$this->e2}")->assertOk()->assertSee('Diagnóstico de E2');
    }

    /** Caracterización: la ficha lista los expedientes del paciente, los firme quien los firme. */
    public function test_la_ficha_lista_expedientes_de_otros_autores(): void
    {
        $this->actingAs($this->dentista1)->get("/dentista/pacientes/{$this->p1}")->assertOk()
            ->assertSee(route('dentista.expedientes.show', $this->e1), false)
            ->assertSee(route('dentista.expedientes.show', $this->e2), false);
    }

    public function test_el_asistente_lee_en_solo_lectura(): void
    {
        $this->actingAs($this->asistente)->get("/asistente/expedientes/{$this->e1}")->assertOk()
            ->assertSee('Diagnóstico de E1')
            ->assertDontSee('expedientes/'.$this->e1.'" method="POST"', false);
    }

    public function test_el_expediente_fuera_del_alcance_es_un_404_auditado(): void
    {
        foreach ([[$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->get("/{$rol}/expedientes/{$this->e3}")->assertNotFound()->assertDontSee('Diagnóstico de E3');
            $this->assertDenegado('denegado_404', 'expedientes', $this->e3, "{$rol}.expedientes.show", $usuario->id);
        }
    }

    public function test_modificar_el_expediente_de_otro_autor_se_rechaza(): void
    {
        $this->actingAs($this->dentista1)->put("/dentista/expedientes/{$this->e2}", ['diagnostico' => 'Cambiado'])->assertForbidden();

        $this->assertSame('Diagnóstico de E2', $this->diagnosticoDe($this->e2));
        $this->assertDenegado('denegado', 'expedientes', $this->e2, 'dentista.expedientes.update', $this->dentista1->id);
    }

    /** Caracterización. */
    public function test_el_autor_modifica_su_expediente(): void
    {
        $this->actingAs($this->dentista1)->put("/dentista/expedientes/{$this->e1}", ['diagnostico' => 'Cambiado por D1'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Cambiado por D1', $this->diagnosticoDe($this->e1));
    }

    /** Caracterización. */
    public function test_el_admin_modifica_cualquier_expediente(): void
    {
        $this->actingAs($this->admin)->put("/admin/expedientes/{$this->e2}", ['diagnostico' => 'Cambiado por admin'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Cambiado por admin', $this->diagnosticoDe($this->e2));
    }

    /** Caracterización (defensa en profundidad): el 403 lo da el rol de la ruta, y detrás el Gate. */
    public function test_el_asistente_no_modifica_expedientes(): void
    {
        $this->actingAs($this->asistente)->put("/dentista/expedientes/{$this->e1}", ['diagnostico' => 'Cambiado'])->assertForbidden();

        $this->assertSame('Diagnóstico de E1', $this->diagnosticoDe($this->e1));
    }

    /** CA12: cada lectura permitida queda registrada, sin datos clínicos. */
    public function test_leer_un_expediente_queda_registrado(): void
    {
        foreach ([[$this->admin, 'admin'], [$this->dentista1, 'dentista'], [$this->asistente, 'asistente']] as [$usuario, $rol]) {
            $this->actingAs($usuario)->get("/{$rol}/expedientes/{$this->e1}")->assertOk();

            $evento = $this->ultimoEvento('expediente_leido', $this->e1);
            $this->assertNotNull($evento, "{$rol}: la lectura queda registrada.");
            $this->assertSame([$usuario->id, 'expedientes', 'exito'], [$evento->actor_id, $evento->recurso_tipo, $evento->resultado]);
            $this->assertStringNotContainsString('Diagnóstico', (string) $evento->detalle.(string) $evento->campos, 'Sin datos clínicos.');
        }
    }

    /** CA12, TM10: sin registro no hay lectura. */
    public function test_sin_auditoria_no_se_muestra_el_expediente(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));

        $this->actingAs($this->dentista1)->get("/dentista/expedientes/{$this->e1}")->assertStatus(503)->assertDontSee('Diagnóstico de E1');
    }

    /** TM9, D17: el Gate va antes de validar; un expediente ajeno y uno inexistente no se distinguen. */
    public function test_un_expediente_ajeno_y_uno_inexistente_responden_igual(): void
    {
        foreach ([$this->e3, (string) Str::uuid()] as $id) {
            $this->actingAs($this->dentista1)->get("/dentista/expedientes/{$id}")->assertNotFound();
            $this->actingAs($this->dentista1)->put("/dentista/expedientes/{$id}", ['diagnostico' => ''])->assertNotFound();
        }
    }

    /** D7: el índice del dentista, los que firmó de pacientes de su alcance. */
    public function test_el_indice_del_dentista_son_los_que_firmo_de_su_alcance(): void
    {
        $e4 = $this->expediente($this->p2, $this->d1, 'Diagnóstico de E4'); // firmado por D1, de un paciente que ya no es suyo

        $ids = $this->actingAs($this->dentista1)->get('/dentista/expedientes')->assertOk()->viewData('expedientes')->pluck('id')->all();

        $this->assertContains($this->e1, $ids);
        foreach (['E2 (otro autor)' => $this->e2, 'E3 (fuera del alcance)' => $this->e3, 'E4 (suyo, fuera del alcance)' => $e4] as $caso => $id) {
            $this->assertNotContains($id, $ids, "Sin {$caso}.");
        }
    }

    // ── T017 · el alta ────────────────────────────────────────────────────────────────

    public function test_abrir_el_alta_fuera_del_alcance_es_un_404(): void
    {
        $this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$this->p2}")->assertNotFound()->assertDontSee('Paula');
        $this->assertDenegado('denegado_404', 'pacientes', $this->p2, 'dentista.expedientes.create', $this->dentista1->id);
    }

    /** Caracterización. */
    public function test_el_alta_de_expediente_firma_el_propio_dentista(): void
    {
        $this->actingAs($this->dentista1)->post('/dentista/expedientes', $this->alta($this->p1, $this->d1))
            ->assertRedirect(route('dentista.pacientes.show', $this->p1))->assertSessionHasNoErrors();

        $this->assertSame($this->d1, DB::table('expedientes')->where('diagnostico', 'Alta 014')->value('dentista_id'));
    }

    public function test_el_alta_con_otro_autor_se_rechaza(): void
    {
        $this->actingAs($this->dentista1)->post('/dentista/expedientes', $this->alta($this->p1, $this->d2))->assertForbidden();

        $this->assertSame(0, DB::table('expedientes')->where('diagnostico', 'Alta 014')->count());
        $this->assertDenegado('denegado', 'pacientes', $this->p1, 'dentista.expedientes.store', $this->dentista1->id);
    }

    public function test_el_alta_con_una_cita_de_otro_paciente_se_rechaza(): void
    {
        $deOtro = $this->cita($this->d1, $this->p3, 'completada', '2026-11-02 10:00');

        $this->actingAs($this->dentista1)->post('/dentista/expedientes', $this->alta($this->p1, $this->d1, ['cita_id' => $deOtro]))
            ->assertSessionHasErrors('cita_id');

        $this->assertSame(0, DB::table('expedientes')->where('diagnostico', 'Alta 014')->count());
    }

    public function test_el_alta_con_un_autor_que_no_es_dentista_se_rechaza(): void
    {
        $asistente = DB::table('personal')->where('usuario_id', $this->asistente->id)->value('id');

        $this->actingAs($this->admin)->post('/admin/expedientes', $this->alta($this->p1, $asistente))->assertSessionHasErrors('dentista_id');

        $this->assertSame(0, DB::table('expedientes')->where('diagnostico', 'Alta 014')->count());
    }

    /** D17, /analyze N3: en `create` y en `store`, también con datos inválidos. */
    public function test_un_paciente_ajeno_y_uno_inexistente_responden_igual_al_crear_expediente(): void
    {
        foreach ([$this->p2, (string) Str::uuid()] as $id) {
            $this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$id}")->assertNotFound();
            $this->actingAs($this->dentista1)->post('/dentista/expedientes', ['paciente_id' => $id, 'dentista_id' => $this->d1])->assertNotFound();
        }
    }

    public function test_el_alta_para_un_paciente_fuera_del_alcance_es_un_404(): void
    {
        $this->actingAs($this->dentista1)->post('/dentista/expedientes', $this->alta($this->p2, $this->d1))->assertNotFound();

        $this->assertSame(0, DB::table('expedientes')->where('diagnostico', 'Alta 014')->count());
        $this->assertDenegado('denegado_404', 'pacientes', $this->p2, 'dentista.expedientes.store', $this->dentista1->id);
    }

    /** TM13: un paciente que no es un UUID (texto, ausente o en array) da 404, nunca 500. */
    public function test_el_alta_de_expediente_con_paciente_mal_formado_es_un_404(): void
    {
        foreach (['?paciente_id=no-es-un-uuid', '', '?paciente_id[]=x'] as $consulta) {
            $this->actingAs($this->dentista1)->get("/dentista/expedientes/crear{$consulta}")->assertNotFound();
        }

        foreach ([['paciente_id' => 'no-es-un-uuid'], [], ['paciente_id' => ['x']]] as $paciente) {
            $datos = array_merge(['dentista_id' => $this->d1, 'diagnostico' => 'Alta 014'], $paciente);
            $this->actingAs($this->dentista1)->post('/dentista/expedientes', $datos)->assertNotFound();
        }

        $this->assertSame(0, DB::table('expedientes')->where('diagnostico', 'Alta 014')->count());
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    private function alta(string $paciente, ?string $dentista, array $extra = []): array
    {
        return array_merge(['paciente_id' => $paciente, 'dentista_id' => $dentista, 'diagnostico' => 'Alta 014'], $extra);
    }

    private function diagnosticoDe(string $expediente): ?string
    {
        return DB::table('expedientes')->where('id', $expediente)->value('diagnostico');
    }
}
