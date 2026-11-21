<?php

namespace Tests\Feature;

use App\Http\Controllers\RegisterController;
use App\Models\Usuario;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 002: autorregistro de pacientes.
 *
 * CA1–CA6 caracterizan lo que ya funciona, cada uno comprobado rompiendo el código que protege. CA8 cambia
 * comportamiento: el rechazo de un correo ya registrado deja de decir que tiene cuenta (plan, Estrategia de pruebas).
 */
class RegistroPacientesTest extends TestCase
{
    use DatabaseTransactions;
    use ReglasCss;

    private const CONTRASENA = 'registro-de-pruebas';

    // ── CA1 · el alta crea la cuenta y la ficha ───────────────────────────────────────

    public function test_el_alta_crea_la_cuenta_y_la_ficha_y_entra(): void
    {
        $datos = $this->datosDelAlta();

        $this->post('/registro', $datos)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));

        $cuenta = Usuario::where('email', $datos['email'])->sole();
        $this->assertSame(['cliente', 1], [$cuenta->rol, (int) $cuenta->activo]);
        $this->assertTrue(Hash::check(self::CONTRASENA, $cuenta->password_hash), 'La contraseña se guarda cifrada.');
        $this->assertNotSame(self::CONTRASENA, $cuenta->password_hash);
        $this->assertSame(1, DB::table('pacientes')->where('usuario_id', $cuenta->id)->count(), 'Tiene su ficha de paciente.');
        $this->assertAuthenticatedAs($cuenta);
    }

    /** CA1 (/analyze A8): la cuenta y la ficha van en una transacción; si la ficha falla, no queda la cuenta. */
    public function test_si_falla_la_ficha_no_queda_la_cuenta(): void
    {
        $datos = $this->datosDelAlta();
        DB::beforeExecuting(function (string $sql) {
            if (str_starts_with($sql, 'insert into `pacientes`')) {
                throw new RuntimeException('Fallo simulado de la ficha de paciente.');
            }
        });

        $this->post('/registro', $datos)->assertStatus(500);

        $this->assertDatabaseMissing('usuarios', ['email' => $datos['email']]);
        $this->assertGuest();
    }

    // ── CA2 · el dentista asignado ────────────────────────────────────────────────────
    // Los `id` y las fechas se fijan para que el resultado no dependa del orden de MariaDB en un empate (/analyze A1).

    public function test_se_asigna_el_dentista_activo_con_menos_pacientes(): void
    {
        $this->desactivarDentistasDelVolcado();
        $this->crearDentista('30000000-0000-4000-8000-000000000003', '2020-01-01 09:00:00', activo: false);
        $d1 = $this->crearDentista('10000000-0000-4000-8000-000000000001', '2021-01-01 09:00:00');
        $d2 = $this->crearDentista('20000000-0000-4000-8000-000000000002', '2022-01-01 09:00:00');
        $this->crearPacienteDe($d1);

        $this->assertSame($d2, $this->dentistaAsignado(), 'El activo con menos pacientes; nunca el inactivo.');
    }

    /**
     * Sin el orden por antigüedad, un empate en SQL no tiene orden definido y MariaDB elige uno cualquiera: con seis
     * empatados, el más antiguo solo ganaría por azar una vez de cada seis (/analyze N2).
     */
    public function test_a_igualdad_el_dentista_mas_antiguo(): void
    {
        $this->desactivarDentistasDelVolcado();
        foreach (['0', '3', '6', '9', 'c'] as $i => $prefijo) {
            $this->crearDentista($prefijo.'0000000-0000-4000-8000-00000000000'.$i, '2022-0'.($i + 1).'-01 09:00:00');
        }
        $antiguo = $this->crearDentista('f0000000-0000-4000-8000-00000000000f', '2021-01-01 09:00:00');

        $this->assertSame($antiguo, $this->dentistaAsignado(), 'A igualdad de pacientes, el más antiguo.');
    }

    /** CA2 (/analyze A7): sin ningún dentista activo, el alta se completa y la ficha queda sin dentista. */
    public function test_sin_dentistas_activos_el_alta_queda_sin_dentista(): void
    {
        $this->desactivarDentistasDelVolcado();
        $this->crearDentista('30000000-0000-4000-8000-000000000003', '2020-01-01 09:00:00', activo: false);

        $this->assertNull($this->dentistaAsignado());
    }

    // ── CA4 · registro con Google ─────────────────────────────────────────────────────

    public function test_el_registro_con_google_crea_la_cuenta_sin_contrasena(): void
    {
        // A14: las claves que deja la vuelta de Google, como en CiudadListaCerradaTest.
        $google = ['google_id' => 'google-002-'.Str::random(8), 'nombre' => 'Gala', 'apellido' => 'Google',
            'email' => 'google.'.Str::lower(Str::random(10)).'@example.test', 'foto_url' => null];

        $this->withSession(['google_registro' => $google])->post('/registro/contacto', [
            'telefono' => '443-555-0404', 'ciudad' => 'Ario', 'terminos' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));

        $cuenta = Usuario::where('email', $google['email'])->sole();
        $this->assertSame([$google['google_id'], 'cliente', null], [$cuenta->google_id, $cuenta->rol, $cuenta->password_hash]);
        $this->assertSame(1, DB::table('pacientes')->where('usuario_id', $cuenta->id)->count(), 'Tiene su ficha de paciente.');
        $this->assertAuthenticatedAs($cuenta);
    }

    // ── CA5 · el rol · CA6 · la contraseña ────────────────────────────────────────────

    /** CA5, TM3: el rol y `activo` los fija el servidor, aunque el formulario los envíe. */
    public function test_el_rol_es_siempre_cliente(): void
    {
        $datos = $this->datosDelAlta(['rol' => 'admin', 'activo' => '0']);

        $this->post('/registro', $datos)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));

        $cuenta = Usuario::where('email', $datos['email'])->sole();
        $this->assertSame(['cliente', 1], [$cuenta->rol, (int) $cuenta->activo]);
    }

    /** CA6 y el requisito no funcional (`Password::min(8)`); A12: 8 caracteres es el umbral exacto. */
    public function test_una_contrasena_corta_se_rechaza(): void
    {
        $corta = $this->datosDelAlta(['password' => 'siete77', 'password_confirmation' => 'siete77']);
        $this->from('/registro')->post('/registro', $corta)->assertRedirect('/registro')
            ->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        $this->assertDatabaseMissing('usuarios', ['email' => $corta['email']]);

        $justa = $this->datosDelAlta(['password' => 'ocho8888', 'password_confirmation' => 'ocho8888']);
        $this->post('/registro', $justa)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $this->assertDatabaseHas('usuarios', ['email' => $justa['email']]);
    }

    // ── CA3 y CA8 · el correo ya registrado ───────────────────────────────────────────

    /** CA3, CA8, TM1, TM4: el rechazo no dice que el correo tenga cuenta y ofrece entrar o recuperar la contraseña. */
    public function test_un_correo_registrado_se_rechaza_sin_decir_que_existe(): void
    {
        $existente = $this->crearCuentaExistente();

        // También con otras mayúsculas: la comparación del correo no distingue mayúsculas.
        $mayusculas = $this->datosDelAlta(['email' => Str::upper($existente)]);
        $this->from('/registro')->post('/registro', $mayusculas)->assertRedirect('/registro')
            ->assertSessionHasErrors(['email' => RegisterController::CORREO_NO_DISPONIBLE]);

        $datos = $this->datosDelAlta(['email' => $existente]);
        $pagina = $this->from('/registro')->followingRedirects()->post('/registro', $datos)->assertOk()->getContent();
        $this->assertSame(1, Usuario::where('email', $existente)->count(), 'No se crea ninguna cuenta.');

        $this->assertStringNotContainsString('ya está registrado', $pagina);
        $this->assertStringNotContainsString('ya existe', $pagina);
        $xpath = $this->xpath($pagina);
        $grupo = $this->grupoDelCorreo($xpath);
        $this->assertStringContainsString(RegisterController::CORREO_NO_DISPONIBLE, $grupo->textContent);
        // A10: los enlaces, dentro del grupo del campo y no en el aviso general; TM4: direcciones fijas.
        $this->assertSame([route('login'), route('contrasena.solicitud')], $this->enlaces($xpath, $grupo));

        // A5: vuelven los datos escritos salvo la contraseña; la casilla de términos, sin marcar.
        foreach (['nombre' => 'Rita', 'apellido' => 'Registro', 'telefono' => '443-555-0303', 'fecha_nacimiento' => '1992-03-14'] as $campo => $valor) {
            $this->assertSame($valor, $xpath->query("//input[@name='{$campo}']")->item(0)?->getAttribute('value'), "Vuelve {$campo}.");
        }
        $this->assertSame('', (string) $xpath->query("//input[@name='password']")->item(0)?->getAttribute('value'), 'La contraseña no vuelve.');
        $this->assertSame(1, $xpath->query("//select[@name='genero']/option[@value='femenino'][@selected]")->length, 'Vuelve el género.');
        $this->assertSame(1, $xpath->query("//select[@name='ciudad']//option[@value='Ario'][@selected]")->length, 'Vuelve la ciudad.');
        $this->assertSame('Calle del Registro 7', trim($xpath->query("//textarea[@name='direccion']")->item(0)?->textContent ?? ''), 'Vuelve la dirección.');
        $this->assertFalse($xpath->query("//input[@name='terminos']")->item(0)->hasAttribute('checked'), 'La casilla vuelve sin marcar.');
    }

    /** A13: el correo que vuelve al campo va escapado. */
    public function test_el_correo_que_vuelve_va_escapado(): void
    {
        $pagina = $this->from('/registro')->followingRedirects()
            ->post('/registro', $this->datosDelAlta(['email' => 'a<b>@example.test']))->assertOk()->getContent();

        $this->assertStringNotContainsString('a<b>@example.test', $pagina);
        $this->assertSame('a<b>@example.test', $this->xpath($pagina)->query("//input[@id='email']")->item(0)?->getAttribute('value'));
    }

    // ── CA8 · accesibilidad, otros errores y estilos ──────────────────────────────────

    public function test_el_error_del_correo_queda_asociado_al_campo(): void
    {
        $xpath = $this->xpath($this->rechazoDe($this->crearCuentaExistente()));
        $campo = $xpath->query("//input[@id='email']")->item(0);

        $this->assertSame('true', $campo?->getAttribute('aria-invalid'), 'El campo dice que tiene un error.');
        $descrito = $campo?->getAttribute('aria-describedby');
        $this->assertNotEmpty($descrito, 'El campo apunta a su mensaje.');
        $mensaje = $xpath->query("//*[@id='{$descrito}']")->item(0);
        $this->assertNotNull($mensaje, 'El mensaje existe con ese id.');
        $this->assertStringContainsString(RegisterController::CORREO_NO_DISPONIBLE, $mensaje->textContent);
    }

    public function test_otros_errores_del_correo_no_llevan_los_enlaces(): void
    {
        $xpath = $this->xpath($this->rechazoDe('no-es-un-correo'));
        $grupo = $this->grupoDelCorreo($xpath);

        $this->assertStringContainsString('Ingresa un correo válido.', $grupo->textContent);
        $this->assertSame([], $this->enlaces($xpath, $grupo), 'Sin los enlaces del correo ya registrado.');
    }

    /** A11: con el límite de la 030 agotado, el error del correo es el del límite, sin los enlaces, y vuelven los datos. */
    public function test_con_el_limite_agotado_no_salen_los_enlaces(): void
    {
        $existente = $this->crearCuentaExistente();
        for ($i = 0; $i < 5; $i++) {
            $this->from('/registro')->post('/registro', $this->datosDelAlta(['email' => $existente]));
        }

        $xpath = $this->xpath($this->rechazoDe($existente));
        $grupo = $this->grupoDelCorreo($xpath);

        $this->assertStringContainsString('Demasiados intentos', $grupo->textContent);
        $this->assertSame([], $this->enlaces($xpath, $grupo), 'El límite no lleva los enlaces.');
        $this->assertSame('Rita', $xpath->query("//input[@name='nombre']")->item(0)?->getAttribute('value'), 'Vuelven los datos.');
    }

    /** P9: el mensaje y sus enlaces solo usan clases y reglas sin valores literales. */
    public function test_los_enlaces_del_rechazo_solo_usan_tokens(): void
    {
        $xpath = $this->xpath($this->rechazoDe($this->crearCuentaExistente()));
        $grupo = $this->grupoDelCorreo($xpath);
        $this->assertSame(0, $xpath->query(".//*[contains(@class, 'error-msg')]/descendant-or-self::*[@style]", $grupo)->length, 'Sin style="".');

        $regla = $this->declaraciones('.page-register .error-msg a', 'css/auth.css');
        $this->assertNotSame('', $regla, 'auth.css tiene la regla de los enlaces del mensaje.');
        $this->assertSame([], $this->literales($regla), 'La regla no tiene valores literales.');
    }

    /** P9, WCAG 2.4.7 y 1.4.3 (/analyze A2): foco visible con los tokens y contraste ≥ 4,5:1 del texto del mensaje. */
    public function test_los_enlaces_del_rechazo_tienen_foco_y_contraste(): void
    {
        $foco = $this->declaraciones('.page-register .error-msg a:focus-visible', 'css/auth.css');
        foreach (['var(--focus-ring-width)', 'var(--focus-ring)', 'var(--focus-ring-offset)'] as $token) {
            $this->assertStringContainsString($token, $foco, "El foco usa {$token}.");
        }
        $this->assertSame([], $this->literales($foco), 'El foco no tiene valores literales.');

        $tokens = $this->tokensDeColor('css/auth.css');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--red'], $tokens['--white']), '--red sobre --white.');
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    private function crearCuentaExistente(): string
    {
        $correo = 'existente.'.Str::lower(Str::random(10)).'@example.test';
        Usuario::create(['nombre' => 'Eva', 'apellido' => 'Existente', 'email' => $correo, 'rol' => 'cliente',
            'activo' => 1, 'password_hash' => Hash::make('otra-contrasena-de-pruebas')]);

        return $correo;
    }

    /** Envía un alta con ese correo desde el formulario y devuelve la página del rechazo. */
    private function rechazoDe(string $correo): string
    {
        return $this->from('/registro')->followingRedirects()
            ->post('/registro', $this->datosDelAlta(['email' => $correo]))->assertOk()->getContent();
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new DOMXPath($documento);
    }

    /** El grupo del campo del correo (etiqueta, campo y mensaje), sin el aviso general de errores. */
    private function grupoDelCorreo(DOMXPath $xpath): DOMElement
    {
        $grupo = $xpath->query("//input[@id='email']/ancestor::div[contains(@class, 'field-group')][1]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $grupo, 'El campo del correo tiene su grupo.');

        return $grupo;
    }

    /** @return list<string> */
    private function enlaces(DOMXPath $xpath, DOMElement $grupo): array
    {
        return array_map(fn (DOMElement $a) => $a->getAttribute('href'), iterator_to_array($xpath->query('.//a[@href]', $grupo)));
    }

    /** Da de alta a alguien nuevo y devuelve el dentista de su ficha. */
    private function dentistaAsignado(): ?string
    {
        $datos = $this->datosDelAlta();
        $this->post('/registro', $datos)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $cuenta = Usuario::where('email', $datos['email'])->sole();

        return DB::table('pacientes')->where('usuario_id', $cuenta->id)->sole()->dentista_id;
    }

    /** Dentro de la transacción del test: solo cuentan los dentistas que crea cada caso. */
    private function desactivarDentistasDelVolcado(): void
    {
        DB::table('usuarios')
            ->whereIn('id', DB::table('personal')->where('tipo', 'dentista')->pluck('usuario_id'))
            ->update(['activo' => 0]);
    }

    /** El `id` y el correo de la cuenta siguen el orden del `id` de `personal`, para que ningún recorrido de MariaDB lo invierta. */
    private function crearDentista(string $id, string $creadoEn, bool $activo = true): string
    {
        $usuarioId = substr($id, 0, 9).'1111'.substr($id, 13);
        DB::table('usuarios')->insert([
            'id' => $usuarioId, 'nombre' => 'Dentista', 'apellido' => substr($id, 0, 8),
            'email' => 'dentista.'.$id.'@example.test', 'password_hash' => Hash::make(self::CONTRASENA),
            'rol' => 'dentista', 'activo' => $activo ? 1 : 0,
        ]);
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuarioId, 'tipo' => 'dentista', 'creado_en' => $creadoEn]);

        return $id;
    }

    private function crearPacienteDe(string $dentista): void
    {
        $usuarioId = (string) Str::uuid();
        DB::table('usuarios')->insert([
            'id' => $usuarioId, 'nombre' => 'Paciente', 'apellido' => 'Asignado',
            'email' => 'paciente.'.Str::lower(Str::random(10)).'@example.test', 'password_hash' => Hash::make(self::CONTRASENA),
            'rol' => 'cliente', 'activo' => 1,
        ]);
        DB::table('pacientes')->insert(['id' => (string) Str::uuid(), 'usuario_id' => $usuarioId, 'dentista_id' => $dentista]);
    }

    /** Un alta válida (/analyze A9): con la confirmación y los términos; la ciudad, de la lista. */
    private function datosDelAlta(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Rita',
            'apellido' => 'Registro',
            'email' => 'registro.'.Str::lower(Str::random(10)).'@example.test',
            'password' => self::CONTRASENA,
            'password_confirmation' => self::CONTRASENA,
            'telefono' => '443-555-0303',
            'fecha_nacimiento' => '1992-03-14',
            'genero' => 'femenino',
            'direccion' => 'Calle del Registro 7',
            'ciudad' => 'Ario',
            'terminos' => '1',
        ], $cambios);
    }
}
