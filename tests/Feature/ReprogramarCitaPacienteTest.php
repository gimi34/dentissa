<?php

namespace Tests\Feature;

use App\Agenda\CambioDeEstadoNoPermitido;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * El paciente reprograma y cancela con 24 horas o más de antelación (spec 028, CA2, CA3,
 * CA5, CA6 y CA9; TM4).
 *
 * La cita de referencia es de D1, el martes 2026-10-06 a las 10:00 en la clínica (16:00 UTC).
 * A las 16:00 UTC del lunes faltan exactamente 24 horas (dentro de plazo); a las 16:01, no.
 */
class ReprogramarCitaPacienteTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const CITA = '2026-10-06 10:00';

    private const PLAZO = 'Faltan menos de 24 horas para tu cita: ya no puedes cambiarla ni cancelarla desde aquí. Llama a la clínica.';

    private const AVISO_CORTO = 'Faltan menos de 24 h: llama a la clínica para cambiarla.';

    private const YA_EMPEZO = 'Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica.';

    private const NO_REPROGRAMABLE = 'Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.';

    private const CONFIRMAR = 'Marca la casilla para confirmar que quieres cancelar la cita.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->conHorarios();
        $this->ahora('16:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── CA2 ───────────────────────────────────────────────────────────

    public function test_el_paciente_reprograma_con_24_horas_o_mas(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::CITA);

        $this->reprogramar($this->paciente1, $cita, '2026-10-07', '11:00')
            ->assertRedirect("/cliente/citas/{$cita}");

        $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));
        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    public function test_el_hueco_nuevo_puede_empezar_en_menos_de_24_horas(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        // Las 12:00 de hoy en la clínica: faltan 2 horas, pero el plazo se mide sobre la cita actual.
        $this->reprogramar($this->paciente1, $cita, '2026-10-05', '12:00')->assertSessionHasNoErrors();

        $this->assertSame('2026-10-05 12:00', $this->fechaHoraDe($cita));
    }

    public function test_con_menos_de_24_horas_se_rechaza_con_el_mensaje_de_plazo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        $this->ahora('16:01');

        $this->reprogramar($this->paciente1, $cita, '2026-10-07', '11:00')
            ->assertStatus(302)
            ->assertSessionHas('error', self::PLAZO);

        $this->assertSame(self::CITA, $this->fechaHoraDe($cita));
    }

    public function test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        $dentro = $this->acciones("/cliente/citas/{$cita}", 'acciones-cita');
        $this->assertStringContainsString("/cliente/citas/{$cita}/reprogramar", $dentro);
        $this->assertStringContainsString("/cliente/citas/{$cita}/cancelar", $dentro);
        $this->assertStringNotContainsString(self::PLAZO, $dentro);

        $this->ahora('16:01');
        $fuera = $this->acciones("/cliente/citas/{$cita}", 'acciones-cita');
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/reprogramar", $fuera);
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/cancelar", $fuera);
        $this->assertStringContainsString(self::PLAZO, $fuera);

        // A40: con la cita ya empezada, el texto de «ya empezó» de la 026.
        $empezada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30');
        $acciones = $this->acciones("/cliente/citas/{$empezada}", 'acciones-cita');
        $this->assertStringContainsString(self::YA_EMPEZO, $acciones);
        $this->assertStringNotContainsString("/cliente/citas/{$empezada}/reprogramar", $acciones);
        $this->assertStringNotContainsString("/cliente/citas/{$empezada}/cancelar", $acciones);
    }

    public function test_la_lista_muestra_el_aviso_corto_fuera_de_plazo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        $dentro = $this->acciones('/cliente/citas', "acciones-{$cita}");
        $this->assertStringContainsString("/cliente/citas/{$cita}/reprogramar", $dentro);
        $this->assertStringContainsString("/cliente/citas/{$cita}/cancelar", $dentro);
        $this->assertStringNotContainsString(self::AVISO_CORTO, $dentro);

        $this->ahora('16:01');
        $fuera = $this->acciones('/cliente/citas', "acciones-{$cita}");
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/reprogramar", $fuera);
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/cancelar", $fuera);
        $this->assertStringContainsString(self::AVISO_CORTO, $fuera);

        // A40 y A52: con la cita ya empezada (sale en «pasadas», deuda D4), ni botones ni aviso; «Ver» sigue.
        $empezada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30');
        $acciones = $this->acciones('/cliente/citas?filtro=pasadas', "acciones-{$empezada}");
        $this->assertStringNotContainsString("/cliente/citas/{$empezada}/reprogramar", $acciones);
        $this->assertStringNotContainsString("/cliente/citas/{$empezada}/cancelar", $acciones);
        $this->assertStringNotContainsString(self::AVISO_CORTO, $acciones);
        $this->assertStringContainsString("/cliente/citas/{$empezada}\"", $acciones);
    }

    public function test_el_panel_de_inicio_respeta_el_plazo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        // Dentro de plazo: «Reprogramar» y «Cancelar», sin aviso; también con la cita confirmada.
        foreach (['agendada', 'confirmada'] as $estado) {
            DB::table('citas')->where('id', $cita)->update(['estado' => $estado]);
            $dentro = $this->acciones('/cliente', 'acciones-proxima');
            $this->assertStringContainsString("/cliente/citas/{$cita}/reprogramar", $dentro, "{$estado}: ofrece reprogramar.");
            $this->assertStringContainsString("/cliente/citas/{$cita}/cancelar", $dentro, "{$estado}: ofrece cancelar.");
            $this->assertStringNotContainsString(self::AVISO_CORTO, $dentro);
        }

        // Fuera de plazo: ni «Reprogramar» ni «Cancelar», el aviso corto y sigue «Ver detalles».
        DB::table('citas')->where('id', $cita)->update(['estado' => 'agendada']);
        $this->ahora('16:01');
        $fuera = $this->acciones('/cliente', 'acciones-proxima');
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/reprogramar", $fuera);
        $this->assertStringNotContainsString("/cliente/citas/{$cita}/cancelar", $fuera);
        $this->assertStringContainsString(self::AVISO_CORTO, $fuera);
        $this->assertStringContainsString("/cliente/citas/{$cita}\"", $fuera);
        $this->assertStringContainsString('Ver detalles', $fuera);
    }

    public function test_con_el_dentista_inactivo_el_paciente_solo_puede_cancelar(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        DB::table('usuarios')->where('id', $this->dentista1->id)->update(['activo' => 0]);

        foreach (['/cliente/citas' => "acciones-{$cita}", "/cliente/citas/{$cita}" => 'acciones-cita', '/cliente' => 'acciones-proxima'] as $url => $bloque) {
            $acciones = $this->acciones($url, $bloque);
            $this->assertStringNotContainsString("/cliente/citas/{$cita}/reprogramar", $acciones, "{$url}: sin «Reprogramar».");
            $this->assertStringContainsString("/cliente/citas/{$cita}/cancelar", $acciones, "{$url}: «Cancelar» sigue.");
        }

        $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}/reprogramar")
            ->assertOk()
            ->assertSee(self::NO_REPROGRAMABLE)
            ->getContent();
        $this->assertStringNotContainsString('name="fecha"', $html);
    }

    public function test_el_paciente_ve_el_motivo_si_su_cita_no_se_puede_mover(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        $this->ahora('16:01');

        $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}/reprogramar")
            ->assertOk()
            ->assertSee(self::PLAZO)
            ->assertSee(route('cliente.citas.show', $cita), false)
            ->getContent();

        $this->assertStringNotContainsString('name="fecha"', $html);
        $this->assertStringNotContainsString('id="no-reagendar"', $html);
    }

    // ── CA3 ───────────────────────────────────────────────────────────

    public function test_el_paciente_cancela_con_24_horas_o_mas(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        $this->actingAs($this->paciente1)->patch("/cliente/citas/{$cita}/cancelar")->assertStatus(302);

        $this->assertSame('cancelada', $this->estadoDe($cita));
    }

    public function test_el_paciente_no_cancela_con_menos_de_24_horas(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        $this->ahora('16:01');

        $this->actingAs($this->paciente1)->patch("/cliente/citas/{$cita}/cancelar")
            ->assertStatus(302)
            ->assertSessionHas('error', self::PLAZO);

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    // ── CA6 ───────────────────────────────────────────────────────────

    public function test_el_paciente_cancela_con_motivo_desde_la_pantalla(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}/reprogramar")->assertOk()->getContent();
        $seccion = (string) $this->seccion($html, 'no-reagendar');
        $this->assertStringContainsString(route('cliente.citas.cancelar', $cita), $seccion);
        $this->assertStringContainsString('name="motivo"', $seccion);

        $this->actingAs($this->paciente1)
            ->patch("/cliente/citas/{$cita}/cancelar", ['motivo' => 'Me mudo de ciudad', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertStatus(302);

        $this->assertSame('cancelada', $this->estadoDe($cita));
        $entrada = $this->entradasDeHistorial($cita)->sole();
        $this->assertSame('Me mudo de ciudad', $entrada->motivo);
        $this->assertSame($this->paciente1->id, $entrada->cambiado_por);
    }

    public function test_el_paciente_confirma_la_cancelacion_desde_la_pantalla(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        $formulario = "/cliente/citas/{$cita}/reprogramar";

        $html = $this->actingAs($this->paciente1)->get($formulario)->assertOk()->getContent();
        $xpath = new DOMXPath($this->dom((string) $this->seccion($html, 'no-reagendar')));
        $casilla = $xpath->query("//input[@type='checkbox'][@name='confirmar'][@value='1']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $casilla, 'Falta la casilla de confirmar.');
        $etiqueta = $xpath->query("//label[@for='{$casilla->getAttribute('id')}']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $etiqueta, 'La casilla no tiene etiqueta asociada.');

        // Sin la casilla, no se cancela y el error va junto a ella.
        $this->actingAs($this->paciente1)->from($formulario)
            ->patch("/cliente/citas/{$cita}/cancelar", ['formulario' => 'no-reagendar'])
            ->assertRedirect($formulario);
        // Se lee antes del GET: `assertSessionHasErrors` haría que el GET ya no recibiera los errores.
        $this->assertSame(self::CONFIRMAR, $this->errorDe('confirmar'));
        $this->assertSame('agendada', $this->estadoDe($cita));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
        $html = $this->actingAs($this->paciente1)->get($formulario)->assertOk()->getContent();
        $xpath = new DOMXPath($this->dom((string) $this->seccion($html, 'no-reagendar')));
        $casilla = $xpath->query("//input[@name='confirmar']")->item(0);
        $this->assertSame('true', $casilla->getAttribute('aria-invalid'));
        $error = $xpath->query("//*[@id='{$casilla->getAttribute('aria-describedby')}']")->item(0);
        $this->assertSame(self::CONFIRMAR, trim((string) $error?->textContent));

        // Con la casilla, se cancela y vuelve al detalle, sin el aviso de que ya no se puede mover (R1).
        $this->actingAs($this->paciente1)->from($formulario)
            ->patch("/cliente/citas/{$cita}/cancelar", ['formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertRedirect("/cliente/citas/{$cita}");
        $this->assertSame('cancelada', $this->estadoDe($cita));
        $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}")->assertOk()->getContent();
        $this->assertStringContainsString('Tu cita fue cancelada.', $html);
        $this->assertStringNotContainsString(self::NO_REPROGRAMABLE, $html);

        // Desde el detalle o la lista (sin `formulario`), cancelar no pide la casilla.
        $otra = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-07 10:00');
        $this->actingAs($this->paciente1)->from('/cliente/citas')
            ->patch("/cliente/citas/{$otra}/cancelar")
            ->assertRedirect('/cliente/citas')
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelada', $this->estadoDe($otra));
    }

    // ── CA5 (paciente) ────────────────────────────────────────────────

    public function test_el_paciente_no_ve_estados_internos(): void
    {
        $casos = [
            'completada' => $this->cita($this->d1, $this->p1, 'completada', self::CITA),
            'no asistió' => $this->cita($this->d1, $this->p1, 'no_asistio', self::CITA),
            'en curso' => $this->cita($this->d1, $this->p1, 'en_curso', '2026-10-05 09:45'),
            'agendada ya empezada' => $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30'),
        ];

        foreach ($casos as $caso => $cita) {
            // Sesión limpia: el aviso del PATCH del caso anterior lo pintaría el layout (R4).
            $this->flushSession();
            $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}/reprogramar")
                ->assertOk()
                ->assertSee(self::NO_REPROGRAMABLE)
                ->getContent();
            $this->assertStringNotContainsString('name="fecha"', $html, "{$caso}: sin formulario de nueva fecha.");
            $this->assertStringNotContainsString('id="no-reagendar"', $html, "{$caso}: sin «No reagendar».");

            $this->reprogramar($this->paciente1, $cita, '2026-10-07', '11:00')
                ->assertStatus(302)
                ->assertSessionHas('error', self::NO_REPROGRAMABLE);

            foreach (['completada', 'no_asistio', 'en_curso'] as $estado) {
                $this->assertStringNotContainsString(
                    CambioDeEstadoNoPermitido::etiqueta($estado),
                    (string) session('error'),
                    "{$caso}: el mensaje no nombra estados internos.",
                );
            }
        }
    }

    // ── CA9 y TM4 ─────────────────────────────────────────────────────

    public function test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::CITA);
        $this->ahora('16:01');

        $this->reprogramar($this->paciente1, $cita, '2026-10-07', '11:00')->assertSessionHas('error', self::PLAZO);
        $this->actingAs($this->paciente1)
            ->patch("/cliente/citas/{$cita}/cancelar", ['motivo' => 'Enviado a mano', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertSessionHas('error', self::PLAZO);

        $this->assertSame(self::CITA, $this->fechaHoraDe($cita));
        $this->assertSame('confirmada', $this->estadoDe($cita));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
    }

    public function test_los_mensajes_de_validacion_de_la_cancelacion_estan_en_espanol(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);

        $this->actingAs($this->paciente1)->patch("/cliente/citas/{$cita}/cancelar", ['motivo' => ['lista']]);
        $this->assertSame('El motivo debe ser un texto.', $this->errorDe('motivo'));

        $this->actingAs($this->paciente1)->patch("/cliente/citas/{$cita}/cancelar", ['motivo' => str_repeat('a', 501)]);
        $this->assertSame('El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.', $this->errorDe('motivo'));

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    public function test_las_listas_en_el_formulario_no_dan_error_del_servidor(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::CITA);
        $formulario = "/cliente/citas/{$cita}/reprogramar";

        foreach (['fecha', 'hora', 'motivo', 'formulario'] as $campo) {
            $datos = ['fecha' => '2026-10-07', 'hora' => '11:00', 'motivo' => null, 'formulario' => 'reprogramar'];
            $datos[$campo] = ['lista'];

            $respuesta = $this->actingAs($this->paciente1)->from($formulario)->patch($formulario, $datos);
            $this->assertNotSame(500, $respuesta->status(), $campo);

            if ($campo === 'formulario') {
                // D10: `formulario` solo lo lee la vista; con fecha y hora válidas, la cita se mueve.
                $respuesta->assertSessionHasNoErrors();
                $this->assertSame('2026-10-07 11:00', $this->fechaHoraDe($cita));
            } else {
                $respuesta->assertSessionHasErrors($campo);
                $this->assertSame(self::CITA, $this->fechaHoraDe($cita), $campo);
            }

            // A34: al volver al formulario con la entrada anterior, nunca hay un 500.
            $this->actingAs($this->paciente1)->get($formulario)->assertOk();
        }
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function dom(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return $dom;
    }

    /** Fija "ahora" el lunes 2026-10-05 a esa hora UTC. */
    private function ahora(string $hora): void
    {
        Carbon::setTestNow(Carbon::parse("2026-10-05 {$hora}:00", 'UTC'));
    }

    /** HTML del bloque con ese `id` en la página, como paciente. */
    private function acciones(string $url, string $id): string
    {
        $html = $this->actingAs($this->paciente1)->get($url)->assertOk()->getContent();
        $bloque = $this->seccion($html, $id);
        $this->assertNotNull($bloque, "La página {$url} no tiene #{$id}.");

        return $bloque;
    }
}
