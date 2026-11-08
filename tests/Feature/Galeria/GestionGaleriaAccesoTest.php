<?php

namespace Tests\Feature\Galeria;

use App\Models\GaleriaFoto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\GaleriaDePrueba;
use Tests\TestCase;

/**
 * Spec 039 (T013; CA13 (abuso); TM1; P3): solo el administrador gestiona la galería. Cada
 * acción de `admin.galeria.*` × dentista, asistente y paciente da 403 sin cambiar nada; sin sesión
 * lleva al inicio de sesión. El caso permitido del admin está en `SubidaFotoGaleriaTest` y en
 * `GestionGaleriaTest` (plan P3).
 */
class GestionGaleriaAccesoTest extends TestCase
{
    use DatabaseTransactions;
    use GaleriaDePrueba;

    private GaleriaFoto $f1;

    private GaleriaFoto $f2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
        foreach ([1, 2] as $posicion) {
            $foto = GaleriaFoto::create([
                'descripcion' => "Trabajo {$posicion}", 'posicion' => $posicion, 'con_paciente' => false,
                'ancho' => 1600, 'alto' => 1200, 'bytes' => 10, 'bytes_pequena' => 10,
            ]);
            Storage::disk('galeria')->put($foto->archivo(1600), 'x');
            Storage::disk('galeria')->put($foto->archivo(800), 'x');
            $this->{"f{$posicion}"} = $foto;
        }
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($rol),
            'apellido' => 'De Pruebas',
            'email' => "{$rol}.galeria.".Str::random(8).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** La galería tal como está: filas, orden y archivos. */
    private function estado(): array
    {
        return [
            GaleriaFoto::ordenadas()->get()->map(fn ($f) => [$f->id, $f->posicion, $f->descripcion])->all(),
            Storage::disk('galeria')->allFiles(),
        ];
    }

    /** La petición de cada acción, como la haría alguien a mano. */
    private function peticion(string $accion)
    {
        return match ($accion) {
            'index' => $this->get(route('admin.galeria.index')),
            'store' => $this->post(route('admin.galeria.store'), [
                'foto' => $this->imagenLisa('jpg'), 'descripcion' => 'Intrusa', 'con_paciente' => 'no',
            ]),
            'subir' => $this->patch(route('admin.galeria.subir', $this->f2)),
            'bajar' => $this->patch(route('admin.galeria.bajar', $this->f1)),
            'quitar' => $this->get(route('admin.galeria.quitar', $this->f1)),
            'destroy' => $this->delete(route('admin.galeria.destroy', $this->f1)),
        };
    }

    public static function rolesPorAccion(): array
    {
        $casos = [];
        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            foreach (['index', 'store', 'subir', 'bajar', 'quitar', 'destroy'] as $accion) {
                $casos["{$rol} · {$accion}"] = [$rol, $accion];
            }
        }

        return $casos;
    }

    #[DataProvider('rolesPorAccion')]
    public function test_otros_roles_reciben_403_en_cada_accion(string $rol, string $accion): void
    {
        $antes = $this->estado();
        $this->actingAs($this->usuario($rol));

        $this->peticion($accion)->assertForbidden();

        $this->assertSame($antes, $this->estado(), 'La galería no cambia.');
    }

    public static function acciones(): array
    {
        return array_combine(
            ['index', 'store', 'subir', 'bajar', 'quitar', 'destroy'],
            array_map(fn ($a) => [$a], ['index', 'store', 'subir', 'bajar', 'quitar', 'destroy']),
        );
    }

    #[DataProvider('acciones')]
    public function test_sin_sesion_va_al_inicio_de_sesion(string $accion): void
    {
        $antes = $this->estado();

        $this->peticion($accion)->assertRedirect(route('login'));

        $this->assertSame($antes, $this->estado());
    }

    public function test_solo_el_admin_ve_la_entrada_galeria_del_menu(): void
    {
        $this->actingAs($this->usuario('admin'));
        $html = view('placeholder')->render();
        $this->assertMatchesRegularExpression('#<a href="/admin/galeria"[^>]*>.*?Galería\s*</a>#s', $html, 'El admin ve «Galería».');
        $servicios = strpos($html, 'href="/admin/servicios"');
        $galeria = strpos($html, 'href="/admin/galeria"');
        $reportes = strpos($html, 'href="/admin/reportes"');
        $this->assertTrue($servicios < $galeria && $galeria < $reportes, 'Va después de «Servicios».');

        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $html = view('placeholder')->render();
            $this->assertStringNotContainsString('/admin/galeria', $html, "El {$rol} no ve la entrada.");
            $this->assertStringNotContainsString('>Galería', $html, "Ni el texto, como {$rol}.");
        }
    }
}
