<?php

namespace Tests\Feature\Galeria;

use App\Auditoria\AlmacenAuditoria;
use App\Models\GaleriaFoto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\GaleriaDePrueba;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 039 (T016; CA16; TM7; P7): publicar, mover y quitar quedan en el registro de auditoría de
 * la spec 020, en modo crítico y sin la descripción ni la imagen; el 403 con sesión, como
 * `acceso_denegado` del registro central. Vocabulario del registro de la 020 (/analyze A17).
 */
class AuditoriaGaleriaTest extends TestCase
{
    use DatabaseTransactions;
    use GaleriaDePrueba;

    private const DESCRIPCION = 'Carillas de la señora del mercado';

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
        $this->admin = $this->usuario('admin');
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($rol), 'apellido' => 'Auditada',
            'email' => "{$rol}.auditoria.".Str::random(8).'@example.test', 'rol' => $rol,
            'telefono' => '555-0000', 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    private function eventos(string $accion)
    {
        return DB::table('auditoria')->where('accion', $accion)->orderBy('id')->get();
    }

    private function foto(int $posicion): GaleriaFoto
    {
        $foto = GaleriaFoto::create([
            'descripcion' => "Trabajo {$posicion}", 'posicion' => $posicion, 'con_paciente' => false,
            'ancho' => 1600, 'alto' => 1200, 'bytes' => 10, 'bytes_pequena' => 10,
        ]);
        Storage::disk('galeria')->put($foto->archivo(1600), 'x');
        Storage::disk('galeria')->put($foto->archivo(800), 'x');

        return $foto;
    }

    private function publicar(array $mas = [])
    {
        return $this->actingAs($this->admin)->from(route('admin.galeria.index'))->post(route('admin.galeria.store'), array_merge([
            'foto' => $this->imagenLisa('jpg'),
            'descripcion' => self::DESCRIPCION,
            'con_paciente' => 'si',
            'consentimiento' => '1',
        ], $mas));
    }

    private function assertEvento(object $evento, string $recursoId, array $detalle): void
    {
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame($this->admin->id, $evento->actor_id, 'Quién.');
        $this->assertSame('admin', $evento->actor_rol);
        $this->assertSame('galeria_foto', $evento->recurso_tipo);
        $this->assertSame($recursoId, $evento->recurso_id, 'Qué foto.');
        $this->assertSame($detalle, json_decode($evento->detalle, true), 'Con paciente o no, y la posición.');
        $this->assertNotNull($evento->ocurrido_en, 'Cuándo.');
    }

    public function test_publicar_mover_y_quitar_quedan_registrados(): void
    {
        $primera = $this->foto(1);
        $this->publicar()->assertSessionHasNoErrors();
        $nueva = GaleriaFoto::where('posicion', 2)->sole();

        $publicada = $this->eventos('galeria_foto_publicada')->sole();
        $this->assertEvento($publicada, $nueva->id, ['con_paciente' => true, 'posicion' => 2]);

        $this->patch(route('admin.galeria.subir', $nueva))->assertRedirect(route('admin.galeria.index'));
        $movida = $this->eventos('galeria_foto_movida')->sole();
        $this->assertEvento($movida, $nueva->id, ['con_paciente' => true, 'posicion' => 1, 'direccion' => 'arriba']);

        $this->patch(route('admin.galeria.bajar', $nueva))->assertRedirect(route('admin.galeria.index'));
        $this->assertSame(['con_paciente' => true, 'posicion' => 2, 'direccion' => 'abajo'], json_decode($this->eventos('galeria_foto_movida')->last()->detalle, true));

        $this->patch(route('admin.galeria.subir', $primera))->assertRedirect(route('admin.galeria.index'));
        $this->assertCount(2, $this->eventos('galeria_foto_movida'), 'Subir la primera no mueve nada ni deja evento.');

        $this->delete(route('admin.galeria.destroy', $nueva))->assertRedirect(route('admin.galeria.index'));
        $quitada = $this->eventos('galeria_foto_quitada')->sole();
        $this->assertEvento($quitada, $nueva->id, ['con_paciente' => true, 'posicion' => 2]);
    }

    public function test_el_evento_no_lleva_la_descripcion(): void
    {
        $this->publicar()->assertSessionHasNoErrors();
        $foto = GaleriaFoto::sole();
        $this->delete(route('admin.galeria.destroy', $foto));

        $guardado = json_encode(DB::table('auditoria')->where('accion', 'like', 'galeria_foto_%')->get());
        $this->assertStringNotContainsString('señora', $guardado, 'Sin la descripción.');
        $this->assertStringNotContainsString('mercado', $guardado);
        $this->assertStringNotContainsString('.webp', $guardado, 'Ni el archivo.');
        $this->assertStringNotContainsString('RIFF', $guardado, 'Ni la imagen.');
    }

    public function test_si_la_auditoria_falla_no_se_publica_nada(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));

        $this->publicar()->assertStatus(503);

        $this->assertSame(0, GaleriaFoto::count(), 'Sin fila.');
        $this->assertSame([], Storage::disk('galeria')->allFiles(), 'Sin archivo.');
    }

    public function test_si_la_auditoria_falla_no_se_mueve_ni_se_quita_nada(): void
    {
        $f1 = $this->foto(1);
        $f2 = $this->foto(2);
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->actingAs($this->admin);

        $this->patch(route('admin.galeria.subir', $f2))->assertStatus(503);
        $this->delete(route('admin.galeria.destroy', $f1))->assertStatus(503);

        $this->assertSame([$f1->id, $f2->id], GaleriaFoto::ordenadas()->pluck('id')->all(), 'El orden no cambia y no se quita nada.');
        $this->assertCount(4, Storage::disk('galeria')->allFiles(), 'Los archivos siguen.');
    }

    public function test_el_acceso_denegado_queda_registrado(): void
    {
        $dentista = $this->usuario('dentista');

        $this->actingAs($dentista)->post(route('admin.galeria.store'), [
            'foto' => $this->imagenLisa('jpg'), 'descripcion' => 'Intrusa', 'con_paciente' => 'no',
        ])->assertForbidden();

        $evento = $this->eventos('acceso_denegado')->sole();
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame($dentista->id, $evento->actor_id);
        $this->assertSame('dentista', $evento->actor_rol);
        $this->assertSame('galeria', $evento->recurso_tipo);
        $this->assertSame(['accion_solicitada' => 'admin.galeria.store'], json_decode($evento->detalle, true));
    }
}
