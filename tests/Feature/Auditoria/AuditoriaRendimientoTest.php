<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\Auditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Spec 020, requisitos no funcionales (plan D14): registrar no añade más de 50 ms y la consulta
 * con filtros responde en menos de 2 s con 100.000 eventos, los dos en el percentil 95.
 *
 * Fuera de la suite por defecto (`php artisan test --group=rnf`). Los eventos sintéticos se
 * insertan dentro de la transacción del test, que se revierte al final.
 */
#[Group('rnf')]
class AuditoriaRendimientoTest extends TestCase
{
    use DatabaseTransactions;

    private const EVENTOS = 100_000;

    private const ACCIONES = ['acceso_denegado', 'auditoria_consultada', 'expediente_consultado', 'paciente_actualizado', 'cita_reprogramada'];

    private const TIPOS = ['citas', 'expedientes', 'pacientes', 'auditoria', 'dashboard'];

    /** @var list<string> */
    private array $actores = [];

    private function sembrar(): void
    {
        $this->actores = array_map(fn () => (string) Str::uuid(), range(1, 50));
        $inicio = now('UTC')->subYears(2)->getTimestamp();
        $lote = [];
        for ($n = 0; $n < self::EVENTOS; $n++) {
            $lote[] = [
                'ocurrido_en' => gmdate('Y-m-d H:i:s', $inicio + $n * 600).'.000000',
                'actor_id' => $this->actores[$n % 50],
                'actor_rol' => 'dentista',
                'accion' => self::ACCIONES[$n % 5],
                'recurso_tipo' => self::TIPOS[intdiv($n, 7) % 5],
                'recurso_id' => 'r_'.($n % 5000),
                'resultado' => $n % 3 === 0 ? 'denegado' : 'exito',
                'ip' => '192.0.2.'.($n % 250),
                'request_id' => (string) Str::uuid(),
            ];
            if (count($lote) === 2000) {
                DB::table('auditoria')->insert($lote);
                $lote = [];
            }
        }
    }

    /** @param  list<float>  $muestras  en milisegundos */
    private function p95(array $muestras): float
    {
        sort($muestras);

        return $muestras[(int) ceil(0.95 * count($muestras)) - 1];
    }

    public function test_registrar_tarda_menos_de_50_ms_en_p95(): void
    {
        $this->sembrar();
        $auditoria = app(Auditoria::class);

        $muestras = [];
        for ($n = 0; $n < 200; $n++) {
            $antes = hrtime(true);
            $auditoria->registrar('prueba_rendimiento', 'exito', 'citas', 'r_'.$n, detalle: ['n' => $n]);
            $muestras[] = (hrtime(true) - $antes) / 1e6;
        }

        $p95 = $this->p95($muestras);
        fwrite(STDERR, sprintf("\n[rnf] registrar: p95 %.2f ms (200 llamadas, %d eventos)\n", $p95, self::EVENTOS));
        $this->assertLessThan(50, $p95);
    }

    public function test_la_consulta_con_filtros_tarda_menos_de_2_s_en_p95(): void
    {
        $this->sembrar();
        $admin = Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'Rendimiento', 'email' => 'rnf.'.Str::random(8).'@example.test',
            'rol' => 'admin', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
        $filtros = [
            [], ['page' => 40], ['accion' => 'acceso_denegado'], ['recurso_tipo' => 'expedientes'],
            ['actor' => $this->actores[3]], ['actor' => $this->actores[7], 'accion' => 'cita_reprogramada'],
            ['recurso_tipo' => 'citas', 'recurso_id' => 'r_42'], ['recurso_id' => 'r_4999'],
            ['desde' => now()->subYear()->toDateString()], ['hasta' => now()->subYear()->toDateString()],
            ['desde' => now()->subMonths(6)->toDateString(), 'hasta' => now()->subMonths(5)->toDateString()],
            ['accion' => 'paciente_actualizado', 'recurso_tipo' => 'pacientes'], ['accion' => 'auditoria_consultada', 'page' => 100],
            ['actor' => $this->actores[49], 'desde' => now()->subYear()->toDateString()], ['recurso_tipo' => 'dashboard', 'page' => 3],
            ['accion' => 'expediente_consultado', 'hasta' => now()->subMonths(3)->toDateString()], ['actor' => (string) Str::uuid()],
            ['accion' => 'accion_sin_eventos'], ['recurso_tipo' => 'auditoria', 'recurso_id' => 'r_1'],
            ['desde' => now()->subYears(2)->toDateString(), 'hasta' => now()->toDateString(), 'accion' => 'acceso_denegado'],
        ];

        $muestras = [];
        foreach ($filtros as $filtro) {
            $antes = hrtime(true);
            $this->actingAs($admin)->get('/admin/auditoria'.($filtro ? '?'.http_build_query($filtro) : ''))->assertOk();
            $muestras[] = (hrtime(true) - $antes) / 1e6;
        }

        $p95 = $this->p95($muestras);
        fwrite(STDERR, sprintf("\n[rnf] consulta: p95 %.0f ms (%d consultas, %d eventos)\n", $p95, count($filtros), self::EVENTOS));
        $this->assertCount(20, $filtros);
        $this->assertLessThan(2000, $p95);
    }
}
