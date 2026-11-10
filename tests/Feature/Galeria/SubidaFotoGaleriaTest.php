<?php

namespace Tests\Feature\Galeria;

use App\Galeria\FotoNoProcesable;
use App\Galeria\FotoProcesada;
use App\Galeria\ProcesadorFoto;
use App\Models\GaleriaFoto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\GaleriaDePrueba;
use Tests\TestCase;

/**
 * Spec 039 (T011; CA8, CA11, CA12; TM2, TM3; P8): subir una foto desde la gestión.
 * Contrato: `POST /admin/galeria` (`admin.galeria.store`) y los mensajes de plan → Contratos.
 */
class SubidaFotoGaleriaTest extends TestCase
{
    use DatabaseTransactions;
    use GaleriaDePrueba;

    private const NO_ES_IMAGEN = 'El archivo no es una imagen JPG, PNG o WebP.';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
    }

    private function admin(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Galería',
            'email' => 'admin.galeria.'.Str::random(8).'@example.test',
            'rol' => 'admin',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** Una fila de foto ya publicada, sin pasar por el código bajo prueba. */
    private function fotoExistente(int $posicion): GaleriaFoto
    {
        return GaleriaFoto::create([
            'descripcion' => "Foto existente {$posicion}",
            'posicion' => $posicion,
            'con_paciente' => false,
            'ancho' => 1600,
            'alto' => 1200,
            'bytes' => 100000,
            'bytes_pequena' => 30000,
        ]);
    }

    private function subir(array $datos, ?Usuario $quien = null)
    {
        return $this->actingAs($quien ?? $this->admin())
            ->from(route('admin.galeria.index'))
            ->post(route('admin.galeria.store'), $datos);
    }

    private function datos(UploadedFile $foto, array $mas = []): array
    {
        return array_merge(['foto' => $foto, 'descripcion' => 'Resina en un incisivo, antes y después.', 'con_paciente' => 'no'], $mas);
    }

    /** Nada guardado: ni fila nueva ni archivo en el disco. */
    private function assertNadaGuardado(int $filasAntes): void
    {
        $this->assertSame($filasAntes, GaleriaFoto::count(), 'No se publica ninguna foto.');
        $this->assertSame([], Storage::disk('galeria')->allFiles(), 'No queda ningún archivo.');
    }

    public static function formatos(): array
    {
        return ['jpg' => ['jpg'], 'png' => ['png'], 'webp' => ['webp']];
    }

    #[DataProvider('formatos')]
    public function test_publica_cada_formato_al_final(string $formato): void
    {
        $this->fotoExistente(1);

        $this->subir($this->datos($this->imagenLisa($formato, 2000, 1500)))
            ->assertRedirect(route('admin.galeria.index'))
            ->assertSessionHas('success', 'Foto publicada.')
            ->assertSessionHasNoErrors();

        $foto = GaleriaFoto::where('posicion', 2)->first();
        $this->assertNotNull($foto, 'La nueva va al final.');
        $this->assertSame('Resina en un incisivo, antes y después.', $foto->descripcion);
        $this->assertFalse($foto->con_paciente);
        $this->assertNull($foto->consentimiento_usuario_id, 'Sin paciente, sin declaración.');
        $this->assertSame([1600, 1200], [$foto->ancho, $foto->alto]);
        $disco = Storage::disk('galeria');
        $this->assertTrue($disco->exists("{$foto->id}-1600.webp"), 'Guarda el grande con el UUID como nombre.');
        $this->assertTrue($disco->exists("{$foto->id}-800.webp"), 'Y el pequeño.');
        $this->assertSame(strlen($disco->get("{$foto->id}-1600.webp")), $foto->bytes);
        $this->assertSame(strlen($disco->get("{$foto->id}-800.webp")), $foto->bytes_pequena);
        $this->assertCount(2, $disco->allFiles(), 'Nunca el original.');
    }

    public function test_la_descripcion_es_el_alt_en_la_galeria(): void
    {
        $this->subir($this->datos($this->imagenLisa('jpg')))->assertSessionHasNoErrors();

        $this->get(route('galeria.publica'))->assertOk()
            ->assertSee('alt="Resina en un incisivo, antes y después."', false);
    }

    public static function faltas(): array
    {
        return [
            'sin descripción' => [['descripcion' => ''], 'descripcion', 'Escribe una descripción de la foto.'],
            'sin decir si hay paciente' => [['con_paciente' => null], 'con_paciente', 'Indica si en la foto aparece un paciente.'],
            'con paciente y sin consentimiento' => [['con_paciente' => 'si'], 'consentimiento', 'Marca que tienes el consentimiento escrito del paciente.'],
            'descripción de 161 caracteres' => [['descripcion' => str_repeat('a', 161)], 'descripcion', 'La descripción puede tener como mucho 160 caracteres.'],
        ];
    }

    #[DataProvider('faltas')]
    public function test_cada_falta_tiene_su_mensaje_y_no_publica(array $cambios, string $campo, string $mensaje): void
    {
        $datos = array_filter($this->datos($this->imagenLisa('jpg'), $cambios), fn ($v) => $v !== null);

        $this->subir($datos)
            ->assertRedirect(route('admin.galeria.index'))
            ->assertSessionHasErrors([$campo => $mensaje]);
        $this->assertNadaGuardado(0);
    }

    public function test_sin_archivo_pide_la_foto(): void
    {
        $this->subir(['descripcion' => 'Sin foto', 'con_paciente' => 'no'])
            ->assertSessionHasErrors(['foto' => 'Elige la foto que quieres publicar.']);
        $this->assertNadaGuardado(0);
    }

    public function test_un_archivo_que_no_es_imagen_aunque_acabe_en_jpg_se_rechaza(): void
    {
        $this->subir($this->datos($this->textoComoJpg()))
            ->assertSessionHasErrors(['foto' => self::NO_ES_IMAGEN]);
        $this->assertNadaGuardado(0);
    }

    public function test_otro_formato_se_rechaza(): void
    {
        $this->subir($this->datos($this->imagenLisa('gif', 400, 300)))
            ->assertSessionHasErrors(['foto' => self::NO_ES_IMAGEN]);
        $this->assertNadaGuardado(0);
    }

    public function test_mas_de_5_mb_se_rechaza(): void
    {
        $pesada = $this->jpgPesado(5300000);
        $this->assertGreaterThan(5 * 1024 * 1024, $pesada->getSize());

        $this->subir($this->datos($pesada))->assertSessionHasErrors(['foto' => 'La foto pesa más de 5 MB.']);
        $this->assertNadaGuardado(0);
    }

    public function test_mas_de_6000_px_se_rechaza(): void
    {
        $this->subir($this->datos($this->imagenLisa('png', 6001, 10)))
            ->assertSessionHasErrors(['foto' => 'La foto es demasiado grande: como mucho 6000 píxeles por lado.']);
        $this->assertNadaGuardado(0);
    }

    public function test_un_png_de_mas_de_4000_px_se_rechaza(): void
    {
        // /review R1: un PNG grande agota la memoria al decodificarlo; se rechaza leyendo la cabecera.
        $this->subir($this->datos($this->imagenLisa('png', 4001, 10)))
            ->assertSessionHasErrors(['foto' => 'La foto PNG es demasiado grande: como mucho 4000 píxeles por lado.']);
        $this->assertNadaGuardado(0);
    }

    public function test_un_png_de_4000_px_se_publica(): void
    {
        $this->subir($this->datos($this->pngConAlfa(4000, 3000)))->assertSessionHasNoErrors();

        $this->assertSame(1, GaleriaFoto::count());
    }

    public function test_un_jpg_de_mas_de_4000_px_sigue_admitido(): void
    {
        $this->subir($this->datos($this->imagenLisa('jpg', 4500, 10)))->assertSessionHasNoErrors();

        $this->assertSame(1, GaleriaFoto::count(), 'El límite de 4000 px es solo para PNG.');
    }

    public function test_con_12_fotos_no_se_publica_la_siguiente(): void
    {
        foreach (range(1, 12) as $posicion) {
            $this->fotoExistente($posicion);
        }

        $this->subir($this->datos($this->imagenLisa('jpg')))
            ->assertSessionHasErrors(['foto' => 'Ya hay 12 fotos. Quita una antes de subir otra.']);
        $this->assertNadaGuardado(12);
    }

    public function test_un_fallo_al_procesar_no_publica_nada_y_registra_el_aviso(): void
    {
        $this->app->instance(ProcesadorFoto::class, new class extends ProcesadorFoto
        {
            public function procesar(UploadedFile $archivo): FotoProcesada
            {
                throw new FotoNoProcesable('GD no pudo leerla');
            }
        });
        Log::spy();

        $this->subir($this->datos($this->imagenLisa('png', 1200, 900, 'mi-sonrisa-de-pedro.png')))
            ->assertSessionHasErrors(['foto' => 'No se pudo leer la foto. Prueba con otro archivo.']);
        $this->assertNadaGuardado(0);

        Log::shouldHaveReceived('warning')->once()->with('galeria.procesado_fallido', Mockery::on(function (array $contexto) {
            $this->assertSame(['bytes', 'tipo_detectado'], collect($contexto)->keys()->sort()->values()->all(), 'Solo el tipo detectado y el tamaño.');
            $this->assertSame('image/png', $contexto['tipo_detectado']);
            $this->assertStringNotContainsString('pedro', json_encode($contexto), 'Sin el nombre del archivo.');
            $this->assertStringNotContainsString('Resina', json_encode($contexto), 'Ni la descripción.');

            return true;
        }));
    }
}
