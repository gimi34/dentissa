<?php

namespace Tests\Feature;

use App\Agenda\CambioDeEstadoNoPermitido;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Quién puede reprogramar y cancelar definitivamente (spec 028, CA5, CA8; P3, TM1 y TM2).
 *
 * Reloj fijo: lunes 2026-10-05 a las 16:00 UTC (10:00 en la clínica). Cada denegación
 * comprueba que la cita no cambió.
 */
class ReprogramarCitaRolesTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const FUTURA = '2026-10-07 10:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->conHorarios();
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_administracion_y_el_asistente_abren_el_formulario(): void
    {
        $deD2 = $this->cita($this->d2, $this->p2, 'agendada', self::FUTURA);
        $deD1 = $this->cita($this->d1, $this->p1, 'confirmada', self::FUTURA);

        $this->actingAs($this->admin)->get("/admin/citas/{$deD2}/reprogramar")->assertOk();
        $this->actingAs($this->asistente)->get("/asistente/citas/{$deD1}/reprogramar")->assertOk();
    }

    public function test_el_dentista_no_puede_reprogramar(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        foreach (['admin', 'asistente'] as $grupo) {
            $this->actingAs($this->dentista1)->get("/{$grupo}/citas/{$cita}/reprogramar")->assertForbidden();
            $this->pedir($this->dentista1, $grupo, $cita)->assertForbidden();
        }

        // En su propio grupo la ruta no existe.
        $this->actingAs($this->dentista1)->get("/dentista/citas/{$cita}/reprogramar")->assertNotFound();
        $this->assertContains($this->pedir($this->dentista1, 'dentista', $cita)->status(), [404, 405]);

        $this->assertSame(self::FUTURA, $this->fechaHoraDe($cita));
    }

    public function test_el_asistente_no_reprograma_citas_de_dentistas_no_asignados(): void
    {
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::FUTURA);

        $this->actingAs($this->asistente)->get("/asistente/citas/{$cita}/reprogramar")->assertForbidden();
        $this->pedir($this->asistente, 'asistente', $cita)->assertForbidden();

        $this->assertSame(self::FUTURA, $this->fechaHoraDe($cita));
    }

    public function test_el_paciente_no_encuentra_la_cita_de_otro(): void
    {
        $ajena = $this->cita($this->d2, $this->p2, 'agendada', self::FUTURA);
        $propia = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        $this->actingAs($this->paciente1)->get("/cliente/citas/{$ajena}/reprogramar")->assertNotFound();
        $this->pedir($this->paciente1, 'cliente', $ajena)->assertNotFound();
        $this->assertSame(self::FUTURA, $this->fechaHoraDe($ajena));

        // Su cita propia sí la encuentra.
        $this->actingAs($this->paciente1)->get("/cliente/citas/{$propia}/reprogramar")->assertOk();
    }

    public function test_el_personal_no_puede_usar_la_ruta_del_paciente(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        foreach (['admin' => $this->admin, 'asistente' => $this->asistente, 'dentista' => $this->dentista1] as $rol => $usuario) {
            $this->actingAs($usuario)->get("/cliente/citas/{$cita}/reprogramar")->assertForbidden();
            $this->pedir($usuario, 'cliente', $cita)->assertForbidden();
        }

        $this->assertSame(self::FUTURA, $this->fechaHoraDe($cita));
    }

    public function test_el_paciente_no_puede_usar_la_ruta_del_personal(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        foreach (['admin', 'asistente'] as $grupo) {
            $this->actingAs($this->paciente1)->get("/{$grupo}/citas/{$cita}/reprogramar")->assertForbidden();
            $this->pedir($this->paciente1, $grupo, $cita)->assertForbidden();
        }

        $this->assertSame(self::FUTURA, $this->fechaHoraDe($cita));
    }

    public function test_fuera_de_alcance_tampoco_cancela_definitivamente(): void
    {
        $deD2 = $this->cita($this->d2, $this->p2, 'agendada', self::FUTURA);

        $this->actingAs($this->asistente)
            ->patch("/asistente/citas/{$deD2}/estado", ['estado' => 'cancelada', 'motivo' => 'Desde No reagendar', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertForbidden();
        $this->actingAs($this->paciente1)
            ->patch("/cliente/citas/{$deD2}/cancelar", ['motivo' => 'Desde No reagendar', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertNotFound();

        $this->assertSame('agendada', $this->estadoDe($deD2));
    }

    public function test_el_formulario_de_una_cita_que_no_se_puede_mover_muestra_el_motivo(): void
    {
        $casos = [];
        foreach (['completada', 'cancelada', 'no_asistio'] as $estado) {
            $casos[$estado] = [$this->cita($this->d1, $this->p1, $estado, self::FUTURA), CambioDeEstadoNoPermitido::cerrada($estado)->getMessage()];
        }
        $casos['en curso'] = [$this->cita($this->d1, $this->p1, 'en_curso', '2026-10-05 09:45'), 'Esta cita ya empezó y no se puede reprogramar.'];
        $casos['agendada ya empezada'] = [$this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30'), 'Esta cita ya empezó y no se puede reprogramar.'];

        foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $grupo => $usuario) {
            foreach ($casos as $caso => [$cita, $motivo]) {
                $html = $this->actingAs($usuario)->get("/{$grupo}/citas/{$cita}/reprogramar")
                    ->assertOk()
                    ->assertSee($motivo)
                    ->assertSee(route("{$grupo}.citas.show", $cita), false)
                    ->getContent();

                $this->assertStringNotContainsString('name="fecha"', $html, "{$grupo}, {$caso}: sin formulario de reprogramar.");
                $this->assertStringNotContainsString('id="no-reagendar"', $html, "{$grupo}, {$caso}: sin «No reagendar».");
            }
        }
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function pedir(Usuario $usuario, string $grupo, string $cita): TestResponse
    {
        return $this->actingAs($usuario)->patch("/{$grupo}/citas/{$cita}/reprogramar", ['fecha' => '2026-10-08', 'hora' => '11:00']);
    }
}
