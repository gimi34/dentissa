<?php

namespace Tests\Feature;

use App\Agenda\EstadosCita;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Historial de estados de la cita (spec 026, CA9): una sola entrada con autor por
 * cada cambio aceptado, ninguna por un rechazo, y visible en el detalle.
 *
 * El reloj se fija en UTC; las citas empiezan a las 10:00 en hora de la clínica (16:00 UTC).
 */
class HistorialCitaTest extends TestCase
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

    public function test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor(): void
    {
        $casos = [
            'admin con motivo' => [$this->admin, 'agendada', 'confirmada', 'Confirmó por teléfono'],
            'dentista sin motivo' => [$this->dentista1, 'confirmada', 'en_curso', null],
            'asistente con motivo' => [$this->asistente, 'agendada', 'cancelada', 'El paciente avisó'],
        ];

        foreach ($casos as $caso => [$usuario, $origen, $destino, $motivo]) {
            $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);
            $this->cambiar($usuario, $cita, $destino, $motivo)->assertStatus(302);
            $this->assertUnaEntrada($cita, $usuario->id, $origen, $destino, $motivo, $caso);
        }

        $eliminada = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);
        $this->actingAs($this->admin)->delete("/admin/citas/{$eliminada}")->assertStatus(302);
        $this->assertUnaEntrada($eliminada, $this->admin->id, 'confirmada', 'cancelada', null, 'eliminar del admin');

        // El paciente cancela antes de que empiece (09:30 en la clínica).
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:30:00', 'UTC'));
        $delPaciente = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->actingAs($this->paciente1)->patch("/cliente/citas/{$delPaciente}/cancelar")->assertStatus(302);
        $this->assertUnaEntrada($delPaciente, $this->paciente1->id, 'agendada', 'cancelada', null, 'paciente');
    }

    public function test_un_rechazo_no_deja_entrada_en_el_historial(): void
    {
        $saltada = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $saltada, 'completada', 'Intento de saltar estados')->assertStatus(302);
        $this->assertSame('agendada', $this->estadoDe($saltada));

        $porRol = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->dentista1, $porRol, 'cancelada')->assertStatus(403);
        $this->assertSame('agendada', $this->estadoDe($porRol));

        $this->assertCount(0, $this->entradasDeHistorial($saltada));
        $this->assertCount(0, $this->entradasDeHistorial($porRol));
    }

    public function test_si_falla_el_historial_la_cita_no_cambia(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        // Un autor que no existe en `usuarios`: el INSERT del historial viola su clave foránea.
        $actor = new Usuario(['rol' => 'admin']);
        $actor->id = (string) Str::uuid();

        try {
            app(EstadosCita::class)->cambiar($cita, 'confirmada', $actor, null);
            $this->fail('El historial debía fallar por la clave foránea del autor.');
        } catch (QueryException) {
            // Esperado: sin historial, el cambio de estado tampoco debe quedar guardado.
        }

        $this->assertSame('agendada', DB::table('citas')->where('id', $cita)->lockForUpdate()->value('estado'));
        $this->assertCount(0, $this->entradasDeHistorial($cita));
    }

    public function test_el_historial_muestra_autor_estados_y_motivo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $cita, 'confirmada', 'Confirmó por teléfono');

        $historial = $this->seccion($this->actingAs($this->admin)->get("/admin/citas/{$cita}")->getContent(), 'historial');

        $this->assertNotNull($historial, 'el detalle no tiene la sección historial');
        $this->assertStringContainsString('Adela Administra', $historial);
        $this->assertStringContainsString('Agendada', $historial);
        $this->assertStringContainsString('Confirmada', $historial);
        $this->assertStringContainsString('Confirmó por teléfono', $historial);
        // La fecha se muestra como hoy, en UTC y sin convertir (deuda D4).
        $this->assertStringContainsString('05/10 17:00', $historial);
        $this->assertSame(1, substr_count($historial, 'Confirmó por teléfono'));
    }

    public function test_el_motivo_se_muestra_escapado(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $cita, 'confirmada', '<script>alert(1)</script>');

        $html = $this->actingAs($this->admin)->get("/admin/citas/{$cita}")->getContent();
        $historial = $this->seccion($html, 'historial');

        $this->assertNotNull($historial, 'el detalle no tiene la sección historial');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $historial);
    }

    public function test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs(): void
    {
        $registrados = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registrados) {
            $registrados[] = $evento->message.' '.json_encode($evento->context);
        });

        // Control: el escuchador recoge lo que se registra.
        Log::info('control del escuchador');
        $this->assertCount(1, $registrados);

        $aceptada = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $aceptada, 'confirmada', 'Motivo privado 7731');

        $rechazada = $this->cita($this->d1, $this->p1, 'completada', self::INICIO);
        $this->cambiar($this->admin, $rechazada, 'confirmada', 'Motivo privado 7731');

        foreach (array_slice($registrados, 1) as $linea) {
            $this->assertStringNotContainsString('Motivo privado 7731', $linea);
            $this->assertStringNotContainsString($aceptada, $linea);
            $this->assertStringNotContainsString($rechazada, $linea);
        }
    }

    private function assertUnaEntrada(string $cita, string $autor, string $anterior, string $nuevo, ?string $motivo, string $caso): void
    {
        $entradas = $this->entradasDeHistorial($cita);

        $this->assertCount(1, $entradas, "{$caso}: número de entradas");
        $this->assertSame($autor, $entradas[0]->cambiado_por, "{$caso}: autor");
        $this->assertSame($anterior, $entradas[0]->estado_anterior, "{$caso}: estado anterior");
        $this->assertSame($nuevo, $entradas[0]->estado_nuevo, "{$caso}: estado nuevo");
        $this->assertSame($motivo, $entradas[0]->motivo, "{$caso}: motivo");
    }
}
