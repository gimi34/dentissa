<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 020, CA2, CA5 y CA12 (TM1, TM2, TM9, TM14): el registro es solo de anexado y la purga
 * mensual borra lo de más de 5 años, siempre con su propio evento.
 *
 * Los triggers se prueban contra MariaDB real y con la conexión de la propia aplicación. El
 * trigger mide con el reloj de la base (`UTC_TIMESTAMP()`), así que los tests que borran
 * eventos antiguos congelan Carbon en el instante real; solo el del año bisiesto usa una fecha
 * inventada, y en él no hay eventos que el trigger tenga que evaluar.
 */
class InmutabilidadAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private string $zonaDeLaSesion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zonaDeLaSesion = DB::selectOne('SELECT @@session.time_zone AS zona')->zona;
    }

    protected function tearDown(): void
    {
        // La conexión sobrevive al test: se deja como estaba.
        DB::statement('SET @auditoria_purga = NULL');
        DB::statement('SET time_zone = ?', [$this->zonaDeLaSesion]);

        parent::tearDown();
    }

    /** Inserta un evento sin pasar por `Auditoria`: el fixture no depende del código bajo prueba. */
    private function evento(Carbon|string $ocurridoEn, string $accion = 'prueba_inmutable'): int
    {
        return DB::table('auditoria')->insertGetId([
            'ocurrido_en' => $ocurridoEn instanceof Carbon ? $ocurridoEn->format('Y-m-d H:i:s.u') : $ocurridoEn,
            'accion' => $accion,
            'resultado' => 'exito',
            'request_id' => (string) Str::uuid(),
        ]);
    }

    private function assertRechazado(callable $operacion, string $mensaje): void
    {
        try {
            $operacion();
            $this->fail($mensaje);
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->errorInfo[0] ?? null, 'Lo rechaza el trigger (SIGNAL 45000).');
        }
    }

    private function hace(int $anios): Carbon
    {
        return Carbon::now('UTC')->subYears($anios);
    }

    public function test_un_evento_no_se_puede_modificar(): void
    {
        $id = $this->evento($this->hace(0));

        $this->assertRechazado(
            fn () => DB::table('auditoria')->where('id', $id)->update(['resultado' => 'denegado', 'actor_id' => null]),
            'Un evento no se puede modificar.',
        );
        $this->assertSame('exito', DB::table('auditoria')->where('id', $id)->value('resultado'));
    }

    public function test_un_evento_no_se_puede_borrar(): void
    {
        $id = $this->evento($this->hace(0));

        $this->assertRechazado(fn () => DB::table('auditoria')->where('id', $id)->delete(), 'Un evento no se puede borrar.');
        $this->assertTrue(DB::table('auditoria')->where('id', $id)->exists());
    }

    public function test_un_borrado_masivo_se_rechaza(): void
    {
        $this->evento($this->hace(0));
        $this->evento($this->hace(1));
        $this->evento($this->hace(7));
        $antes = DB::table('auditoria')->count();

        $this->assertRechazado(fn () => DB::table('auditoria')->delete(), 'Un borrado sin condición se rechaza.');
        $this->assertSame($antes, DB::table('auditoria')->count(), 'No cambia ningún evento.');
    }

    public function test_la_purga_no_borra_eventos_recientes_aunque_se_active(): void
    {
        $reciente = $this->evento($this->hace(4));

        DB::statement('SET @auditoria_purga = 1');
        $this->assertRechazado(fn () => DB::table('auditoria')->where('id', $reciente)->delete(), 'El indicador no basta: el evento tiene menos de 5 años.');
        $this->assertTrue(DB::table('auditoria')->where('id', $reciente)->exists());
    }

    public function test_la_purga_elimina_solo_eventos_de_mas_de_5_anios(): void
    {
        $antiguo = $this->evento($this->hace(6));
        $reciente = $this->evento($this->hace(4));

        $this->artisan('auditoria:purgar')->assertExitCode(0);

        $this->assertFalse(DB::table('auditoria')->where('id', $antiguo)->exists());
        $this->assertTrue(DB::table('auditoria')->where('id', $reciente)->exists());
    }

    /**
     * A41, A56: con la sesión en otra zona, un trigger que midiera con `NOW()` en lugar de
     * `UTC_TIMESTAMP()` adelantaría el corte seis horas y rechazaría el evento de corte − 1 s.
     */
    public function test_la_purga_respeta_el_corte_al_segundo(): void
    {
        DB::statement("SET time_zone = '-06:00'");
        Carbon::setTestNow(Carbon::now('UTC')->startOfSecond()->addMilliseconds(500));
        $corte = Carbon::now('UTC')->subYearsNoOverflow(5)->startOfSecond();
        $antes = $this->evento($corte->copy()->subSecond());
        $despues = $this->evento($corte->copy()->addSecond());

        $this->artisan('auditoria:purgar')->assertExitCode(0);

        $this->assertFalse(DB::table('auditoria')->where('id', $antes)->exists(), 'Corte − 1 s se purga.');
        $this->assertTrue(DB::table('auditoria')->where('id', $despues)->exists(), 'Corte + 1 s se conserva.');
    }

    /** A40, A56: el 29 de febrero menos cinco años es el 28, no el 1 de marzo; y sin fracciones. */
    public function test_el_corte_no_desborda_los_anios_bisiestos(): void
    {
        Carbon::setTestNow('2028-02-29 00:00:00.500');

        $this->artisan('auditoria:purgar')->assertExitCode(0);

        $evento = DB::table('auditoria')->where('accion', 'auditoria_purgada')->sole();
        $this->assertSame('2023-02-28T00:00:00Z', json_decode($evento->detalle, true)['fecha_corte'] ?? null);
    }

    public function test_la_purga_esta_programada_mensualmente(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento) => str_contains($evento->command ?? '', 'auditoria:purgar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('0 3 1 * *', $eventos->first()->expression, 'El día 1 de cada mes a las 03:00.');
        $this->assertTrue($eventos->first()->withoutOverlapping, 'Sin solapes.');
    }

    /** TM14: el scheduler solo programa la purga; dentissa:vigilar sigue en su temporizador. */
    public function test_la_programacion_solo_tiene_la_purga(): void
    {
        $comandos = collect(app(Schedule::class)->events())->map(fn ($evento) => $evento->command ?? $evento->description);

        $this->assertCount(1, $comandos, 'Una sola tarea programada.');
        $this->assertStringContainsString('auditoria:purgar', $comandos->first());
        $this->assertFalse($comandos->contains(fn ($comando) => str_contains((string) $comando, 'dentissa:vigilar')));
    }

    public function test_la_purga_queda_registrada(): void
    {
        $this->evento($this->hace(6));
        $this->evento($this->hace(7));

        $this->artisan('auditoria:purgar')->assertExitCode(0);

        $evento = DB::table('auditoria')->where('accion', 'auditoria_purgada')->sole();
        $this->assertSame('exito', $evento->resultado);
        $this->assertNull($evento->actor_id, 'La purga la hace el sistema.');
        $this->assertMatchesRegularExpression(self::UUID, $evento->request_id);
        $detalle = json_decode($evento->detalle, true);
        $this->assertSame(2, $detalle['eventos_purgados'] ?? null);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $detalle['fecha_corte'] ?? '');
    }

    /** TM9: debe fallar contra una purga que registre en modo no crítico. */
    public function test_si_no_se_puede_registrar_la_purga_no_se_borra_nada(): void
    {
        $this->evento($this->hace(6));
        $this->evento($this->hace(7));
        $antiguos = fn () => DB::table('auditoria')->where('ocurrido_en', '<', $this->hace(5)->format('Y-m-d H:i:s'))->count();
        $this->assertSame(2, $antiguos());
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->app->forgetInstance(Auditoria::class);

        $this->artisan('auditoria:purgar')->assertFailed();

        $this->assertSame(2, $antiguos(), 'El DELETE real se ejecutó y la transacción lo revirtió.');
    }
}
