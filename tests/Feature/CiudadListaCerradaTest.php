<?php

namespace Tests\Feature;

use App\Pacientes\Ciudades;
use App\Rules\CiudadDeLaLista;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 051: la ciudad se elige de una lista cerrada en los cinco formularios (cuatro vistas), el
 * servidor rechaza lo demás, la ciudad antigua se conserva con aviso y el directorio filtra limpio.
 */
class CiudadListaCerradaTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;
    use ReglasCss;

    private const MENSAJE = 'Elige una ciudad de la lista';

    private const CORREO_NUEVO = 'ciudad-051@example.test';

    private const GOOGLE = ['google_id' => 'google-051', 'nombre' => 'Persona', 'apellido' => 'De Pruebas',
        'email' => 'google-051@example.test', 'foto_url' => null];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->crearEscenario();
    }

    /** @return array<string, array{string}> */
    public static function formularios(): array
    {
        return [
            'registro con correo' => ['registro con correo'],
            'registro con Google' => ['registro con Google'],
            'perfil' => ['perfil'],
            'alta' => ['alta'],
            'edición' => ['edición'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function formulariosConCiudadGuardada(): array
    {
        return ['perfil' => ['perfil'], 'edición' => ['edición']];
    }

    // ── T011 · Marcado ─────────────────────────────────────────────────

    public function test_el_registro_ofrece_la_lista_de_ciudades(): void
    {
        $this->assertListaCompleta($this->xpath($this->pagina('registro con correo')));
        $this->assertListaCompleta($this->xpath($this->pagina('registro con Google')));
    }

    public function test_el_perfil_ofrece_la_lista_con_la_ciudad_guardada(): void
    {
        $this->assertSame([''], $this->elegidas($this->xpath($this->pagina('perfil'))), 'Sin ciudad, «Sin indicar».');

        $this->guardarCiudad('Morelia');
        $xpath = $this->xpath($this->pagina('perfil'));

        $this->assertListaCompleta($xpath);
        $this->assertSame(['Morelia'], $this->elegidas($xpath));
    }

    public function test_el_alta_y_la_edicion_ofrecen_la_lista(): void
    {
        $alta = $this->xpath($this->pagina('alta'));
        $this->assertListaCompleta($alta);
        $this->assertSame([''], $this->elegidas($alta));

        $this->guardarCiudad('Zihuatanejo de Azueta');
        $edicion = $this->xpath($this->pagina('edición'));

        $this->assertListaCompleta($edicion);
        $this->assertSame(['Zihuatanejo de Azueta'], $this->elegidas($edicion));
    }

    #[DataProvider('formularios')]
    public function test_el_campo_ciudad_es_un_desplegable_con_etiqueta_asociada(string $formulario): void
    {
        $xpath = $this->xpath($this->pagina($formulario));
        $campo = $this->campo($xpath);

        $this->assertSame(0, $xpath->query("//input[@name='ciudad']")->length, 'Ya no se escribe a mano.');
        $this->assertNotSame('', $campo->getAttribute('id'));
        $etiqueta = $xpath->query("//label[@for='{$campo->getAttribute('id')}']");
        $this->assertSame(1, $etiqueta->length, 'La etiqueta apunta al campo.');
        $this->assertSame('Ciudad', trim($etiqueta->item(0)->textContent));

        if (in_array($formulario, ['registro con correo', 'registro con Google', 'perfil'], true)) {
            $this->assertContains('campo-ciudad', $this->clases($campo), 'Foco y borde de 3:1 (D12).');
        }
    }

    // ── T012 · Guardar y rechazar ──────────────────────────────────────

    #[DataProvider('formularios')]
    public function test_cada_formulario_guarda_una_ciudad_de_la_lista(string $formulario): void
    {
        $this->enviar($formulario, ['ciudad' => 'Lázaro Cárdenas'])->assertSessionHasNoErrors();

        $this->assertSame('Lázaro Cárdenas', $this->ciudadGuardada($formulario));
    }

    #[DataProvider('formularios')]
    public function test_sin_indicar_deja_al_paciente_sin_ciudad(string $formulario): void
    {
        $this->guardarCiudad('Morelia');

        $this->enviar($formulario, ['ciudad' => ''])->assertSessionHasNoErrors();

        $this->assertNull($this->ciudadGuardada($formulario));
    }

    /** @return array<string, array{string, string}> */
    public static function formulariosYValoresRechazados(): array
    {
        $casos = [];
        foreach (array_keys(self::formularios()) as $formulario) {
            foreach (['otra ciudad' => 'Ciudad de México', 'texto largo' => str_repeat('x', 101), 'marcado' => '<b>x</b>'] as $nombre => $valor) {
                $casos["{$formulario}: {$nombre}"] = [$formulario, $valor];
            }
        }

        return $casos;
    }

    #[DataProvider('formulariosYValoresRechazados')]
    public function test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario(string $formulario, string $valor): void
    {
        $this->guardarCiudad('Morelia');

        $this->enviar($formulario, ['ciudad' => $valor, 'telefono' => '5550051'])
            ->assertSessionHasErrors(['ciudad' => self::MENSAJE]);

        $this->assertNadaGuardado($formulario);
    }

    #[DataProvider('formularios')]
    public function test_el_rechazo_muestra_el_mensaje_y_conserva_lo_escrito(string $formulario): void
    {
        $html = $this->enviarSiguiendo($formulario, ['ciudad' => 'Ciudad de México', 'telefono' => '5550051']);
        $xpath = $this->xpath($html);

        $this->assertSame(self::MENSAJE, trim($xpath->query("//*[@id='error-ciudad']")->item(0)?->textContent ?? ''));
        $this->assertSame('5550051', $xpath->query("//input[@name='telefono']")->item(0)?->getAttribute('value'));
    }

    #[DataProvider('formularios')]
    public function test_un_array_en_ciudad_se_rechaza(string $formulario): void
    {
        $this->guardarCiudad('Morelia');

        $this->enviar($formulario, ['ciudad' => ['Morelia'], 'telefono' => '5550051'])
            ->assertSessionHasErrors(['ciudad' => self::MENSAJE]);
        $this->assertNadaGuardado($formulario);

        $this->assertStringContainsString(self::MENSAJE, $this->enviarSiguiendo($formulario, ['ciudad' => ['Morelia']]));
    }

    #[DataProvider('formularios')]
    public function test_el_error_y_el_aviso_van_en_aria_describedby(string $formulario): void
    {
        $xpath = $this->xpath($this->enviarSiguiendo($formulario, ['ciudad' => 'Ciudad de México']));
        $campo = $this->campo($xpath);

        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame('error-ciudad', $campo->getAttribute('aria-describedby'));
    }

    public function test_el_error_de_ciudad_abre_el_paso_2_del_registro(): void
    {
        $html = $this->enviarSiguiendo('registro con correo', ['ciudad' => 'Ciudad de México']);

        $this->assertStringContainsString('goTo(2);', $this->scriptDelRegistro($html));
        $this->assertStringNotContainsString('currentStep = 2', $html, 'El paso inicial siempre es el 1: si no, goTo(2) lo ocultaría.');
    }

    /**
     * /review R1: tras un error del servidor la contraseña vuelve vacía y su paso puede estar oculto; el
     * navegador no marca un campo oculto y no enviaría nada. El botón vuelve antes al paso 1.
     * La comprobación en el navegador va en la nota de T033.
     */
    public function test_crear_mi_cuenta_vuelve_al_paso_1_si_no_es_valido(): void
    {
        $script = $this->scriptDelRegistro($this->enviarSiguiendo('registro con correo', ['ciudad' => 'Ciudad de México']));

        $this->assertMatchesRegularExpression(
            "/querySelector\\('#register-form \\.btn-submit'\\)\\.addEventListener\\('click'.*?checkValidity\\(\\).*?preventDefault\\(\\);\\s*goTo\\(1\\);\\s*validateStep\\(1\\);/s",
            $script,
        );
    }

    public function test_un_error_solo_de_los_terminos_abre_el_paso_2_del_registro(): void
    {
        $html = $this->enviarSiguiendo('registro con correo', ['terminos' => null]);

        $this->assertStringContainsString('goTo(2);', $this->scriptDelRegistro($html));
    }

    public function test_un_error_del_paso_1_abre_el_paso_1_aunque_la_ciudad_tambien_falle(): void
    {
        $html = $this->enviarSiguiendo('registro con correo', ['ciudad' => 'Ciudad de México', 'email' => 'no-es-correo']);

        $this->assertStringNotContainsString('goTo(2);', $this->scriptDelRegistro($html));
    }

    public function test_sin_errores_el_registro_no_salta_de_paso(): void
    {
        $this->assertStringNotContainsString('goTo(2);', $this->scriptDelRegistro($this->pagina('registro con correo')));
    }

    public function test_cada_opcion_del_formulario_se_acepta_al_guardar(): void
    {
        $valores = [];
        foreach ($this->xpath($this->pagina('alta'))->query("//select[@name='ciudad']//option") as $opcion) {
            $valores[] = $opcion->getAttribute('value');
        }

        $this->assertSame(array_merge([''], Ciudades::MICHOACAN, Ciudades::GUERRERO, [Ciudades::OTRO_LUGAR]), $valores);
        foreach (array_filter($valores) as $valor) {
            $this->assertTrue(
                Validator::make(['ciudad' => $valor], ['ciudad' => ['nullable', new CiudadDeLaLista]])->passes(),
                "«{$valor}» se acepta al guardar."
            );
        }
    }

    // ── T013 · Ciudad antigua ──────────────────────────────────────────

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_la_ciudad_fuera_de_la_lista_muestra_el_aviso(string $formulario): void
    {
        $this->guardarCiudad('LC');
        $xpath = $this->xpath($this->pagina($formulario));
        $campo = $this->campo($xpath);

        $primera = $xpath->query("//select[@name='ciudad']//option")->item(0);
        $this->assertSame('LC', $primera->getAttribute('value'));
        $this->assertSame('LC (no está en la lista)', trim($primera->textContent));
        $this->assertSame(['LC'], $this->elegidas($xpath));

        $aviso = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' field-hint-aviso ')]");
        $this->assertSame(1, $aviso->length);
        $this->assertSame('La ciudad guardada «LC» no está en la lista: elige una o deja «Sin indicar»', trim($aviso->item(0)->textContent));
        $this->assertContains($aviso->item(0)->getAttribute('id'), explode(' ', $campo->getAttribute('aria-describedby')));
        $this->assertNotContains('field-hint-error', $this->clases($aviso->item(0)), 'No es un error.');
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_una_ciudad_de_la_lista_no_lleva_aviso(string $formulario): void
    {
        $this->guardarCiudad('Morelia');
        $xpath = $this->xpath($this->pagina($formulario));

        $this->assertSame(0, $xpath->query("//*[contains(@class, 'field-hint-aviso')]")->length);
        $this->assertSame('', $xpath->query("//select[@name='ciudad']//option")->item(0)->getAttribute('value'));
        $this->assertFalse($this->campo($xpath)->hasAttribute('aria-describedby'));
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_guardar_sin_cambiar_conserva_la_ciudad_antigua(string $formulario): void
    {
        $this->guardarCiudad('LC');

        $this->enviar($formulario, ['ciudad' => 'LC', 'telefono' => '5550051'])->assertSessionHasNoErrors();

        $this->assertSame('LC', $this->ciudadGuardada($formulario));
        $this->assertSame('5550051', DB::table('usuarios')->where('id', $this->paciente1->id)->value('telefono'));
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_elegir_otra_sustituye_la_ciudad_antigua(string $formulario): void
    {
        $this->guardarCiudad('LC');

        $this->enviar($formulario, ['ciudad' => 'Uruapan'])->assertSessionHasNoErrors();

        $this->assertSame('Uruapan', $this->ciudadGuardada($formulario));
        $xpath = $this->xpath($this->pagina($formulario));
        $this->assertSame(0, $xpath->query("//option[@value='LC']")->length, 'La antigua deja de ofrecerse.');
        $this->assertSame(0, $xpath->query("//*[contains(@class, 'field-hint-aviso')]")->length);
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_el_error_se_suma_al_aviso_en_aria_describedby(string $formulario): void
    {
        $this->guardarCiudad('LC');

        $campo = $this->campo($this->xpath($this->enviarSiguiendo($formulario, ['ciudad' => 'zihuayork'])));

        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame(['error-ciudad', 'aviso-ciudad'], explode(' ', $campo->getAttribute('aria-describedby')));
    }

    /** @return array<string, array{string}> */
    public static function formulariosSinEsaCiudad(): array
    {
        return self::formularios();
    }

    #[DataProvider('formulariosSinEsaCiudad')]
    public function test_la_ciudad_antigua_de_otro_paciente_se_rechaza(string $formulario): void
    {
        $this->guardarCiudad('LC');
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => 'zihuayork']);

        $this->enviar($formulario, ['ciudad' => 'zihuayork'])->assertSessionHasErrors(['ciudad' => self::MENSAJE]);

        if (in_array($formulario, ['perfil', 'edición'], true)) {
            $this->assertSame('LC', $this->ciudadGuardada($formulario));
        }
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_sin_indicar_se_respeta_tras_un_error_en_otro_campo(string $formulario): void
    {
        $this->guardarCiudad('LC');

        $xpath = $this->xpath($this->enviarSiguiendo($formulario, ['ciudad' => '', 'email' => 'no-es-correo']));

        $this->assertSame([''], $this->elegidas($xpath), 'Se queda lo que eligió, no la ciudad antigua.');
    }

    /** @return array<string, array{string}> */
    public static function ciudadesGuardadas(): array
    {
        return ['de la lista' => ['Morelia'], 'antigua' => ['LC']];
    }

    #[DataProvider('ciudadesGuardadas')]
    public function test_un_error_de_contrasena_no_cambia_la_ciudad_del_perfil(string $ciudad): void
    {
        $this->guardarCiudad($ciudad);

        $html = $this->actingAs($this->paciente1)->followingRedirects()->from('/cliente/perfil')
            ->put('/cliente/perfil/password', ['password' => 'x', 'password_confirmation' => 'y'])
            ->assertOk()->getContent();

        $this->assertSame([$ciudad], $this->elegidas($this->xpath($html)));
    }

    #[DataProvider('formulariosConCiudadGuardada')]
    public function test_la_ciudad_antigua_se_muestra_escapada(string $formulario): void
    {
        $this->guardarCiudad('<script>alert(1)</script>');

        $html = $this->pagina($formulario);

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; (no está en la lista)', $html);
        $this->assertStringContainsString('La ciudad guardada «&lt;script&gt;alert(1)&lt;/script&gt;»', $html);
    }

    // ── T014 · Directorio y caracterizaciones ──────────────────────────

    public function test_el_filtro_solo_ofrece_ciudades_de_la_lista(): void
    {
        $this->guardarCiudad('Morelia');
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => 'zihuayork']);

        $opciones = $this->opcionesDelFiltro($this->directorio());

        $this->assertContains('Morelia', $opciones);
        $this->assertNotContains('zihuayork', $opciones);
        $this->assertSame([], array_values(array_diff($opciones, Ciudades::nombres())), 'Solo nombres de la lista.');
    }

    public function test_el_filtro_va_en_el_orden_de_la_lista_con_otro_lugar_al_final(): void
    {
        $this->guardarCiudad('Zamora');
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => Ciudades::OTRO_LUGAR]);

        $opciones = $this->opcionesDelFiltro($this->directorio());

        $this->assertSame(array_values(array_intersect(Ciudades::nombres(), $opciones)), $opciones);
        $this->assertSame(Ciudades::OTRO_LUGAR, end($opciones));
    }

    public function test_morelia_sigue_en_el_filtro_aunque_otro_paciente_tenga_morelia_en_minusculas(): void
    {
        $this->guardarCiudad('morelia');
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => 'Morelia']);

        $this->assertContains('Morelia', $this->opcionesDelFiltro($this->directorio()));
    }

    public function test_ciudades_distintas_no_cuenta_otro_lugar(): void
    {
        $this->guardarCiudad('Morelia');
        DB::table('pacientes')->where('id', $this->p2)->update(['ciudad' => Ciudades::OTRO_LUGAR]);

        $xpath = $this->directorio();
        $opciones = $this->opcionesDelFiltro($xpath);
        $cifra = $xpath->query("//div[contains(@class, 'stat-label')][normalize-space()='Ciudades distintas']/preceding-sibling::div[contains(@class, 'stat-value')]");

        $this->assertContains(Ciudades::OTRO_LUGAR, $opciones);
        $this->assertSame((string) (count($opciones) - 1), trim($cifra->item(0)->textContent));
    }

    public function test_el_filtro_ignora_una_ciudad_fuera_de_la_lista(): void
    {
        $this->guardarCiudad('Morelia');

        $ignorado = $this->actingAs($this->admin)->get('/admin/pacientes?ciudad=zihuayork&buscar=Pablo')->assertOk()->getContent();
        $this->assertStringContainsString('Pablo', $this->filasDelDirectorio($ignorado), 'Un valor fuera de la lista no filtra.');

        $aplicado = $this->actingAs($this->admin)->get('/admin/pacientes?ciudad=Uruapan&buscar=Pablo')->assertOk()->getContent();
        $this->assertStringNotContainsString('Pablo', $this->filasDelDirectorio($aplicado), 'Control: uno de la lista sí filtra.');
    }

    /** Caracterización de CA11: la ciudad se muestra tal como está guardada. */
    #[DataProvider('ciudadesParaMostrar')]
    public function test_la_ficha_y_el_directorio_muestran_la_ciudad_guardada(string $ciudad): void
    {
        $this->guardarCiudad($ciudad);

        $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}")->assertOk()->assertSeeText($ciudad);

        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes?buscar=Pablo')->assertOk()->getContent());
        $this->assertSame($ciudad, trim($xpath->query("//tbody//td[@data-label='Ciudad']")->item(0)->textContent));
    }

    /** @return array<string, array{string}> */
    public static function ciudadesParaMostrar(): array
    {
        return ['de la lista' => ['Morelia'], 'otro lugar' => ['Otro lugar'], 'antigua' => ['LC']];
    }

    /** Caracterización (P3): el asistente no tiene edición y las rutas de otros roles lo rechazan. */
    public function test_el_asistente_sigue_sin_poder_editar_un_paciente(): void
    {
        $this->guardarCiudad('Morelia');

        foreach (['admin', 'dentista'] as $prefijo) {
            $this->actingAs($this->asistente)
                ->put("/{$prefijo}/pacientes/{$this->p1}", ['nombre' => 'X', 'apellido' => 'Y', 'email' => 'x051@example.test', 'ciudad' => 'Uruapan'])
                ->assertForbidden();
        }

        $this->assertSame('Morelia', $this->ciudadGuardada('edición'));
    }

    // ── T015 · Estilos ─────────────────────────────────────────────────

    public function test_el_bloque_051_solo_usa_tokens(): void
    {
        $this->assertNotSame([], $this->literales('color: #fff; border-width: 2px'), 'Control positivo: detecta literales.');

        foreach (['css/app.css', 'css/auth.css'] as $hoja) {
            $cuerpos = implode(' ', array_column($this->analizarCss($this->bloque051($hoja)), 1));

            $this->assertNotSame('', trim($cuerpos), "{$hoja}: el bloque tiene reglas.");
            $this->assertSame([], $this->literales($cuerpos), "{$hoja}: el bloque 051 solo usa tokens.");
        }

        // Con las dos clases: `pacientes/create` redefine `.field-hint` en su propio <style>, después de app.css.
        $this->assertStringContainsString('color: var(--text)', $this->declaraciones('.field-hint.field-hint-aviso'));
    }

    public function test_el_foco_y_el_borde_de_la_ciudad_llegan_a_3_1(): void
    {
        foreach (['css/app.css' => 'select.campo-ciudad', 'css/auth.css' => '.page-register select.campo-ciudad'] as $hoja => $selector) {
            foreach ([$selector, "{$selector}:focus"] as $estado) {
                $this->assertStringContainsString('border-color: var(--border-control)', $this->declaraciones($estado, $hoja), "{$hoja}: borde de {$estado}.");
            }

            $foco = $this->declaraciones("{$selector}:focus-visible", $hoja);
            $this->assertStringContainsString('outline: var(--focus-ring-width) solid var(--focus-ring)', $foco, "{$hoja}: anillo de foco.");
            $this->assertStringContainsString('outline-offset: var(--focus-ring-offset)', $foco, "{$hoja}: separación del anillo.");
        }

        $toque = array_filter(
            $this->analizarCss($this->bloque051('css/auth.css')),
            fn (array $regla) => $regla[0] === '.page-register select.campo-ciudad' && in_array('@media (max-width: 899px)', $regla[2], true),
        );
        $this->assertCount(1, $toque, 'El registro no carga movil.css: el toque de 44 px va en su bloque.');
        $this->assertStringContainsString('min-height: var(--touch-min)', array_values($toque)[0][1]);

        $this->assertSame($this->tokenDeLaHoja('css/app.css', '--border-control'), $this->tokenDeLaHoja('css/auth.css', '--border-control'));
        $this->assertSame($this->tokenDeLaHoja('css/app.css', '--p400'), $this->tokenDeLaHoja('css/auth.css', '--p400'));

        $tokens = $this->tokensDeColor();
        foreach (['--white', '--p50'] as $fondo) {
            foreach (['--border-control', '--focus-ring'] as $trazo) {
                $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens[$trazo], $tokens[$fondo]), "{$trazo} sobre {$fondo}.");
            }
        }
    }

    // ── Ayudas ─────────────────────────────────────────────────────────

    private function guardarCiudad(string $ciudad): void
    {
        DB::table('pacientes')->where('id', $this->p1)->update(['ciudad' => $ciudad]);
    }

    private function pagina(string $formulario): string
    {
        return (match ($formulario) {
            'registro con correo' => $this->get('/registro'),
            'registro con Google' => $this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto'),
            'perfil' => $this->actingAs($this->paciente1)->get('/cliente/perfil'),
            'alta' => $this->actingAs($this->admin)->get('/admin/pacientes/crear'),
            'edición' => $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}/edit"),
        })->assertOk()->getContent();
    }

    /** @param  array<string, mixed>  $datos */
    private function enviar(string $formulario, array $datos, bool $siguiendo = false): TestResponse
    {
        $prueba = $siguiendo ? $this->followingRedirects() : $this;

        return match ($formulario) {
            'registro con correo' => $prueba->from('/registro')->post('/registro', $this->conDatos([
                'nombre' => 'Persona', 'apellido' => 'De Pruebas', 'email' => self::CORREO_NUEVO,
                'password' => 'secreto-de-pruebas', 'password_confirmation' => 'secreto-de-pruebas', 'terminos' => '1',
            ], $datos)),
            'registro con Google' => $prueba->withSession(['google_registro' => self::GOOGLE])->from('/registro/contacto')
                ->post('/registro/contacto', $this->conDatos(['terminos' => '1'], $datos)),
            'perfil' => $prueba->actingAs($this->paciente1)->from('/cliente/perfil')->put('/cliente/perfil', $this->conDatos([
                'nombre' => $this->paciente1->nombre, 'apellido' => $this->paciente1->apellido, 'email' => $this->paciente1->email,
            ], $datos)),
            'alta' => $prueba->actingAs($this->admin)->from('/admin/pacientes/crear')->post('/admin/pacientes', $this->conDatos([
                'nombre' => 'Persona', 'apellido' => 'De Pruebas', 'email' => self::CORREO_NUEVO,
            ], $datos)),
            'edición' => $prueba->actingAs($this->admin)->from("/admin/pacientes/{$this->p1}/edit")->put("/admin/pacientes/{$this->p1}", $this->conDatos([
                'nombre' => $this->paciente1->nombre, 'apellido' => $this->paciente1->apellido, 'email' => $this->paciente1->email,
            ], $datos)),
        };
    }

    /** @param  array<string, mixed>  $datos */
    private function enviarSiguiendo(string $formulario, array $datos): string
    {
        return $this->enviar($formulario, $datos, true)->assertOk()->getContent();
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $datos  un valor `null` quita el campo del envío
     * @return array<string, mixed>
     */
    private function conDatos(array $base, array $datos): array
    {
        return array_filter(array_merge($base, $datos), fn ($valor) => $valor !== null);
    }

    private function ciudadGuardada(string $formulario): ?string
    {
        return match ($formulario) {
            'registro con correo', 'alta' => $this->ciudadPorCorreo(self::CORREO_NUEVO),
            'registro con Google' => $this->ciudadPorCorreo(self::GOOGLE['email']),
            default => DB::table('pacientes')->where('id', $this->p1)->value('ciudad'),
        };
    }

    private function ciudadPorCorreo(string $correo): ?string
    {
        return DB::table('pacientes')->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.email', $correo)->value('pacientes.ciudad');
    }

    /** Nada del envío rechazado llegó a la base. */
    private function assertNadaGuardado(string $formulario): void
    {
        if (in_array($formulario, ['perfil', 'edición'], true)) {
            $this->assertSame('Morelia', $this->ciudadGuardada($formulario));
            $this->assertNotSame('5550051', DB::table('usuarios')->where('id', $this->paciente1->id)->value('telefono'));

            return;
        }

        $correo = $formulario === 'registro con Google' ? self::GOOGLE['email'] : self::CORREO_NUEVO;
        $this->assertDatabaseMissing('usuarios', ['email' => $correo]);
    }

    private function assertListaCompleta(DOMXPath $xpath): void
    {
        $this->assertSame(1, $xpath->query("//select[@name='ciudad']")->length);

        $hijas = [];
        foreach ($this->campo($xpath)->childNodes as $nodo) {
            if ($nodo instanceof DOMElement) {
                $hijas[] = $nodo;
            }
        }

        $this->assertCount(4, $hijas, '«Sin indicar», los dos grupos y «Otro lugar».');
        [$sinIndicar, $michoacan, $guerrero, $otroLugar] = $hijas;

        $this->assertSame(['option', ''], [$sinIndicar->tagName, $sinIndicar->getAttribute('value')]);
        $this->assertSame('Sin indicar', trim($sinIndicar->textContent));
        $this->assertSame(['optgroup', 'Michoacán'], [$michoacan->tagName, $michoacan->getAttribute('label')]);
        $this->assertSame(['optgroup', 'Guerrero'], [$guerrero->tagName, $guerrero->getAttribute('label')]);
        $this->assertSame(Ciudades::MICHOACAN, $this->valoresDe($michoacan));
        $this->assertSame(Ciudades::GUERRERO, $this->valoresDe($guerrero));
        $this->assertSame(['option', Ciudades::OTRO_LUGAR], [$otroLugar->tagName, $otroLugar->getAttribute('value')]);
    }

    /** @return list<string> */
    private function valoresDe(DOMElement $grupo): array
    {
        $valores = [];
        foreach ($grupo->getElementsByTagName('option') as $opcion) {
            $valores[] = $opcion->getAttribute('value');
        }

        return $valores;
    }

    /** @return list<string> valores de las opciones marcadas */
    private function elegidas(DOMXPath $xpath): array
    {
        $valores = [];
        foreach ($xpath->query("//select[@name='ciudad']//option[@selected]") as $opcion) {
            $valores[] = $opcion->getAttribute('value');
        }

        return $valores;
    }

    private function campo(DOMXPath $xpath): DOMElement
    {
        $campo = $xpath->query("//select[@name='ciudad']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $campo, 'El campo es un desplegable.');

        return $campo;
    }

    private function directorio(): DOMXPath
    {
        return $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes')->assertOk()->getContent());
    }

    /** @return list<string> */
    private function opcionesDelFiltro(DOMXPath $xpath): array
    {
        $opciones = [];
        foreach ($xpath->query("//select[@name='ciudad']/option[@value!='']") as $opcion) {
            $opciones[] = $opcion->getAttribute('value');
        }

        return $opciones;
    }

    private function filasDelDirectorio(string $html): string
    {
        $cuerpo = $this->xpath($html)->query('//tbody')->item(0);

        return $cuerpo === null ? '' : $cuerpo->textContent;
    }

    /** El script del registro sin sus comentarios. */
    private function scriptDelRegistro(string $html): string
    {
        $scripts = '';
        foreach ($this->xpath($html)->query('//script[not(@src)]') as $script) {
            $scripts .= $script->textContent."\n";
        }

        return preg_replace('#^\s*//.*$#m', '', $scripts);
    }

    /** Bloque de una hoja entre `/* Spec 051 *\/` y `/* Fin spec 051 *\/`. */
    private function bloque051(string $hoja): string
    {
        $css = file_get_contents(public_path($hoja));
        $inicio = strpos($css, '/* Spec 051 */');
        $fin = strpos($css, '/* Fin spec 051 */');
        $this->assertNotFalse($inicio, "{$hoja}: empieza el bloque 051.");
        $this->assertNotFalse($fin, "{$hoja}: termina el bloque 051.");

        return substr($css, $inicio, $fin - $inicio);
    }

    private function tokenDeLaHoja(string $hoja, string $token): ?string
    {
        preg_match('/^\s*'.preg_quote($token, '/').'\s*:\s*([^;]+);/m', file_get_contents(public_path($hoja)), $valor);

        return $valor[1] ?? null;
    }

    /** @return list<string> */
    private function clases(DOMElement $elemento): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($elemento->getAttribute('class')))));
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
