<?php

namespace Tests\Feature\Reportes;

use App\Reportes\ReporteCitas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 047, CA1, CA3, CA4 y CA5 y el requisito de exactitud: las cifras de `ReporteCitas`.
 *
 * Los periodos son de 2031: `dentissa_test` trae citas de 2026 del volcado y los reportes cuentan
 * todas las citas de la clínica (plan D12). Cada test comprueba que su periodo está vacío antes
 * de sembrar.
 */
class ReportesCitasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private string $limpieza;

    private string $blanqueamiento;

    private string $d3;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2031-03-15 18:00:00');
        $this->crearEscenario();

        $this->limpieza = $this->servicio('Limpieza de prueba', true);
        $this->blanqueamiento = $this->servicio('Blanqueamiento de prueba', false);

        // Un dentista que hoy está inactivo y otro, activo, que no tiene ninguna cita.
        [$inactivo, $this->d3] = $this->crearPersonal('dentista', 'Ivan', 'Inactivo');
        DB::table('usuarios')->where('id', $inactivo->id)->update(['activo' => 0]);
        $this->crearPersonal('dentista', 'Nadia', 'Nueva');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function servicio(string $nombre, bool $activo): string
    {
        $id = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $id, 'nombre' => $nombre, 'activo' => $activo ? 1 : 0]);

        return $id;
    }

    /** Como `cita()` del trait, con servicio y fecha de creación (el trait no los admite). */
    private function citaCon(string $dentista, string $estado, string $inicio, ?string $servicio = null, ?string $creadoEn = null): void
    {
        DB::table('citas')->insert(array_filter([
            'id' => (string) Str::uuid(),
            'paciente_id' => $this->p1,
            'dentista_id' => $dentista,
            'servicio_id' => $servicio,
            'fecha_hora' => $inicio.':00',
            'duracion_min' => 30,
            'estado' => $estado,
            'notas_paciente' => 'Nota del paciente que no debe salir',
            'notas_internas' => 'Nota interna que no debe salir',
            'creado_en' => $creadoEn,
        ], fn ($valor) => $valor !== null));
    }

    private function periodoVacio(string $desde, string $hasta): void
    {
        $this->assertSame(0, DB::table('citas')->whereBetween('fecha_hora', ["{$desde} 00:00:00", "{$hasta} 23:59:59"])->count(),
            "El periodo de prueba {$desde}–{$hasta} no tiene citas antes de sembrar.");
    }

    private function generar(string $desde, string $hasta): array
    {
        return app(ReporteCitas::class)->generar($desde, $hasta);
    }

    /** Nueve citas en marzo de 2031: los seis estados, tres dentistas, dos servicios y dos sin servicio. */
    private function sembrarMarzo(): void
    {
        $this->periodoVacio('2031-03-01', '2031-03-31');

        $this->citaCon($this->d1, 'completada', '2031-03-03 10:00', $this->limpieza);
        $this->citaCon($this->d1, 'completada', '2031-03-04 10:00', $this->limpieza);
        $this->citaCon($this->d1, 'cancelada', '2031-03-05 10:00', $this->limpieza);
        $this->citaCon($this->d1, 'no_asistio', '2031-03-06 10:00', $this->blanqueamiento);
        $this->citaCon($this->d1, 'agendada', '2031-03-20 10:00');
        $this->citaCon($this->d2, 'confirmada', '2031-03-07 10:00', $this->blanqueamiento);
        $this->citaCon($this->d2, 'en_curso', '2031-03-08 10:00', $this->limpieza);
        $this->citaCon($this->d2, 'cancelada', '2031-03-09 10:00');
        $this->citaCon($this->d3, 'completada', '2031-03-10 10:00');
    }

    public function test_una_cita_cuenta_por_su_fecha_agendada_y_no_por_la_de_creacion(): void
    {
        $this->periodoVacio('2031-06-01', '2031-07-31');
        $this->citaCon($this->d1, 'agendada', '2031-07-10 09:00', null, '2031-06-01 12:00:00');

        $this->assertSame(0, $this->generar('2031-06-01', '2031-06-30')['total'], 'No cuenta en el mes en que se creó.');
        $this->assertSame(1, $this->generar('2031-07-01', '2031-07-31')['total'], 'Cuenta en el mes para el que está agendada.');
    }

    public function test_citas_por_estado_muestra_los_seis_estados_con_su_porcentaje(): void
    {
        $this->sembrarMarzo();

        $reporte = $this->generar('2031-03-01', '2031-03-31');

        $this->assertSame(9, $reporte['total']);
        $this->assertSame([
            ['estado' => 'agendada', 'citas' => 1, 'porcentaje' => 11.1],
            ['estado' => 'confirmada', 'citas' => 1, 'porcentaje' => 11.1],
            ['estado' => 'en_curso', 'citas' => 1, 'porcentaje' => 11.1],
            ['estado' => 'completada', 'citas' => 3, 'porcentaje' => 33.3],
            ['estado' => 'cancelada', 'citas' => 2, 'porcentaje' => 22.2],
            ['estado' => 'no_asistio', 'citas' => 1, 'porcentaje' => 11.1],
        ], $reporte['porEstado']);
        $this->assertSame($reporte['total'], array_sum(array_column($reporte['porEstado'], 'citas')));
    }

    public function test_los_estados_sin_citas_aparecen_con_cero(): void
    {
        $this->periodoVacio('2031-08-01', '2031-08-31');
        $this->citaCon($this->d1, 'completada', '2031-08-05 10:00');

        $porEstado = $this->generar('2031-08-01', '2031-08-31')['porEstado'];

        $this->assertCount(6, $porEstado);
        $this->assertSame(['estado' => 'agendada', 'citas' => 0, 'porcentaje' => 0.0], $porEstado[0]);
        $this->assertSame(['estado' => 'completada', 'citas' => 1, 'porcentaje' => 100.0], $porEstado[3]);
    }

    public function test_las_tasas_de_cancelacion_e_inasistencia(): void
    {
        $this->sembrarMarzo();

        $reporte = $this->generar('2031-03-01', '2031-03-31');

        $this->assertSame(['citas' => 2, 'porcentaje' => 22.2], $reporte['cancelacion']);
        $this->assertSame(['citas' => 1, 'porcentaje' => 11.1], $reporte['inasistencia']);
    }

    public function test_citas_por_dentista_ordena_y_cuadra_con_el_total(): void
    {
        $this->sembrarMarzo();

        $reporte = $this->generar('2031-03-01', '2031-03-31');

        $this->assertSame([
            ['nombre' => 'Diego Primero', 'total' => 5, 'completadas' => 2, 'canceladas' => 1, 'no_asistio' => 1],
            ['nombre' => 'Dora Segunda', 'total' => 3, 'completadas' => 0, 'canceladas' => 1, 'no_asistio' => 0],
            ['nombre' => 'Ivan Inactivo', 'total' => 1, 'completadas' => 1, 'canceladas' => 0, 'no_asistio' => 0],
        ], $reporte['porDentista']['filas']);
        $this->assertSame(['total' => 9, 'completadas' => 3, 'canceladas' => 2, 'no_asistio' => 1], $reporte['porDentista']['totales']);
        $this->assertSame($reporte['total'], $reporte['porDentista']['totales']['total']);
    }

    public function test_los_dentistas_empatados_se_ordenan_por_nombre_y_apellido(): void
    {
        $this->periodoVacio('2031-04-01', '2031-04-30');
        // Dora y Diego empatan a dos citas; Ivan, con tres, va primero.
        foreach (['2031-04-02', '2031-04-03'] as $dia) {
            $this->citaCon($this->d2, 'completada', "{$dia} 10:00");
            $this->citaCon($this->d1, 'completada', "{$dia} 11:00");
        }
        foreach (['2031-04-02', '2031-04-03', '2031-04-04'] as $dia) {
            $this->citaCon($this->d3, 'agendada', "{$dia} 12:00");
        }

        $nombres = array_column($this->generar('2031-04-01', '2031-04-30')['porDentista']['filas'], 'nombre');

        $this->assertSame(['Ivan Inactivo', 'Diego Primero', 'Dora Segunda'], $nombres);
    }

    public function test_un_dentista_inactivo_con_citas_aparece_y_uno_sin_citas_no(): void
    {
        $this->sembrarMarzo();

        $nombres = array_column($this->generar('2031-03-01', '2031-03-31')['porDentista']['filas'], 'nombre');

        $this->assertContains('Ivan Inactivo', $nombres);
        $this->assertNotContains('Nadia Nueva', $nombres);
    }

    public function test_servicios_mas_solicitados_cuenta_las_no_canceladas(): void
    {
        $this->sembrarMarzo();

        $porServicio = $this->generar('2031-03-01', '2031-03-31')['porServicio'];

        // Siete no canceladas: tres de limpieza (la cancelada no cuenta; la inasistencia de
        // blanqueamiento sí), dos de blanqueamiento y dos sin servicio.
        $this->assertSame(7, $porServicio['total']);
        $this->assertSame(['nombre' => 'Limpieza de prueba', 'citas' => 3, 'porcentaje' => 42.9], $porServicio['filas'][0]);
    }

    public function test_las_citas_sin_servicio_y_el_servicio_inactivo(): void
    {
        $this->sembrarMarzo();

        $filas = $this->generar('2031-03-01', '2031-03-31')['porServicio']['filas'];

        // El servicio inactivo sigue con su nombre; «Sin servicio» se ordena como una fila más
        // (empata a dos con blanqueamiento y va después por nombre).
        $this->assertSame([
            ['nombre' => 'Limpieza de prueba', 'citas' => 3, 'porcentaje' => 42.9],
            ['nombre' => 'Blanqueamiento de prueba', 'citas' => 2, 'porcentaje' => 28.6],
            ['nombre' => 'Sin servicio', 'citas' => 2, 'porcentaje' => 28.6],
        ], $filas);
    }

    public function test_un_periodo_sin_citas_da_total_cero(): void
    {
        $this->periodoVacio('2031-09-01', '2031-09-30');

        $reporte = $this->generar('2031-09-01', '2031-09-30');

        $this->assertSame(0, $reporte['total']);
        $this->assertSame([], $reporte['porDentista']['filas']);
        $this->assertSame([], $reporte['porServicio']['filas']);
    }

    public function test_dos_consultas_del_mismo_periodo_dan_las_mismas_cifras(): void
    {
        $this->sembrarMarzo();

        $this->assertSame($this->generar('2031-03-01', '2031-03-31'), $this->generar('2031-03-01', '2031-03-31'));
    }
}
