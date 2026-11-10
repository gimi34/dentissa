<?php

namespace Tests\Feature\Galeria;

use App\Models\GaleriaFoto;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\Feature\Concerns\GaleriaDePrueba;
use Tests\TestCase;

/**
 * Spec 039 (T012; CA9, CA10, CA15; TM5, TM7, TM8, TM9; P8): la gestión de la galería por el
 * administrador. También el caso permitido del admin en `index`, `subir`, `bajar`, `quitar` y
 * `destroy` (plan P3) y el índice único de `posicion` en el centro y en los extremos (/analyze A19).
 */
class GestionGaleriaTest extends TestCase
{
    use DatabaseTransactions;
    use GaleriaDePrueba;

    private const MESES = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('galeria');
        $this->admin = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Galería',
            'email' => 'admin.gestion.'.Str::random(8).'@example.test',
            'rol' => 'admin',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** Una foto publicada con sus dos archivos en el disco, sin pasar por el código bajo prueba. */
    private function foto(int $posicion, string $descripcion = '', array $mas = []): GaleriaFoto
    {
        $foto = GaleriaFoto::create(array_merge([
            'descripcion' => $descripcion ?: "Trabajo número {$posicion}",
            'posicion' => $posicion,
            'con_paciente' => false,
            'ancho' => 1600,
            'alto' => 1200,
            'bytes' => 10,
            'bytes_pequena' => 10,
        ], $mas));
        Storage::disk('galeria')->put($foto->archivo(1600), 'RIFF-grande');
        Storage::disk('galeria')->put($foto->archivo(800), 'RIFF-pequena');

        return $foto;
    }

    /** Descripciones en el orden en que se ven en `/galeria`. */
    private function ordenPublico(): array
    {
        $html = $this->get(route('galeria.publica'))->assertOk()->getContent();
        preg_match_all('/<img[^>]*\salt="(Trabajo número \d+)"/', $html, $m);

        return $m[1];
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function fecha(Carbon $dia): string
    {
        return $dia->day.' '.self::MESES[$dia->month].' '.$dia->year;
    }

    public function test_con_paciente_y_consentimiento_se_publica(): void
    {
        $antes = now()->subSecond();

        $this->actingAs($this->admin)->from(route('admin.galeria.index'))->post(route('admin.galeria.store'), [
            'foto' => $this->imagenLisa('jpg'),
            'descripcion' => 'Ortodoncia terminada.',
            'con_paciente' => 'si',
            'consentimiento' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.galeria.index'));

        $foto = GaleriaFoto::sole();
        $this->assertTrue($foto->con_paciente);
        $this->assertSame($this->admin->id, $foto->consentimiento_usuario_id, 'Con la cuenta que lo declara.');
        $this->assertSame('Admin Galería', $foto->consentimiento_nombre, 'Con el nombre copiado.');
        $this->assertTrue($foto->consentimiento_en->greaterThanOrEqualTo($antes), 'Y la fecha.');
    }

    public function test_la_declaracion_muestra_quien_y_cuando(): void
    {
        $this->foto(1, 'Con paciente', [
            'con_paciente' => true,
            'consentimiento_usuario_id' => $this->admin->id,
            'consentimiento_nombre' => 'Admin Galería',
            'consentimiento_en' => Carbon::parse('2026-10-28 10:00:00'),
        ]);
        $this->foto(2, 'Sin paciente');

        $html = $this->actingAs($this->admin)->get(route('admin.galeria.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Consentimiento declarado por Admin Galería el 28 oct 2026', $html);
        $this->assertSame(1, substr_count($html, 'Consentimiento declarado por'), 'Solo la foto con paciente lleva la declaración.');
    }

    public function test_el_nombre_copiado_se_conserva_aunque_se_borre_la_cuenta(): void
    {
        $otro = Usuario::create([
            'nombre' => 'Otra', 'apellido' => 'Admin', 'email' => 'otra.'.Str::random(8).'@example.test',
            'rol' => 'admin', 'telefono' => '555-0001', 'activo' => 1, 'password_hash' => Hash::make('x'),
        ]);
        $foto = $this->foto(1, 'Con paciente', [
            'con_paciente' => true, 'consentimiento_usuario_id' => $otro->id,
            'consentimiento_nombre' => 'Otra Admin', 'consentimiento_en' => now(),
        ]);

        $otro->delete();

        $foto->refresh();
        $this->assertNull($foto->consentimiento_usuario_id, 'La clave queda en nulo (nullOnDelete).');
        $this->assertSame('Otra Admin', $foto->consentimiento_nombre, 'La declaración se conserva.');
    }

    public function test_subir_y_bajar_cambian_el_orden(): void
    {
        [$f1, $f2, $f3] = [$this->foto(1), $this->foto(2), $this->foto(3)];
        $this->actingAs($this->admin);

        $this->patch(route('admin.galeria.subir', $f2))->assertRedirect(route('admin.galeria.index'));
        $this->assertSame(['Trabajo número 2', 'Trabajo número 1', 'Trabajo número 3'], $this->ordenPublico(), 'Subir la del centro la cambia con la de arriba.');

        $this->patch(route('admin.galeria.bajar', $f2))->assertRedirect(route('admin.galeria.index'));
        $this->patch(route('admin.galeria.bajar', $f2))->assertRedirect(route('admin.galeria.index'));
        $this->assertSame(['Trabajo número 1', 'Trabajo número 3', 'Trabajo número 2'], $this->ordenPublico(), 'Bajar dos veces la lleva al final.');

        $this->assertSame([1, 2, 3], GaleriaFoto::ordenadas()->pluck('posicion')->all(), 'Las posiciones siguen sin huecos.');
        $this->assertSame([$f1->id, $f3->id, $f2->id], GaleriaFoto::ordenadas()->pluck('id')->all());
    }

    public function test_en_los_extremos_subir_y_bajar_no_hacen_nada(): void
    {
        [$f1, , $f3] = [$this->foto(1), $this->foto(2), $this->foto(3)];
        $this->actingAs($this->admin);

        $this->patch(route('admin.galeria.subir', $f1))->assertRedirect(route('admin.galeria.index'));
        $this->patch(route('admin.galeria.bajar', $f3))->assertRedirect(route('admin.galeria.index'));

        $this->assertSame(['Trabajo número 1', 'Trabajo número 2', 'Trabajo número 3'], $this->ordenPublico());

        $html = $this->get(route('admin.galeria.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Mover arriba: Trabajo número 1', $html, 'La primera no se puede subir.');
        $this->assertStringNotContainsString('Mover abajo: Trabajo número 3', $html, 'La última no se puede bajar.');
        $this->assertStringContainsString('Mover arriba: Trabajo número 2', $html);
        $this->assertStringContainsString('Mover abajo: Trabajo número 2', $html);
    }

    public function test_quitar_pide_confirmacion(): void
    {
        $foto = $this->foto(1, 'Corona de porcelana en un premolar.');

        $html = $this->actingAs($this->admin)->get(route('admin.galeria.quitar', $foto))->assertOk()->getContent();

        // /review R34: página de confirmación con role="dialog", pero sin aria-modal: el layout sigue
        // tabulable y el diálogo no promete un fondo inerte que no tiene.
        $xpath = $this->xpath($html);
        $dialogo = $xpath->query('//*[@role="dialog"]')->item(0);
        $this->assertNotNull($dialogo, 'El diálogo está abierto.');
        $this->assertFalse($dialogo->hasAttribute('aria-modal'), 'Sin aria-modal.');
        $this->assertSame('¿Quitar esta foto?', trim($xpath->query('//*[@id="'.$dialogo->getAttribute('aria-labelledby').'"]')->item(0)?->textContent ?? ''), 'Nombrado por su título.');
        $this->assertStringContainsString('deja de verse', $xpath->query('//*[@id="'.$dialogo->getAttribute('aria-describedby').'"]')->item(0)?->textContent ?? '', 'Descrito por su texto.');
        $cancelar = $xpath->query('.//a[normalize-space()="Cancelar"]', $dialogo)->item(0);
        $this->assertTrue($cancelar?->hasAttribute('autofocus') ?? false, 'El foco inicial, en «Cancelar».');
        $this->assertStringContainsString('¿Quitar esta foto?', $html);
        $this->assertStringContainsString('«Corona de porcelana en un premolar.»', $html, 'Dice cuál.');
        $this->assertMatchesRegularExpression('/<form[^>]*action="'.preg_quote(route('admin.galeria.destroy', $foto), '/').'"/', $html, 'Con el formulario de borrado.');
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertMatchesRegularExpression('/>\s*Cancelar\s*</', $html);
        $this->assertSame(1, GaleriaFoto::count(), 'Pedir confirmación no borra nada.');
        $this->assertTrue(Storage::disk('galeria')->exists($foto->archivo(1600)));
    }

    public function test_la_foto_quitada_deja_de_abrirse(): void
    {
        $f1 = $this->foto(1);
        $f2 = $this->foto(2);
        $f3 = $this->foto(3);
        $grande = route('galeria.foto', [$f2, 1600]);
        $pequena = route('galeria.foto', [$f2, 800]);
        $this->get($pequena)->assertOk();

        $this->actingAs($this->admin)->delete(route('admin.galeria.destroy', $f2))
            ->assertRedirect(route('admin.galeria.index'))
            ->assertSessionHas('success', 'Foto quitada.');

        $this->assertNull(GaleriaFoto::find($f2->id));
        $this->assertSame([[$f1->id, 1], [$f3->id, 2]], GaleriaFoto::ordenadas()->get()->map(fn ($f) => [$f->id, $f->posicion])->all(), 'Las posiciones se compactan.');
        $this->assertFalse(Storage::disk('galeria')->exists($f2->archivo(1600)), 'Se borra el grande.');
        $this->assertFalse(Storage::disk('galeria')->exists($f2->archivo(800)), 'Y el pequeño.');
        $this->get($grande)->assertNotFound();
        $this->get($pequena)->assertNotFound();
        $this->get($pequena, ['If-None-Match' => '"'.$f2->id.'-800"'])->assertNotFound();
    }

    public function test_quitar_la_primera_y_la_ultima_compacta_las_posiciones(): void
    {
        [$f1, $f2, $f3, $f4] = [$this->foto(1), $this->foto(2), $this->foto(3), $this->foto(4)];
        $this->actingAs($this->admin);

        $this->delete(route('admin.galeria.destroy', $f1))->assertRedirect(route('admin.galeria.index'));
        $this->delete(route('admin.galeria.destroy', $f4))->assertRedirect(route('admin.galeria.index'));

        $this->assertSame([[$f2->id, 1], [$f3->id, 2]], GaleriaFoto::ordenadas()->get()->map(fn ($f) => [$f->id, $f->posicion])->all());
    }

    public function test_si_no_se_puede_borrar_el_archivo_queda_el_aviso(): void
    {
        $foto = $this->foto(1, 'Foto con la ruta secreta');
        $real = Storage::disk('galeria');
        $disco = Mockery::mock($real)->makePartial();
        $disco->shouldReceive('delete')->andReturnFalse();
        Storage::set('galeria', $disco);
        Log::spy();

        $this->actingAs($this->admin)->delete(route('admin.galeria.destroy', $foto))->assertRedirect(route('admin.galeria.index'));

        $this->assertNull(GaleriaFoto::find($foto->id), 'La foto queda quitada.');
        $anchos = [];
        Log::shouldHaveReceived('warning')->twice()->with('galeria.archivo_no_borrado', Mockery::on(function (array $contexto) use ($foto, &$anchos) {
            $this->assertSame(['ancho', 'foto_id'], collect($contexto)->keys()->sort()->values()->all());
            $this->assertSame($foto->id, $contexto['foto_id']);
            $this->assertStringNotContainsString('.webp', json_encode($contexto), 'Sin la ruta del archivo.');
            $this->assertStringNotContainsString('secreta', json_encode($contexto), 'Ni la descripción.');
            $anchos[] = $contexto['ancho'];

            return true;
        }));
        sort($anchos);
        $this->assertSame([800, 1600], array_values(array_unique($anchos)), 'Un aviso por cada archivo.');
    }

    public function test_la_descripcion_se_muestra_como_texto_en_la_gestion(): void
    {
        // /review R7: tres fotos con la carga en la del medio, para que salgan sus «Arriba» y «Abajo», y
        // con comillas, para probar también la salida dentro de un atributo.
        $carga = '"><script>alert(1)</script>';
        $escapada = '&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
        $this->foto(1);
        $foto = $this->foto(2, $carga);
        $this->foto(3);
        $this->actingAs($this->admin);

        foreach ([route('admin.galeria.index') => 4, route('admin.galeria.quitar', $foto) => 5] as $url => $veces) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('<script>alert(1)</script>', $html, "Sin la etiqueta en {$url}.");
            $this->assertStringNotContainsString('"><script>', $html, "Sin salir del atributo en {$url}.");
            $this->assertSame($veces, substr_count($html, $escapada), "Escapada en la tarjeta, en los tres aria-label y, en el diálogo, en su texto ({$url}).");
            $xpath = $this->xpath($html);
            foreach (['Mover arriba: ', 'Mover abajo: ', 'Quitar: '] as $prefijo) {
                $this->assertSame(1, $xpath->query('//*[@aria-label='.$this->literalXpath($prefijo.$carga).']')->length, "El aria-label «{$prefijo}…» lleva la descripción como texto.");
            }
        }
    }

    /** Literal de XPath 1.0 para un texto con comillas simples y dobles. */
    private function literalXpath(string $texto): string
    {
        return "concat('".str_replace("'", "', \"'\", '", $texto)."', '')";
    }

    public function test_con_el_dialogo_abierto_el_fondo_queda_inerte(): void
    {
        // /review R3 (WCAG 2.4.3): sin JavaScript, el contenido de la vista detrás del diálogo se vuelve
        // inerte desde el servidor (el layout no; por eso el diálogo no declara aria-modal, R34).
        $foto = $this->foto(1);
        $this->foto(2);
        $this->actingAs($this->admin);

        $xpath = $this->xpath($this->get(route('admin.galeria.quitar', $foto))->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//*[contains(@class, "galeria-gestion__intro") and @inert]')->length, 'La introducción, inerte.');
        $this->assertSame(1, $xpath->query('//*[contains(@class, "galeria-gestion__grid") and @inert]')->length, 'La lista y el formulario, inertes.');
        $this->assertSame(1, $xpath->query('//*[@role="dialog"]')->length, 'Hay diálogo.');
        $this->assertSame(0, $xpath->query('//*[@role="dialog"]/ancestor-or-self::*[@inert]')->length, 'El diálogo no está inerte.');

        $sinDialogo = $this->xpath($this->get(route('admin.galeria.index'))->assertOk()->getContent());
        $this->assertSame(0, $sinDialogo->query('//*[@inert]')->length, 'Sin diálogo, nada inerte.');
    }

    public function test_el_aviso_de_error_recibe_el_foco_y_enlaza_cada_campo(): void
    {
        // /review R5 (WCAG 3.3.1): tras la recarga, el aviso recibe el foco y lleva a cada campo con error.
        $this->actingAs($this->admin);
        $html = $this->from(route('admin.galeria.index'))->followingRedirects()
            ->post(route('admin.galeria.store'), [])->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $aviso = $xpath->query('//*[@role="alert" and contains(@class, "aviso-error")]')->item(0);
        $this->assertNotNull($aviso, 'Hay aviso.');
        $this->assertSame('-1', $aviso->getAttribute('tabindex'));
        $this->assertTrue($aviso->hasAttribute('autofocus'), 'Recibe el foco al cargar.');
        $enlaces = array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($xpath->query('.//a', $aviso)));
        $this->assertSame(['#foto', '#descripcion', '#con_paciente-no'], $enlaces, 'Un enlace por campo con error, en orden.');
        foreach ($enlaces as $href) {
            $this->assertSame(1, $xpath->query('//*[@id="'.substr($href, 1).'"]')->length, "{$href} existe.");
        }

        $conPaciente = $this->xpath($this->from(route('admin.galeria.index'))->followingRedirects()->post(route('admin.galeria.store'), [
            'foto' => $this->imagenLisa('jpg'), 'descripcion' => 'Algo', 'con_paciente' => 'si',
        ])->assertOk()->getContent());
        $this->assertSame(['#consentimiento'], array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($conPaciente->query('//*[@role="alert"]//a'))));
        $this->assertSame(1, $conPaciente->query('//input[@id="consentimiento"]')->length);
    }

    public function test_la_casilla_del_consentimiento_enlaza_su_ayuda(): void
    {
        // /review R10: como la foto y la descripción, la casilla lleva su ayuda en aria-describedby.
        $xpath = $this->xpath($this->actingAs($this->admin)->get(route('admin.galeria.index'))->assertOk()->getContent());

        $casilla = $xpath->query('//input[@name="consentimiento"]')->item(0);
        $this->assertContains('consentimiento-ayuda', explode(' ', $casilla->getAttribute('aria-describedby')));
        $this->assertStringContainsString('Guarda el documento firmado', $xpath->query('//*[@id="consentimiento-ayuda"]')->item(0)?->textContent ?? '');
    }

    public function test_los_formularios_llevan_token_csrf(): void
    {
        $foto = $this->foto(1);
        $this->foto(2);
        $this->actingAs($this->admin);

        foreach ([route('admin.galeria.index'), route('admin.galeria.quitar', $foto)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<form\b[^>]*method="post"[^>]*>(.*?)<\/form>/is', $html, $formularios);
            $this->assertNotEmpty($formularios[0], "Hay formularios en {$url}.");
            foreach ($formularios[1] as $cuerpo) {
                $this->assertStringContainsString('name="_token"', $cuerpo, "Cada formulario de {$url} lleva el token.");
            }
        }
    }
}
