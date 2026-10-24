<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Quién puede cambiar el estado de una cita (spec 026, CA4–CA7, y P3 por rol y acción).
 *
 * El reloj se fija en UTC. Las citas empiezan el lunes 2026-10-05 a las 10:00 en hora
 * de la clínica (16:00 UTC). Por defecto son las 17:00 UTC: las citas ya empezaron.
 */
class EstadosCitaRolesTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const INICIO = '2026-10-05 10:00';

    private const MENSAJE_DENTISTA = 'Como dentista no puedes cancelar citas. Pide a tu asistente o a la administración que la cancelen.';

    private const MENSAJE_ASISTENTE = 'Como asistente no puedes iniciar ni completar citas. Eso lo hace el dentista o la administración.';

    private const MENSAJE_PACIENTE_NO_CANCELABLE = 'Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica.';

    private const MENSAJE_PACIENTE_YA_EMPEZO = 'Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica.';

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

    // ── Dentista (CA4) ────────────────────────────────────────────────

    public function test_el_dentista_confirma_inicia_completa_y_marca_no_asistio_en_sus_citas(): void
    {
        foreach ([['agendada', 'confirmada'], ['confirmada', 'en_curso'], ['en_curso', 'completada'], ['agendada', 'no_asistio']] as [$origen, $destino]) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cambiar($this->dentista1, $cita, $destino)->assertStatus(302);

            $this->assertSame($destino, $this->estadoDe($cita), "{$origen} → {$destino}");
        }
    }

    public function test_el_dentista_no_puede_cancelar_su_cita(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

        $this->cambiar($this->dentista1, $cita, 'cancelada')
            ->assertStatus(403)
            ->assertSee(self::MENSAJE_DENTISTA);

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    public function test_el_dentista_no_puede_cambiar_la_cita_de_otro_dentista(): void
    {
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::INICIO);

        $this->cambiar($this->dentista1, $cita, 'confirmada')->assertStatus(403);

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    // ── Asistente (CA5) ───────────────────────────────────────────────

    public function test_el_asistente_confirma_cancela_y_marca_no_asistio_en_citas_de_sus_dentistas(): void
    {
        foreach ([['agendada', 'confirmada'], ['confirmada', 'cancelada'], ['agendada', 'no_asistio']] as [$origen, $destino]) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cambiar($this->asistente, $cita, $destino)->assertStatus(302);

            $this->assertSame($destino, $this->estadoDe($cita), "{$origen} → {$destino}");
        }
    }

    public function test_el_asistente_no_puede_iniciar_ni_completar(): void
    {
        foreach ([['confirmada', 'en_curso'], ['en_curso', 'completada']] as [$origen, $destino]) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cambiar($this->asistente, $cita, $destino)
                ->assertStatus(403)
                ->assertSee(self::MENSAJE_ASISTENTE);

            $this->assertSame($origen, $this->estadoDe($cita), "{$origen} → {$destino}");
        }
    }

    public function test_el_asistente_no_puede_cambiar_citas_de_dentistas_no_asignados(): void
    {
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::INICIO);

        $this->cambiar($this->asistente, $cita, 'confirmada')->assertStatus(403);

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    // ── Alcance y orden de comprobaciones ─────────────────────────────

    public function test_fuera_de_alcance_con_estado_invalido_responde_403(): void
    {
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::INICIO);

        foreach ([$this->dentista1, $this->asistente] as $usuario) {
            foreach (['', 'reabierta'] as $estado) {
                $this->cambiar($usuario, $cita, $estado)->assertStatus(403);
            }
        }

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    public function test_un_rol_sin_la_transicion_recibe_403_aunque_la_cita_este_cerrada(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'completada', self::INICIO);

        $this->cambiar($this->dentista1, $cita, 'cancelada')
            ->assertStatus(403)
            ->assertSee(self::MENSAJE_DENTISTA);

        $this->assertSame('completada', $this->estadoDe($cita));
    }

    public function test_el_alcance_del_listado_y_del_detalle_no_cambia(): void
    {
        $propia = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $ajena = $this->cita($this->d2, $this->p2, 'agendada', self::INICIO);
        $filtro = '?fecha_desde=2026-10-05&fecha_hasta=2026-10-05';

        foreach (['dentista' => $this->dentista1, 'asistente' => $this->asistente] as $prefijo => $usuario) {
            $ids = $this->actingAs($usuario)->get("/{$prefijo}/citas{$filtro}")
                ->assertOk()
                ->viewData('citas')->pluck('id')->all();
            $this->assertContains($propia, $ids, "{$prefijo}: falta su cita");
            $this->assertNotContains($ajena, $ids, "{$prefijo}: ve una cita fuera de su alcance");

            $this->actingAs($usuario)->get("/{$prefijo}/citas/{$propia}")->assertOk();
            $this->actingAs($usuario)->get("/{$prefijo}/citas/{$ajena}")->assertStatus(403);
        }
    }

    public function test_los_desplegables_de_dentistas_no_cambian(): void
    {
        $dentistas = fn (string $url, $usuario) => $this->actingAs($usuario)->get($url)
            ->assertOk()->viewData('dentistas')->pluck('id')->all();

        // Alta de cita: el dentista solo se ve a sí mismo; el asistente, a sus dentistas.
        $this->assertSame([$this->d1], $dentistas('/dentista/citas/crear', $this->dentista1));
        $this->assertSame([$this->d1], $dentistas('/asistente/citas/crear', $this->asistente));

        // Listado: el asistente filtra por sus dentistas; el dentista ve todos (como hoy).
        $this->assertSame([$this->d1], $dentistas('/asistente/citas', $this->asistente));
        $this->assertContains($this->d2, $dentistas('/dentista/citas', $this->dentista1));
    }

    // ── "Eliminar" del admin (CA6) ────────────────────────────────────

    public function test_eliminar_cita_es_una_cancelacion_con_las_mismas_reglas(): void
    {
        $agendada = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->actingAs($this->admin)->delete("/admin/citas/{$agendada}")->assertRedirect(route('admin.citas'));
        $this->assertSame('cancelada', $this->estadoDe($agendada));

        $completada = $this->cita($this->d1, $this->p1, 'completada', self::INICIO);
        $this->actingAs($this->admin)->delete("/admin/citas/{$completada}")->assertStatus(302);
        $this->assertSame('completada', $this->estadoDe($completada));
        $this->assertSame(
            'Esta cita ya está completada y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.',
            $this->errorDe('estado'),
        );
    }

    public function test_eliminar_una_cita_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->delete('/admin/citas/'.Str::uuid())->assertNotFound();
    }

    public function test_solo_el_admin_puede_eliminar_una_cita(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

        foreach ([$this->dentista1, $this->asistente, $this->paciente1] as $usuario) {
            $this->actingAs($usuario)->delete("/admin/citas/{$cita}")->assertStatus(403);
        }

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    // ── Paciente (CA7) ────────────────────────────────────────────────

    public function test_el_paciente_cancela_su_cita_antes_de_que_empiece(): void
    {
        // Spec 028 (CA3): el paciente cancela con 24 horas o más de antelación. El domingo
        // 2026-10-04 a las 15:30 UTC (09:30 en la clínica) faltan 24 h 30 min para la cita.
        Carbon::setTestNow(Carbon::parse('2026-10-04 15:30:00', 'UTC'));

        foreach (['agendada', 'confirmada'] as $origen) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cancelarComoPaciente($this->paciente1, $cita)->assertStatus(302);

            $this->assertSame('cancelada', $this->estadoDe($cita), $origen);
        }
    }

    public function test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo(): void
    {
        // 16:30 UTC son las 10:30 en la clínica: la cita ya empezó.
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:30:00', 'UTC'));
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);

        $this->cancelarComoPaciente($this->paciente1, $cita)->assertStatus(302);

        $this->assertSame('confirmada', $this->estadoDe($cita));
        $this->assertSame(self::MENSAJE_PACIENTE_YA_EMPEZO, session('error'));
    }

    public function test_el_paciente_no_puede_cancelar_una_cita_en_otro_estado(): void
    {
        foreach (['en_curso', 'completada', 'cancelada', 'no_asistio'] as $origen) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cancelarComoPaciente($this->paciente1, $cita)->assertStatus(302);

            $this->assertSame($origen, $this->estadoDe($cita));
            $this->assertSame(self::MENSAJE_PACIENTE_NO_CANCELABLE, session('error'), $origen);
        }
    }

    public function test_el_paciente_recibe_un_mensaje_sin_estados_internos_al_no_poder_cancelar(): void
    {
        foreach (['completada', 'en_curso'] as $origen) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

            $this->cancelarComoPaciente($this->paciente1, $cita);

            $this->assertSame(self::MENSAJE_PACIENTE_NO_CANCELABLE, session('error'), $origen);
        }
    }

    public function test_el_paciente_no_puede_cancelar_la_cita_de_otro(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:30:00', 'UTC'));
        $cita = $this->cita($this->d2, $this->p2, 'agendada', self::INICIO);

        $this->cancelarComoPaciente($this->paciente1, $cita)->assertNotFound();

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    // ── Rutas de otro rol ─────────────────────────────────────────────

    public function test_el_personal_no_puede_usar_la_cancelacion_del_paciente(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:30:00', 'UTC'));
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

        foreach ([$this->admin, $this->dentista1, $this->asistente] as $usuario) {
            $this->cancelarComoPaciente($usuario, $cita)->assertStatus(403);
        }

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    public function test_el_paciente_no_puede_usar_el_cambio_de_estado_del_personal(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

        foreach (['admin', 'dentista', 'asistente'] as $prefijo) {
            $this->actingAs($this->paciente1)
                ->patch("/{$prefijo}/citas/{$cita}/estado", ['estado' => 'cancelada'])
                ->assertStatus(403);
        }

        $this->assertSame('agendada', $this->estadoDe($cita));
    }

    private function cancelarComoPaciente($usuario, string $cita)
    {
        return $this->actingAs($usuario)->patch("/cliente/citas/{$cita}/cancelar");
    }
}
