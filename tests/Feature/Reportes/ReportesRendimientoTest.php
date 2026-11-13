<?php

namespace Tests\Feature\Reportes;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 047, requisito de rendimiento (TM3; plan D9): con 10.000 citas en un periodo de 366 días,
 * la página responde en menos de 2 s en el entorno local.
 *
 * Fuera de la suite por defecto (`php artisan test --group=rnf`), como el de la spec 020. Las
 * citas sintéticas se insertan dentro de la transacción del test, que se revierte al final.
 */
#[Group('rnf')]
class ReportesRendimientoTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const CITAS = 10_000;

    private const ESTADOS = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

    public function test_el_reporte_de_un_ano_con_diez_mil_citas_responde_en_menos_de_dos_segundos(): void
    {
        Carbon::setTestNow('2031-03-15 18:00:00');
        $this->crearEscenario();

        $servicios = [];
        foreach (range(1, 12) as $n) {
            $servicios[] = $id = (string) Str::uuid();
            DB::table('servicios')->insert(['id' => $id, 'nombre' => "Servicio sintético {$n}", 'activo' => 1]);
        }
        $servicios[] = null;

        $inicio = Carbon::parse('2031-01-01 08:00:00')->getTimestamp();
        $paso = intdiv(366 * 86400, self::CITAS);
        $lote = [];
        for ($n = 0; $n < self::CITAS; $n++) {
            $lote[] = [
                'id' => (string) Str::uuid(),
                'paciente_id' => $n % 2 === 0 ? $this->p1 : $this->p2,
                'dentista_id' => $n % 3 === 0 ? $this->d2 : $this->d1,
                'servicio_id' => $servicios[$n % 13],
                'fecha_hora' => date('Y-m-d H:i:s', $inicio + $n * $paso),
                'duracion_min' => 30,
                'estado' => self::ESTADOS[$n % 6],
            ];
            if (count($lote) === 500) {
                DB::table('citas')->insert($lote);
                $lote = [];
            }
        }

        $antes = microtime(true);
        $respuesta = $this->actingAs($this->admin)->get('/admin/reportes?desde=2031-01-01&hasta=2032-01-01');
        $segundos = microtime(true) - $antes;

        Carbon::setTestNow();

        $respuesta->assertOk()->assertSee('Citas por estado');
        $this->assertLessThan(2.0, $segundos, sprintf('La página tardó %.2f s con %d citas.', $segundos, self::CITAS));
    }
}
