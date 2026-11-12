<?php

namespace Tests\Feature\Reportes;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 047, CA9 (TM1, TM6, TM7; P3, P7 y P8): solo la administración ve los reportes.
 *
 * Los de rol, auditoría, menú, no-caché e identificador de petición son de caracterización: la
 * ruta ya existía bajo `role:admin`. Cada uno se comprobó rompiendo la pieza que lo protege
 * (nota de T010 en tasks.md).
 */
class ReportesAccesoTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const PERIODO = '?desde=2031-03-01&hasta=2031-03-31';

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearEscenario();
    }

    private function usuario(string $rol): Usuario
    {
        return match ($rol) {
            'dentista' => $this->dentista1,
            'asistente' => $this->asistente,
            'cliente' => $this->paciente1,
            default => $this->admin,
        };
    }

    /** @return array<string, array{0: string, 1: string}> rol y dirección */
    public static function rolesSinAcceso(): array
    {
        $casos = [];
        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            $casos["{$rol}, sin periodo"] = [$rol, '/admin/reportes'];
            $casos["{$rol}, con periodo"] = [$rol, '/admin/reportes'.self::PERIODO];
        }

        return $casos;
    }

    public function test_la_administracion_ve_los_reportes(): void
    {
        $this->actingAs($this->admin)->get('/admin/reportes')->assertOk();
        $this->actingAs($this->admin)->get('/admin/reportes'.self::PERIODO)->assertOk();
    }

    #[DataProvider('rolesSinAcceso')]
    public function test_solo_la_administracion_ve_los_reportes(string $rol, string $direccion): void
    {
        $respuesta = $this->actingAs($this->usuario($rol))->get($direccion);

        $respuesta->assertForbidden();
        $respuesta->assertDontSee('Citas por estado');
    }

    public function test_el_gate_deniega_a_quien_no_es_admin(): void
    {
        $this->assertTrue(Gate::has('ver-reportes'), 'El Gate ver-reportes está definido.');
        $this->assertTrue(Gate::forUser($this->admin)->allows('ver-reportes'));

        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            $this->assertFalse(Gate::forUser($this->usuario($rol))->allows('ver-reportes'), "El rol {$rol} no pasa el Gate.");
        }
    }

    public function test_sin_sesion_lleva_al_inicio_de_sesion(): void
    {
        $this->get('/admin/reportes')->assertRedirect('/login');
        $this->get('/admin/reportes'.self::PERIODO)->assertRedirect('/login');
    }

    public function test_el_acceso_denegado_queda_en_el_registro_de_auditoria(): void
    {
        $this->actingAs($this->dentista1)->get('/admin/reportes'.self::PERIODO)->assertForbidden();

        // Solo los eventos de este actor: la tabla puede traer denegaciones ajenas al test (/review R4).
        $evento = DB::table('auditoria')->where('accion', 'acceso_denegado')
            ->where('actor_id', $this->dentista1->id)->get()->sole();
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'admin.reportes'], json_decode($evento->detalle, true));
        $this->assertSame($this->dentista1->id, $evento->actor_id);
        $this->assertSame('dentista', $evento->actor_rol);
        $this->assertNotNull($evento->ocurrido_en);
    }

    public function test_reportes_solo_esta_en_el_menu_de_la_administracion(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('href="/admin/reportes"', false);

        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))->get("/{$rol}")->assertOk()->assertDontSee('/admin/reportes', false);
        }
    }

    public function test_la_respuesta_no_se_guarda_en_cache(): void
    {
        $cabecera = (string) $this->actingAs($this->admin)->get('/admin/reportes')->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cabecera);
        $this->assertStringContainsString('no-cache', $cabecera);
    }

    public function test_la_respuesta_lleva_el_identificador_de_peticion(): void
    {
        $identificador = '0b6f6f1e-3c1d-4a53-9a55-2f8d3c1a7e10';

        $this->actingAs($this->admin)->withHeader('X-Request-Id', $identificador)->get('/admin/reportes')
            ->assertOk()->assertHeader('X-Request-Id', $identificador);
    }
}
