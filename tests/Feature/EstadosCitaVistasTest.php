<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Qué ve cada rol al cambiar el estado de una cita (spec 026, plan "Qué ve cada rol").
 *
 * Las opciones y los avisos se comprueban dentro de la sección `estado` del detalle; los
 * botones de cancelar, por el formulario cuyo `action` es la ruta de cancelar (A7).
 * El reloj se fija en UTC; la cita de referencia empieza a las 10:00 en la clínica (16:00 UTC).
 */
class EstadosCitaVistasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const INICIO = '2026-10-05 10:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_formulario_solo_ofrece_las_transiciones_del_rol_y_del_estado(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);

        $esperadas = [
            'admin' => [$this->admin, ['en_curso', 'cancelada', 'no_asistio']],
            'dentista' => [$this->dentista1, ['en_curso', 'no_asistio']],
            'asistente' => [$this->asistente, ['cancelada', 'no_asistio']],
        ];

        foreach ($esperadas as $prefijo => [$usuario, $opciones]) {
            $seccion = $this->seccionEstado($usuario, $prefijo, $cita);

            $this->assertNotNull($seccion, "{$prefijo}: el detalle no tiene la sección estado");
            $this->assertEqualsCanonicalizing($opciones, $this->opcionesDeEstado($seccion), $prefijo);
        }
    }

    public function test_una_cita_cerrada_no_ofrece_cambios_y_explica_como_seguir(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'completada', self::INICIO);

        foreach (['admin' => $this->admin, 'dentista' => $this->dentista1, 'asistente' => $this->asistente] as $prefijo => $usuario) {
            $seccion = $this->seccionEstado($usuario, $prefijo, $cita);

            $this->assertSame([], $this->opcionesDeEstado($seccion), $prefijo);
            $this->assertStringContainsString(
                'Esta cita ya está completada y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.',
                $seccion,
                $prefijo,
            );
        }
    }

    public function test_antes_de_la_hora_se_explica_desde_cuando_se_puede_iniciar(): void
    {
        // 09:00 en la clínica: la cita de las 10:00 aún no empieza.
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:00:00', 'UTC'));
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);

        $seccion = $this->seccionEstado($this->dentista1, 'dentista', $cita);

        $this->assertSame([], $this->opcionesDeEstado($seccion));
        $this->assertStringContainsString(
            'Podrás marcarla como en curso o no asistida a partir de las 10:00 del 05/10/2026.',
            $seccion,
        );
    }

    public function test_el_asistente_ve_quien_puede_completar_una_cita_en_curso(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'en_curso', self::INICIO);

        $seccion = $this->seccionEstado($this->asistente, 'asistente', $cita);

        $this->assertSame([], $this->opcionesDeEstado($seccion));
        $this->assertStringContainsString('Solo el dentista o la administración pueden completar esta cita.', $seccion);
    }

    public function test_el_boton_cancelar_del_admin_solo_aparece_en_citas_abiertas(): void
    {
        foreach (['agendada' => 1, 'confirmada' => 1, 'en_curso' => 0, 'completada' => 0, 'cancelada' => 0, 'no_asistio' => 0] as $estado => $esperado) {
            $cita = $this->cita($this->d1, $this->p1, $estado, self::INICIO);

            $html = $this->actingAs($this->admin)->get("/admin/citas/{$cita}")->assertOk()->getContent();

            $this->assertSame($esperado, $this->formulariosHacia($html, "/admin/citas/{$cita}", 'DELETE'), $estado);
        }
    }

    public function test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo(): void
    {
        // 16:30 UTC son las 10:30 en la clínica.
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:30:00', 'UTC'));
        $empezada = $this->cita($this->d1, $this->p1, 'confirmada', '2026-10-05 10:00');
        $pendiente = $this->cita($this->d1, $this->p1, 'confirmada', '2026-10-05 12:00');

        $detalle = fn (string $cita) => $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}")->assertOk()->getContent();
        $this->assertSame(0, $this->formulariosHacia($detalle($empezada), "/cliente/citas/{$empezada}/cancelar", 'PATCH'));
        $this->assertSame(1, $this->formulariosHacia($detalle($pendiente), "/cliente/citas/{$pendiente}/cancelar", 'PATCH'));

        // El listado compara en UTC (deuda D4): las dos aparecen como pasadas (A8).
        $listado = $this->actingAs($this->paciente1)->get('/cliente/citas?filtro=pasadas')->assertOk()->getContent();
        $this->assertSame(0, $this->formulariosHacia($listado, "/cliente/citas/{$empezada}/cancelar", 'PATCH'));
        $this->assertSame(1, $this->formulariosHacia($listado, "/cliente/citas/{$pendiente}/cancelar", 'PATCH'));
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function seccionEstado($usuario, string $prefijo, string $cita): ?string
    {
        $html = $this->actingAs($usuario)->get("/{$prefijo}/citas/{$cita}")->assertOk()->getContent();

        return $this->seccion($html, 'estado');
    }

    /** Valores de los radios `estado` dentro de la sección. */
    private function opcionesDeEstado(?string $seccion): array
    {
        preg_match_all('/<input[^>]*name="estado"[^>]*value="([a-z_]+)"/', (string) $seccion, $m);

        return $m[1];
    }

    /** Número de formularios cuyo `action` termina en la ruta dada y usan ese método. */
    private function formulariosHacia(string $html, string $ruta, string $metodo): int
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $total = 0;
        foreach ((new DOMXPath($dom))->query('//form') as $form) {
            $action = (string) $form->getAttribute('action');
            if (! str_ends_with(parse_url($action, PHP_URL_PATH) ?? '', $ruta)) {
                continue;
            }
            $metodoReal = strtoupper((string) $form->getAttribute('method'));
            foreach ($form->getElementsByTagName('input') as $input) {
                if ($input->getAttribute('name') === '_method') {
                    $metodoReal = strtoupper($input->getAttribute('value'));
                }
            }
            $total += $metodoReal === $metodo ? 1 : 0;
        }

        return $total;
    }
}
