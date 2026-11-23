<?php

namespace Tests\Feature;

use App\Autorizacion\AlcanceClinico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Concerns\EscenarioAlcanceClinico;
use Tests\TestCase;

/**
 * Spec 014: la regla de alcance clínico (CA1, CA2, CA10, TM11) y los Gates de `AccesoClinico` por rol (CA3, CA5–CA7,
 * CA9). Un paciente es del alcance si está asignado a un dentista del alcance o tiene una cita no cancelada con él.
 */
class AlcanceClinicoReglaTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioAlcanceClinico;

    private AlcanceClinico $alcance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenarioAlcance();
        $this->alcance = app(AlcanceClinico::class);
    }

    public function test_el_alcance_de_cada_rol(): void
    {
        $esperado = [
            'D1' => [$this->dentista1, ['p1' => true, 'p2' => false, 'p3' => true, 'p4' => false, 'p5' => false]],
            'A1' => [$this->asistente, ['p1' => true, 'p2' => false, 'p3' => true, 'p4' => false, 'p5' => false]],
            'D2' => [$this->dentista2, ['p1' => false, 'p2' => true, 'p3' => true, 'p4' => true, 'p5' => false]],
            'admin' => [$this->admin, ['p1' => true, 'p2' => true, 'p3' => true, 'p4' => true, 'p5' => true]],
        ];

        foreach ($esperado as $quien => [$usuario, $pacientes]) {
            foreach ($pacientes as $propiedad => $enAlcance) {
                $this->assertSame($enAlcance, $this->alcance->pacienteEnAlcance($usuario, $this->{$propiedad}),
                    "{$quien} y {$propiedad}: ".($enAlcance ? 'en' : 'fuera del').' alcance.');
            }
        }
    }

    public function test_aplicar_a_pacientes_devuelve_el_alcance(): void
    {
        $nuestros = [$this->p1, $this->p2, $this->p3, $this->p4, $this->p5];
        $filtrados = fn ($usuario) => $this->alcance->aplicarAPacientes(DB::table('pacientes'), $usuario)
            ->whereIn('pacientes.id', $nuestros)->orderBy('pacientes.id')->pluck('pacientes.id')->all();
        $ordenados = fn (array $ids) => collect($ids)->sort()->values()->all();

        $this->assertSame($ordenados([$this->p1, $this->p3]), $filtrados($this->dentista1));
        $this->assertSame($ordenados([$this->p1, $this->p3]), $filtrados($this->asistente));
        $this->assertSame($ordenados([$this->p2, $this->p3, $this->p4]), $filtrados($this->dentista2));
        $this->assertSame($ordenados($nuestros), $filtrados($this->admin));
    }

    // ── Gates (T011) ──────────────────────────────────────────────────────────────────

    /** CA3: ver y editar la ficha. Fuera del alcance, 404; el asistente no edita (403). */
    public function test_los_gates_de_la_ficha_por_rol(): void
    {
        $this->assertGate('permitido', $this->dentista1, 'ver-paciente', $this->p1);
        $this->assertGate('404', $this->dentista1, 'ver-paciente', $this->p2);
        $this->assertGate('404', $this->dentista1, 'ver-paciente', $this->p5);
        $this->assertGate('permitido', $this->asistente, 'ver-paciente', $this->p3);
        $this->assertGate('404', $this->asistente, 'ver-paciente', $this->p2);
        $this->assertGate('permitido', $this->admin, 'ver-paciente', $this->p5);

        $this->assertGate('permitido', $this->dentista1, 'editar-paciente', $this->p1);
        $this->assertGate('404', $this->dentista1, 'editar-paciente', $this->p2);
        $this->assertGate('403', $this->asistente, 'editar-paciente', $this->p1);
        $this->assertGate('permitido', $this->admin, 'editar-paciente', $this->p2);
    }

    /** CA4–CA6: leer y modificar expedientes. Fuera del alcance, 404; dentro sin ser autor, 403. */
    public function test_los_gates_del_expediente_por_rol(): void
    {
        [$e1, $e2, $e3] = array_map(fn ($id) => DB::table('expedientes')->where('id', $id)->first(), [$this->e1, $this->e2, $this->e3]);

        $this->assertGate('permitido', $this->dentista1, 'ver-expediente', $e2);
        $this->assertGate('404', $this->dentista1, 'ver-expediente', $e3);
        $this->assertGate('permitido', $this->asistente, 'ver-expediente', $e1);
        $this->assertGate('404', $this->asistente, 'ver-expediente', $e3);
        $this->assertGate('permitido', $this->admin, 'ver-expediente', $e3);

        $this->assertGate('permitido', $this->dentista1, 'editar-expediente', $e1);
        $this->assertGate('403', $this->dentista1, 'editar-expediente', $e2);
        $this->assertGate('404', $this->dentista1, 'editar-expediente', $e3);
        $this->assertGate('403', $this->asistente, 'editar-expediente', $e1);
        $this->assertGate('permitido', $this->admin, 'editar-expediente', $e2);
    }

    /** CA7 y CA9: crear expedientes y agendar para un paciente. */
    public function test_los_gates_de_alta_por_rol(): void
    {
        $this->assertGate('permitido', $this->dentista1, 'crear-expediente', $this->p1);
        $this->assertGate('404', $this->dentista1, 'crear-expediente', $this->p2);
        $this->assertGate('403', $this->asistente, 'crear-expediente', $this->p1);
        $this->assertGate('permitido', $this->admin, 'crear-expediente', $this->p2);

        $this->assertGate('permitido', $this->dentista1, 'agendar-para-paciente', $this->p3);
        $this->assertGate('404', $this->dentista1, 'agendar-para-paciente', $this->p2);
        $this->assertGate('404', $this->dentista1, 'agendar-para-paciente', $this->p5);
        $this->assertGate('permitido', $this->asistente, 'agendar-para-paciente', $this->p1);
        $this->assertGate('404', $this->asistente, 'agendar-para-paciente', $this->p2);
        $this->assertGate('permitido', $this->admin, 'agendar-para-paciente', $this->p5);
    }

    /** `permitido`, `404` (`denyAsNotFound`, no confirma que exista) o `403` (`deny`, dentro del alcance). */
    private function assertGate(string $esperado, $usuario, string $habilidad, mixed $recurso): void
    {
        $respuesta = Gate::forUser($usuario)->inspect($habilidad, [$recurso]);
        $obtenido = match (true) {
            $respuesta->allowed() => 'permitido',
            $respuesta->status() === 404 => '404',
            default => '403',
        };

        $this->assertSame($esperado, $obtenido, "{$habilidad} para {$usuario->rol} ({$usuario->nombre}).");
    }

    /** CA10, TM11: el alcance se evalúa en cada consulta; una reasignación surte efecto en el acto. */
    public function test_reasignar_saca_del_alcance_en_el_acto(): void
    {
        $this->assertTrue($this->alcance->pacienteEnAlcance($this->dentista1, $this->p1));

        DB::table('pacientes')->where('id', $this->p1)->update(['dentista_id' => $this->d2]);

        $this->assertFalse($this->alcance->pacienteEnAlcance($this->dentista1, $this->p1), 'D1 lo pierde.');
        $this->assertTrue($this->alcance->pacienteEnAlcance($this->dentista2, $this->p1), 'D2 lo gana.');
    }
}
