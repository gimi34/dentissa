<?php

namespace Tests\Feature;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use App\Auditoria\AuditoriaNoDisponible;
use App\Pacientes\Ciudades;
use App\Pacientes\CorreccionCiudades;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\EventosDuranteLaPrueba;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/** Spec 051 (CA8, CA9, CA12; TM5, TM6): la corrección de las ciudades ya guardadas. */
class CorreccionCiudadesTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;
    use EventosDuranteLaPrueba;

    private const ACCION = 'pacientes_ciudades_corregidas';

    /** Ciudad guardada → ciudad esperada tras corregir. */
    private const CASOS = [
        'lazaro cardenas' => 'Lázaro Cárdenas',
        'MORELIA ' => 'Morelia',
        'Zihuatanejo' => 'Zihuatanejo de Azueta',
        'La Unión' => 'La Unión de Isidoro Montes de Oca',
        'LC' => 'LC',
        'Petatlán' => 'Petatlán',
    ];

    /** @var array<string, string> id del paciente → ciudad guardada */
    private array $pacientes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();

        foreach (array_keys(self::CASOS) as $indice => $ciudad) {
            [, $paciente] = $this->crearPacienteDePrueba($this->d1, 'Ciudad', "Caso{$indice}");
            DB::table('pacientes')->where('id', $paciente)->update(['ciudad' => $ciudad]);
            $this->pacientes[$paciente] = (string) $ciudad;
        }
    }

    public function test_la_correccion_arregla_las_que_coinciden(): void
    {
        app(CorreccionCiudades::class)->ejecutar();

        foreach ($this->pacientes as $paciente => $ciudad) {
            if (Ciudades::normalizar($ciudad) !== null) {
                $this->assertSame(self::CASOS[$ciudad], $this->ciudadDe($paciente), "«{$ciudad}» queda con el nombre de la lista.");
            }
        }
    }

    public function test_la_correccion_conserva_las_que_no_coinciden(): void
    {
        $sinCiudad = $this->p2;
        DB::table('pacientes')->where('id', $sinCiudad)->update(['ciudad' => null]);

        app(CorreccionCiudades::class)->ejecutar();

        $this->assertSame('LC', $this->ciudadDe($this->idDe('LC')));
        $this->assertNull($this->ciudadDe($sinCiudad), 'Un paciente sin ciudad sigue sin ciudad.');
    }

    public function test_la_correccion_devuelve_los_totales(): void
    {
        $antes = $this->fueraDeLaListaSinCorregir();

        $totales = app(CorreccionCiudades::class)->ejecutar();

        $this->assertGreaterThanOrEqual(4, $totales['corregidos']);
        $this->assertSame($antes, $totales['fuera_de_lista']);
    }

    public function test_la_correccion_dos_veces_no_cambia_nada(): void
    {
        $primera = app(CorreccionCiudades::class)->ejecutar();
        $despues = DB::table('pacientes')->orderBy('id')->pluck('ciudad', 'id')->all();

        $segunda = app(CorreccionCiudades::class)->ejecutar();

        $this->assertSame(0, $segunda['corregidos']);
        $this->assertSame($primera['fuera_de_lista'], $segunda['fuera_de_lista']);
        $this->assertSame($despues, DB::table('pacientes')->orderBy('id')->pluck('ciudad', 'id')->all());
    }

    public function test_la_correccion_registra_un_evento_con_totales_y_sin_valores(): void
    {
        $totales = app(CorreccionCiudades::class)->ejecutar();

        // Solo el de este test: la migración deja el suyo antes de la suite (/review R7).
        $evento = $this->auditoriaDelTest()->where('accion', self::ACCION)->sole();

        $this->assertSame('exito', $evento->resultado);
        $this->assertSame('pacientes', $evento->recurso_tipo);
        $this->assertNull($evento->recurso_id);
        $this->assertNull($evento->actor_id, 'El actor es el sistema.');
        $this->assertSame(['ciudad'], json_decode($evento->campos, true));

        $detalle = json_decode($evento->detalle, true);
        $this->assertSame(['corregidos', 'fuera_de_lista'], array_keys($detalle));
        $this->assertSame($totales['corregidos'], $detalle['corregidos']);
        $this->assertSame($totales['fuera_de_lista'], $detalle['fuera_de_lista']);

        $registro = json_encode($evento, JSON_UNESCAPED_UNICODE);
        foreach (['Lázaro', 'lazaro', 'Zihuatanejo', 'LC"', 'Ciudad '] as $dato) {
            $this->assertStringNotContainsString($dato, $registro, 'Sin ciudades ni nombres en el evento.');
        }
    }

    public function test_si_la_auditoria_falla_no_se_corrige_nada(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->app->forgetInstance(Auditoria::class);

        try {
            app(CorreccionCiudades::class)->ejecutar();
            $this->fail('La corrección tenía que detenerse sin auditoría.');
        } catch (AuditoriaNoDisponible) {
            // Esperado: sin constancia no se corrige.
        }

        foreach ($this->pacientes as $paciente => $ciudad) {
            $this->assertSame($ciudad, $this->ciudadDe($paciente), "«{$ciudad}» sigue como estaba.");
        }
    }

    /** /review R5: la migración llama a la corrección y deja su evento. */
    public function test_la_migracion_ejecuta_la_correccion(): void
    {
        $migracion = require database_path('migrations/2026_11_18_000000_corregir_ciudades_de_pacientes.php');

        $migracion->up();

        $this->assertSame('Lázaro Cárdenas', $this->ciudadDe($this->idDe('lazaro cardenas')));
        $this->assertSame(1, $this->auditoriaDelTest()->where('accion', self::ACCION)->count());
    }

    private function ciudadDe(string $paciente): ?string
    {
        return DB::table('pacientes')->where('id', $paciente)->value('ciudad');
    }

    private function idDe(string $ciudad): string
    {
        return (string) array_search($ciudad, $this->pacientes, true);
    }

    /** Pacientes con una ciudad que ni está en la lista ni se puede llevar a ella. */
    private function fueraDeLaListaSinCorregir(): int
    {
        return DB::table('pacientes')->whereNotNull('ciudad')->pluck('ciudad')
            ->filter(fn (string $ciudad) => Str::of($ciudad)->trim()->isNotEmpty() && Ciudades::normalizar($ciudad) === null)
            ->count();
    }
}
