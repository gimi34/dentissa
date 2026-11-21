<?php

namespace Tests\Feature;

use App\Agenda\RelojClinica;
use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use App\Http\Controllers\GoogleAuthController;
use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use App\Pacientes\PosiblesDuplicados;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\EventosDuranteLaPrueba;
use Tests\Feature\Concerns\ReglasCss;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 054: el personal da de alta pacientes sin correo, que no tienen acceso a la aplicación hasta
 * que se les añade uno; el alta avisa de posibles duplicados; el directorio y la ficha lo dicen; y
 * el alta y el correo añadido quedan en la auditoría. Los apellidos de prueba no existen en el
 * volcado, para no chocar con sus pacientes.
 */
class PacienteSinCorreoTest extends TestCase
{
    use DatabaseTransactions;
    use EscenarioEstadosCita;
    use EventosDuranteLaPrueba;
    use ReglasCss;

    private const SIN_ACCESO = 'Paciente registrado sin correo: no tiene acceso a la aplicación.';

    private const TELEFONO_OBLIGATORIO = 'Sin correo, el teléfono es obligatorio para poder contactarle.';

    private const CORREO_OBLIGATORIO = 'El correo es obligatorio: es el acceso del paciente a la aplicación.';

    private const CORREO_REPETIDO = 'Este correo ya está registrado en el sistema.';

    private const MARCA = 'Sin correo · sin acceso a la aplicación';

    private const AVISO = 'Ya existe un paciente con estos datos';

    private const APELLIDO = 'Sincorreo Pruebas';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Cache::flush();
        $this->crearEscenario();
    }

    /** @return array<string, array{string}> */
    public static function quienesDanDeAlta(): array
    {
        return ['admin' => ['admin'], 'asistente' => ['asistente']];
    }

    // ── T011 · Alta (CA1, CA2, TM9) ────────────────────────────────────

    #[DataProvider('quienesDanDeAlta')]
    public function test_el_admin_y_el_asistente_dan_de_alta_sin_correo(string $rol): void
    {
        $respuesta = $this->alta($rol, $this->datos());

        $respuesta->assertSessionHasNoErrors()->assertRedirect(route("{$rol}.pacientes"));
        $respuesta->assertSessionHas('success', self::SIN_ACCESO);
        $respuesta->assertSessionMissing('password_generada');

        $cuenta = $this->cuenta();
        $this->assertNull($cuenta->email);
        $this->assertNull($cuenta->password_hash, 'Sin correo no hay contraseña.');
        $this->assertSame('cliente', $cuenta->rol);
        $this->assertSame(1, (int) $cuenta->activo);
        $this->assertSame('5550540054', $cuenta->telefono);
        $this->assertSame(1, DB::table('pacientes')->where('usuario_id', $cuenta->id)->count(), 'Tiene su ficha.');
    }

    public function test_un_correo_solo_con_espacios_cuenta_como_vacio(): void
    {
        $this->alta('admin', $this->datos(['email' => '   ']))->assertSessionHasNoErrors()
            ->assertSessionHas('success', self::SIN_ACCESO);

        $this->assertNull($this->cuenta()->email);
    }

    public function test_al_paciente_sin_correo_se_le_agenda_y_se_le_crea_expediente(): void
    {
        $this->conHorarios();
        $this->alta('admin', $this->datos())->assertSessionHasNoErrors();
        $paciente = DB::table('pacientes')->where('usuario_id', $this->cuenta()->id)->value('id');

        $this->actingAs($this->admin)->post('/admin/citas', [
            'paciente_id' => $paciente, 'dentista_id' => $this->d1, 'servicio_id' => '',
            'fecha' => $this->proximoDiaLaborable(), 'hora' => '10:00', 'duracion_min' => 30,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('citas')->where('paciente_id', $paciente)->count(), 'Se le agenda una cita.');

        $this->actingAs($this->dentista1)->post('/dentista/expedientes', [
            'paciente_id' => $paciente, 'dentista_id' => $this->d1, 'diagnostico' => 'Revisión 054',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('expedientes')->where('paciente_id', $paciente)->count(), 'Se le crea un expediente.');
    }

    public function test_el_alta_con_correo_sigue_como_hoy(): void
    {
        $correo = 'con.correo.'.Str::lower(Str::random(6)).'@example.test';

        $respuesta = $this->alta('admin', $this->datos(['email' => $correo, 'telefono' => '']));

        $respuesta->assertSessionHasNoErrors();
        $generada = session('password_generada');
        $this->assertIsArray($generada, 'Sin contraseña escrita, se genera y se muestra una vez.');
        $this->assertSame($correo, $generada['email']);
        $cuenta = $this->cuenta();
        $this->assertSame($correo, $cuenta->email);
        $this->assertNotNull($cuenta->password_hash);
        $this->assertNull($cuenta->telefono, 'Con correo, el teléfono sigue siendo opcional.');
    }

    public function test_sin_correo_ni_telefono_se_rechaza_y_conserva_lo_escrito(): void
    {
        $antes = $this->cuentasDePrueba();

        $respuesta = $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['telefono' => '', 'alergias' => 'Polen 054']));

        $respuesta->assertRedirect('/admin/pacientes/crear');
        $respuesta->assertSessionHasErrors(['telefono' => self::TELEFONO_OBLIGATORIO]);
        $this->assertSame('Sinco', session()->getOldInput('nombre'));
        $this->assertSame('Polen 054', session()->getOldInput('alergias'));
        $this->assertSame($antes, $this->cuentasDePrueba(), 'No se crea nada.');
    }

    public function test_una_contrasena_sin_correo_no_se_guarda(): void
    {
        $this->alta('admin', $this->datos(['password' => 'temporal-de-prueba']))->assertSessionHasNoErrors()
            ->assertSessionMissing('password_generada');

        $this->assertNull($this->cuenta()->password_hash);
    }

    public function test_una_contrasena_corta_sin_correo_no_impide_el_alta(): void
    {
        $this->alta('admin', $this->datos(['password' => '123']))->assertSessionHasNoErrors()
            ->assertSessionHas('success', self::SIN_ACCESO);

        $this->assertNull($this->cuenta()->password_hash);
    }

    public function test_un_correo_no_valido_o_un_array_se_rechaza(): void
    {
        $antes = $this->cuentasDePrueba();

        $this->alta('admin', $this->datos(['email' => 'no-es-correo']))->assertSessionHasErrors('email');
        $this->alta('admin', $this->datos(['email' => ['x@example.test']]))->assertSessionHasErrors('email');

        $this->assertSame($antes, $this->cuentasDePrueba());
    }

    // ── T012 · Aviso de duplicado (CA3, CA9, TM3, TM8, TM10) ────────────

    public function test_el_aviso_aparece_y_no_crea_nada(): void
    {
        $existente = $this->existente('Duplo', '5551112233');
        $antes = $this->cuentasDePrueba();

        $respuesta = $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => 'duplo', 'telefono' => '555 111 2233']));

        $respuesta->assertRedirect('/admin/pacientes/crear')->assertSessionHasNoErrors();
        $this->assertSame(['ids' => [$existente], 'total' => 1], session('posibles_duplicados'));
        $this->assertSame($antes, $this->cuentasDePrueba(), 'Todavía no se crea nada.');

        $aviso = $this->aviso($this->actingAs($this->admin)->get('/admin/pacientes/crear'));
        $this->assertStringContainsString(self::AVISO, $aviso->textContent);
        $this->assertStringContainsString('Duplo '.self::APELLIDO, $aviso->textContent);
    }

    public function test_registrar_de_todos_modos_crea_el_paciente(): void
    {
        $existente = $this->existente('Duplo', '5551112233');
        $antes = $this->cuentasDePrueba();

        $this->alta('admin', $this->datos(['nombre' => 'Duplo', 'telefono' => '5551112233', 'alergias' => 'Látex 054', 'confirmar_duplicado' => '1']))
            ->assertSessionHasNoErrors()->assertSessionHas('success', self::SIN_ACCESO);

        $this->assertSame($antes + 1, $this->cuentasDePrueba());
        $nueva = DB::table('pacientes')->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.apellido', self::APELLIDO)->where('pacientes.id', '!=', $existente)->first(['pacientes.alergias']);
        $this->assertSame('Látex 054', $nueva->alergias, 'Con los datos escritos.');
    }

    public function test_sin_coincidencias_no_hay_aviso(): void
    {
        $this->existente('Duplo', '5551112233');

        $this->alta('admin', $this->datos(['nombre' => 'Otro', 'telefono' => '5551112233']))
            ->assertSessionMissing('posibles_duplicados')->assertSessionHas('success', self::SIN_ACCESO);
    }

    public function test_con_mas_de_tres_coincidencias_dice_cuantas_mas(): void
    {
        foreach (range(1, 5) as $vez) {
            $this->existente('Quinto', '5552223344');
        }

        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => 'Quinto', 'telefono' => '5552223344']));
        $aviso = $this->aviso($this->actingAs($this->admin)->get('/admin/pacientes/crear'));

        $this->assertSame(3, $this->dentro($aviso, './/li')->length, 'Tres nombres.');
        $this->assertStringContainsString('y 2 más', $aviso->textContent);
    }

    public function test_el_aviso_no_muestra_datos_del_existente(): void
    {
        $existente = $this->existente('Duplo', '5551112233', [
            'fecha_nacimiento' => '1985-05-05', 'alergias' => 'Penicilina 054', 'notas_medicas' => 'Nota clínica 054',
        ], 'existente.054@example.test');

        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => 'Duplo', 'telefono' => '', 'email' => 'otro.054@example.test', 'fecha_nacimiento' => '1985-05-05']));
        $aviso = $this->aviso($this->actingAs($this->admin)->get('/admin/pacientes/crear'));
        $html = $aviso->ownerDocument->saveHTML($aviso);

        $this->assertNotNull(DB::table('pacientes')->where('id', $existente)->value('id'));
        foreach (['5551112233', '1985', '05/05', 'existente.054@example.test', 'Penicilina 054', 'Nota clínica 054'] as $dato) {
            $this->assertStringNotContainsString($dato, $html, "El aviso no enseña «{$dato}».");
        }
        $this->assertStringNotContainsString('teléfono que', $html, 'El texto no dice cuál de los dos datos coincide.');
    }

    #[DataProvider('quienesDanDeAlta')]
    public function test_los_enlaces_del_aviso_usan_la_ruta_del_rol(string $rol): void
    {
        $existente = $this->existente('Duplo', '5551112233');
        $usuario = $rol === 'admin' ? $this->admin : $this->asistente;

        $this->actingAs($usuario)->from("/{$rol}/pacientes/crear")
            ->post("/{$rol}/pacientes", $this->datos(['nombre' => 'Duplo', 'telefono' => '5551112233', 'dentista_id' => $this->d1]));
        $enlaces = $this->dentro($this->aviso($this->actingAs($usuario)->get("/{$rol}/pacientes/crear")), './/a');

        $this->assertSame(1, $enlaces->length);
        $this->assertSame(route("{$rol}.pacientes.show", $existente), $enlaces->item(0)->getAttribute('href'));
        $this->assertSame('Ver ficha de Duplo '.self::APELLIDO, trim(preg_replace('/\s+/', ' ', $enlaces->item(0)->textContent)));
    }

    public function test_el_aviso_muestra_los_nombres_escapados(): void
    {
        $this->existente('<script>alert(1)</script>', '5551112233');

        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => '<script>alert(1)</script>', 'telefono' => '5551112233']));
        $html = $this->actingAs($this->admin)->get('/admin/pacientes/crear')->getContent();

        $this->assertStringContainsString(self::AVISO, $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_la_contrasena_no_queda_en_la_sesion_con_el_aviso(): void
    {
        $this->existente('Duplo', '5551112233');
        $correo = 'aviso.'.Str::lower(Str::random(6)).'@example.test';
        $datos = $this->datos(['nombre' => 'Duplo', 'telefono' => '5551112233', 'email' => $correo]);

        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $datos + ['password' => 'secreto-de-prueba'])
            ->assertSessionHas('posibles_duplicados');

        $this->assertArrayNotHasKey('password', session()->getOldInput(), 'La contraseña no llega a la sesión.');
        $this->assertSame($correo, session()->getOldInput('email'), 'El resto sí se conserva.');
        $pantalla = $this->actingAs($this->admin)->get('/admin/pacientes/crear')->getContent();
        $this->assertStringNotContainsString('secreto-de-prueba', $pantalla);
        $this->assertSame('', $this->xpath($pantalla)->query('//input[@name="password"]')->item(0)->getAttribute('value'));

        $this->alta('admin', $datos + ['confirmar_duplicado' => '1'])->assertSessionHasNoErrors();
        $this->assertSame($correo, session('password_generada')['email'] ?? null, 'Al confirmar con correo, se genera una.');
    }

    public function test_enter_en_un_campo_no_confirma_el_duplicado(): void
    {
        $this->existente('Duplo', '5551112233');
        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => 'Duplo', 'telefono' => '5551112233']));
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes/crear')->getContent());

        $envios = $xpath->query('//form[contains(@class, "form-paciente")]//*[(self::button and (not(@type) or @type="submit")) or (self::input and @type="submit")]');
        $this->assertGreaterThan(1, $envios->length);
        $this->assertNotSame('confirmar_duplicado', $envios->item(0)->getAttribute('name'), 'Enter usa el primer envío: no confirma.');
        $this->assertSame(1, $xpath->query('//button[@name="confirmar_duplicado"][@value="1"]')->length, '«Registrar de todos modos» existe.');
    }

    // ── T013 · Directorio y ficha (CA4) ────────────────────────────────

    public function test_el_directorio_y_la_ficha_muestran_sin_correo(): void
    {
        $paciente = $this->existente('Marca', '5554445566');

        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes?buscar=Marca+'.urlencode(self::APELLIDO))->assertOk()->getContent());
        $marca = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " marca-sin-correo ")]');
        $this->assertSame(1, $marca->length, 'El directorio marca al paciente sin correo.');
        $this->assertSame(self::MARCA, trim($marca->item(0)->textContent));
        $this->assertStringNotContainsString('solo-escritorio', $marca->item(0)->getAttribute('class'), 'También en el móvil.');
        $this->assertFalse($marca->item(0)->hasAttribute('style'), 'Sin estilo en línea (P9).');

        $ficha = $this->xpath($this->actingAs($this->admin)->get("/admin/pacientes/{$paciente}")->assertOk()->getContent());
        $fila = $ficha->query('//*[contains(concat(" ", normalize-space(@class), " "), " perfil-dato ")][.//*[normalize-space(.)="Correo"]]');
        $this->assertSame(1, $fila->length, 'La ficha tiene siempre la fila «Correo».');
        $this->assertStringContainsString(self::MARCA, $fila->item(0)->textContent);
    }

    public function test_la_busqueda_encuentra_al_paciente_sin_correo(): void
    {
        $this->existente('Buscable', '5557778899');

        foreach (['Buscable', '5557778899'] as $termino) {
            $this->actingAs($this->admin)->get('/admin/pacientes?buscar='.$termino)->assertOk()
                ->assertSee('Buscable '.self::APELLIDO);
        }
    }

    public function test_un_paciente_con_correo_se_ve_como_hoy(): void
    {
        $listado = $this->actingAs($this->admin)->get('/admin/pacientes?buscar='.urlencode($this->paciente1->email))->assertOk();
        $listado->assertSee($this->paciente1->email)->assertDontSee(self::MARCA);

        $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}")->assertOk()
            ->assertSee($this->paciente1->email)->assertDontSee(self::MARCA);
    }

    // ── T014 · Edición (CA5, CA6, TM4) ─────────────────────────────────

    /** @return array<string, array{string}> */
    public static function quienesEditan(): array
    {
        return ['admin' => ['admin'], 'dentista' => ['dentista']];
    }

    #[DataProvider('quienesEditan')]
    public function test_editar_sin_correo_no_lo_pide_y_exige_telefono(string $rol): void
    {
        $paciente = $this->existente('Editable', '5550001111');
        $usuario = $rol === 'admin' ? $this->admin : $this->dentista1;

        $this->actingAs($usuario)->put("/{$rol}/pacientes/{$paciente}", $this->edicion(['telefono' => '5550002222', 'alergias' => 'Ninguna 054']))
            ->assertSessionHasNoErrors();
        $cuenta = $this->cuentaDelPaciente($paciente);
        $this->assertNull($cuenta->email);
        $this->assertSame('5550002222', $cuenta->telefono);
        $this->assertSame('Ninguna 054', DB::table('pacientes')->where('id', $paciente)->value('alergias'));

        $this->actingAs($usuario)->put("/{$rol}/pacientes/{$paciente}", $this->edicion(['telefono' => '']))
            ->assertSessionHasErrors(['telefono' => self::TELEFONO_OBLIGATORIO]);
        $this->assertSame('5550002222', $this->cuentaDelPaciente($paciente)->telefono, 'Sin cambios.');
    }

    public function test_con_correo_dejarlo_vacio_se_rechaza(): void
    {
        $this->actingAs($this->admin)->put("/admin/pacientes/{$this->p1}", [
            'nombre' => $this->paciente1->nombre, 'apellido' => $this->paciente1->apellido, 'email' => '', 'telefono' => '5550003333',
        ])->assertSessionHasErrors(['email' => self::CORREO_OBLIGATORIO]);

        $this->assertSame($this->paciente1->email, $this->paciente1->fresh()->email);
    }

    public function test_anadir_un_correo_lo_guarda_sin_enviar_nada(): void
    {
        Mail::fake();
        $paciente = $this->existente('Editable', '5550001111');

        $this->actingAs($this->admin)->put("/admin/pacientes/{$paciente}", $this->edicion(['email' => 'nuevo.acceso.054@example.test']))
            ->assertSessionHasNoErrors();

        $cuenta = $this->cuentaDelPaciente($paciente);
        $this->assertSame('nuevo.acceso.054@example.test', $cuenta->email);
        $this->assertNull($cuenta->password_hash, 'No se crea ni se muestra contraseña.');
        $this->assertNull(session('password_generada'));
        Mail::assertNothingOutgoing();
    }

    public function test_tras_anadir_el_correo_el_paciente_crea_su_contrasena_y_entra(): void
    {
        Mail::fake();
        $paciente = $this->existente('Editable', '5550001111');
        $this->actingAs($this->admin)->put("/admin/pacientes/{$paciente}", $this->edicion(['email' => 'entra.054@example.test']));
        $this->app['auth']->forgetGuards();
        Cache::flush();

        $this->post('/contrasena/olvidada', ['email' => 'entra.054@example.test']);
        Mail::assertSent(EnlaceRestablecimiento::class, fn ($correo) => $correo->hasTo('entra.054@example.test'));

        $cuenta = Usuario::findOrFail($this->cuentaDelPaciente($paciente)->id);
        $this->post('/contrasena/nueva', [
            'token' => Password::broker()->createToken($cuenta), 'cuenta' => $cuenta->id,
            'password' => 'contrasena-nueva-segura', 'password_confirmation' => 'contrasena-nueva-segura',
        ])->assertRedirect(url('/login'));

        Cache::flush();
        $this->post('/login', ['email' => 'entra.054@example.test', 'password' => 'contrasena-nueva-segura']);
        $this->assertAuthenticatedAs($cuenta);
    }

    public function test_anadir_un_correo_ya_registrado_se_rechaza(): void
    {
        $paciente = $this->existente('Editable', '5550001111');

        $this->actingAs($this->admin)->put("/admin/pacientes/{$paciente}", $this->edicion(['email' => $this->paciente1->email]))
            ->assertSessionHasErrors(['email' => self::CORREO_REPETIDO]);

        $this->assertNull($this->cuentaDelPaciente($paciente)->email);
    }

    public function test_el_asistente_sigue_sin_poder_editar_un_paciente(): void
    {
        $paciente = $this->existente('Editable', '5550001111');

        foreach (['admin', 'dentista'] as $prefijo) {
            $this->actingAs($this->asistente)->put("/{$prefijo}/pacientes/{$paciente}", $this->edicion(['telefono' => '5559990000']))
                ->assertForbidden();
        }
        $this->assertSame('5550001111', $this->cuentaDelPaciente($paciente)->telefono);
    }

    // ── T015 · Abuso: acceso y roles (CA7, CA8, CA10, TM1, TM2) ─────────

    public function test_no_hay_forma_de_entrar_a_una_cuenta_sin_correo(): void
    {
        Mail::fake();
        $paciente = $this->existente('Cerrado', '5556667777');
        $cuenta = $this->cuentaDelPaciente($paciente);

        foreach (['Cerrado', '5556667777', '', 'cerrado@'] as $intento) {
            Cache::flush();
            $this->post('/login', ['email' => $intento, 'password' => 'cualquier-cosa']);
            $this->assertGuest();
        }

        Cache::flush();
        $this->post('/contrasena/olvidada', ['email' => 'inventado.054@example.test']);
        Mail::assertNothingOutgoing();

        $this->simularGoogle(['id' => 'google-054-nuevo', 'name' => 'Cerrado Otro', 'email' => 'google.054@example.test', 'avatar' => null]);
        $this->get('/auth/google/callback')->assertRedirect(route('register.contacto'));
        $this->assertGuest();
        $this->assertNull(DB::table('usuarios')->where('id', $cuenta->id)->value('google_id'), 'No se vincula con la cuenta sin correo.');
    }

    /** @return array<string, array{?string}> */
    public static function correosDeGoogleVacios(): array
    {
        return ['nulo' => [null], 'vacío' => [''], 'solo espacios' => ['   ']];
    }

    #[DataProvider('correosDeGoogleVacios')]
    public function test_google_sin_correo_ni_vincula_ni_crea_cuenta(?string $correo): void
    {
        $paciente = $this->existente('Cerrado', '5556667777');
        $cuenta = $this->cuentaDelPaciente($paciente);
        $antes = DB::table('usuarios')->count();

        $this->simularGoogle(['id' => 'google-054-sin-correo', 'name' => 'Sin Correo', 'email' => $correo, 'avatar' => null]);
        $respuesta = $this->get('/auth/google/callback');

        $respuesta->assertRedirect(route('login'));
        $this->assertSame(GoogleAuthController::MENSAJE_FALLO_GOOGLE, $this->errorEmail());
        $this->assertGuest();
        $this->assertNull(session('google_registro'), 'Sin registro a medias.');
        $this->assertNull(DB::table('usuarios')->where('id', $cuenta->id)->value('google_id'), 'No se vincula.');
        $this->assertSame($antes, DB::table('usuarios')->count(), 'No se crea ninguna cuenta.');
    }

    public function test_el_dentista_el_paciente_y_el_visitante_no_dan_de_alta(): void
    {
        $antes = $this->cuentasDePrueba();

        $this->actingAs($this->dentista1)->post('/admin/pacientes', $this->datos())->assertForbidden();
        $this->actingAs($this->dentista1)->post('/asistente/pacientes', $this->datos())->assertForbidden();
        $this->actingAs($this->dentista1)->post('/dentista/pacientes', $this->datos())->assertNotFound(); // sin ruta de alta: la app responde 404, no 405
        $this->actingAs($this->paciente1)->post('/admin/pacientes', $this->datos())->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->post('/admin/pacientes', $this->datos())->assertRedirect(route('login'));

        $this->assertSame($antes, $this->cuentasDePrueba(), 'Nadie sin permiso crea pacientes.');
    }

    public function test_el_alta_denegada_deja_acceso_denegado(): void
    {
        $this->actingAs($this->dentista1)->post('/admin/pacientes', $this->datos())->assertForbidden();

        $evento = $this->auditoriaDelTest()->where('accion', 'acceso_denegado')->first();
        $this->assertNotNull($evento);
        $this->assertSame($this->dentista1->id, $evento->actor_id);
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'admin.pacientes.store'], json_decode($evento->detalle, true));
    }

    // ── T016 · Auditoría (CA10, CA11, TM5, TM6, TM7) ────────────────────

    /** @return array<string, array{bool, bool}> */
    public static function casosDeAlta(): array
    {
        return [
            'sin correo, sin aviso' => [false, false],
            'con correo, sin aviso' => [true, false],
            'sin correo, confirmado tras el aviso' => [false, true],
            'con correo, confirmado tras el aviso' => [true, true],
        ];
    }

    #[DataProvider('casosDeAlta')]
    public function test_el_alta_registra_un_evento_sin_datos_personales(bool $conCorreo, bool $trasElAviso): void
    {
        $existente = $trasElAviso ? $this->existente('Auditado', '5558889900') : null;
        $correo = $conCorreo ? 'auditado.'.Str::lower(Str::random(6)).'@example.test' : null;
        $datos = $this->datos(['nombre' => 'Auditado', 'telefono' => '5558889900', 'email' => $correo]);
        if ($trasElAviso) {
            $datos['confirmar_duplicado'] = '1';
        }

        $this->alta('admin', $datos)->assertSessionHasNoErrors();

        $paciente = DB::table('pacientes')->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.apellido', self::APELLIDO)
            ->when($existente, fn ($q) => $q->where('pacientes.id', '!=', $existente))
            ->value('pacientes.id');
        $eventos = $this->auditoriaDelTest()->where('accion', 'paciente_alta')->get();
        $this->assertCount(1, $eventos);
        $evento = $eventos->first();
        $this->assertSame($this->admin->id, $evento->actor_id);
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame('pacientes', $evento->recurso_tipo);
        $this->assertSame($paciente, $evento->recurso_id);
        $this->assertSame(['con_acceso' => $conCorreo, 'posible_duplicado' => $trasElAviso], json_decode($evento->detalle, true));
        $this->assertSinDatosPersonales($evento, ['Auditado', '5558889900', (string) $correo]);
    }

    public function test_la_confirmacion_falsa_no_altera_el_evento(): void
    {
        $this->alta('admin', $this->datos(['confirmar_duplicado' => '1']))->assertSessionHasNoErrors();

        $evento = $this->auditoriaDelTest()->where('accion', 'paciente_alta')->first();
        $this->assertSame(['con_acceso' => false, 'posible_duplicado' => false], json_decode($evento->detalle, true));
    }

    public function test_anadir_el_correo_registra_un_cambio_de_cuenta(): void
    {
        $paciente = $this->existente('Editable', '5550001111');

        $this->actingAs($this->dentista1)->put("/dentista/pacientes/{$paciente}", $this->edicion(['email' => 'cambio.054@example.test']))
            ->assertSessionHasNoErrors();

        $eventos = $this->auditoriaDelTest()->where('accion', 'paciente_correo_anadido')->get();
        $this->assertCount(1, $eventos);
        $evento = $eventos->first();
        $this->assertSame($this->dentista1->id, $evento->actor_id);
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame('pacientes', $evento->recurso_tipo);
        $this->assertSame($paciente, $evento->recurso_id);
        $this->assertSame(['email'], json_decode($evento->campos, true));
        $this->assertSame(['acceso_posible' => true], json_decode($evento->detalle, true));
        $this->assertSinDatosPersonales($evento, ['cambio.054@example.test', 'Editable']);
    }

    public function test_cambiar_un_correo_existente_no_registra_correo_anadido(): void
    {
        $paciente = $this->existente('Editable', '5550001111');

        $this->actingAs($this->admin)->put("/admin/pacientes/{$paciente}", $this->edicion())->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->put("/admin/pacientes/{$this->p1}", [
            'nombre' => $this->paciente1->nombre, 'apellido' => $this->paciente1->apellido, 'email' => 'p1.cambiado.054@example.test',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $this->auditoriaDelTest()->where('accion', 'paciente_correo_anadido')->count());
    }

    public function test_si_la_auditoria_falla_no_se_da_el_alta(): void
    {
        $this->almacenQueFalla();
        $antes = $this->cuentasDePrueba();

        $this->alta('admin', $this->datos())->assertStatus(503);

        $this->assertSame($antes, $this->cuentasDePrueba(), 'Sin evento, sin alta.');
    }

    public function test_si_la_auditoria_falla_no_se_guarda_el_correo(): void
    {
        $paciente = $this->existente('Editable', '5550001111');
        $this->almacenQueFalla();

        $this->actingAs($this->admin)->put("/admin/pacientes/{$paciente}", $this->edicion(['email' => 'falla.054@example.test']))
            ->assertStatus(503);

        $this->assertNull($this->cuentaDelPaciente($paciente)->email);
    }

    // ── T017 · Marcado, CSS y rendimiento (P9, RNF) ─────────────────────

    public function test_las_etiquetas_del_correo_segun_el_caso(): void
    {
        $sinCorreo = $this->existente('Editable', '5550001111');

        foreach (['/admin/pacientes/crear', "/admin/pacientes/{$sinCorreo}/edit"] as $pantalla) {
            $xpath = $this->xpath($this->actingAs($this->admin)->get($pantalla)->assertOk()->getContent());
            $this->assertStringContainsString('(opcional)', $xpath->query('//label[@for="campo-email"]')->item(0)->textContent, "{$pantalla}: correo opcional.");
            $this->assertFalse($this->campo($xpath, 'email')->hasAttribute('required'), "{$pantalla}: sin required.");
        }

        $xpath = $this->xpath($this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}/edit")->assertOk()->getContent());
        $this->assertStringNotContainsString('(opcional)', $xpath->query('//label[@for="campo-email"]')->item(0)->textContent);
        $this->assertTrue($this->campo($xpath, 'email')->hasAttribute('required'), 'Con correo, sigue obligatorio.');
    }

    public function test_las_ayudas_y_errores_van_en_aria_describedby(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes/crear')->getContent());
        foreach (['email', 'telefono', 'password'] as $campo) {
            $this->assertSame("ayuda-{$campo}", $this->campo($xpath, $campo)->getAttribute('aria-describedby'), "{$campo}: enlazado a su ayuda.");
            $ayuda = $xpath->query("//*[@id='ayuda-{$campo}']")->item(0);
            $this->assertInstanceOf(DOMElement::class, $ayuda);
            $this->assertStringContainsString('field-hint-aviso', $ayuda->getAttribute('class'));
        }

        $this->actingAs($this->admin)->from('/admin/pacientes/crear')->post('/admin/pacientes', $this->datos(['telefono' => '']));
        $xpath = $this->xpath($this->actingAs($this->admin)->get('/admin/pacientes/crear')->getContent());
        $telefono = $this->campo($xpath, 'telefono');
        $this->assertSame('true', $telefono->getAttribute('aria-invalid'));
        $this->assertSame('error-telefono ayuda-telefono', $telefono->getAttribute('aria-describedby'), 'El error primero y la ayuda después.');
    }

    public function test_el_aviso_tiene_role_alert_y_titulo(): void
    {
        $this->existente('Duplo', '5551112233');
        $this->actingAs($this->admin)->from('/admin/pacientes/crear')
            ->post('/admin/pacientes', $this->datos(['nombre' => 'Duplo', 'telefono' => '5551112233']));

        $aviso = $this->aviso($this->actingAs($this->admin)->get('/admin/pacientes/crear'));
        $this->assertSame('alert', $aviso->getAttribute('role'));
        $titulo = $this->dentro($aviso, './/h2')->item(0);
        $this->assertInstanceOf(DOMElement::class, $titulo);
        $this->assertSame(self::AVISO, trim($titulo->textContent));
        $this->assertSame('-1', $titulo->getAttribute('tabindex'));
        $this->assertTrue($titulo->hasAttribute('autofocus'), 'El foco empieza en el aviso (A14).');
        $this->assertSame('true', $this->dentro($aviso, './/*[local-name()="svg"]')->item(0)?->getAttribute('aria-hidden'), 'Icono oculto a los lectores.');
    }

    public function test_el_bloque_054_solo_usa_tokens(): void
    {
        $this->assertNotSame([], $this->literales('color: #fff; border-width: 2px'), 'Control positivo: detecta literales.');

        $cuerpos = implode(' ', array_column($this->analizarCss($this->bloque054()), 1));
        $this->assertNotSame('', trim($cuerpos), 'El bloque tiene reglas.');
        $this->assertSame([], $this->literales($cuerpos), 'El bloque 054 solo usa tokens.');

        foreach (['.aviso-duplicado', '.marca-sin-correo', '.envio-por-defecto'] as $selector) {
            $this->assertStringContainsString($selector, $this->bloque054(), "{$selector} está en el bloque.");
        }
        $this->assertStringContainsString('var(--text-small)', $this->declaraciones('.marca-sin-correo'));
        $this->assertStringContainsString('var(--text-muted)', $this->declaraciones('.marca-sin-correo'));
    }

    public function test_el_aviso_de_duplicado_llega_a_aa(): void
    {
        $tokens = $this->tokensDeColor();

        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--text'], $tokens['--amber-bg']), 'Texto sobre el ámbar.');
        $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--amber'], $tokens['--white']), 'Borde de la caja.');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--white'], $tokens['--p500']), 'Botón principal.');

        $caja = $this->declaraciones('.aviso-duplicado');
        $this->assertStringContainsString('var(--amber-bg)', $caja);
        $this->assertStringContainsString('var(--amber)', $caja);
        $this->assertStringContainsString('var(--text)', $caja);
        $this->assertStringContainsString('var(--p500)', $this->declaraciones('.aviso-duplicado .btn-primary'));
    }

    public function test_la_busqueda_de_duplicados_hace_una_sola_consulta(): void
    {
        $this->existente('Consulta', '5551230000');
        DB::flushQueryLog();
        DB::enableQueryLog();

        PosiblesDuplicados::buscar('Consulta', self::APELLIDO, '5551230000', null);

        $consultas = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $consultas, 'Una sola consulta.');
        $this->assertStringContainsString('like ?', Str::lower($consultas[0]['query']), 'Con binding.');
        $this->assertSame(['Sincorreo%'], $consultas[0]['bindings']);
    }

    // ── Ayudas ─────────────────────────────────────────────────────────

    /** Datos de un alta sin correo; el asistente siempre asigna un dentista suyo (A11). */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Sinco', 'apellido' => self::APELLIDO, 'email' => '', 'telefono' => '5550540054',
            'dentista_id' => $this->d1,
        ], $cambios);
    }

    private function edicion(array $cambios = []): array
    {
        return array_merge(['nombre' => 'Editable', 'apellido' => self::APELLIDO, 'email' => '', 'telefono' => '5550001111'], $cambios);
    }

    private function alta(string $rol, array $datos): TestResponse
    {
        $usuario = $rol === 'admin' ? $this->admin : $this->asistente;

        return $this->actingAs($usuario)->post("/{$rol}/pacientes", $datos);
    }

    /** Paciente ya registrado (sin correo salvo que se indique). Devuelve el id de su ficha. */
    private function existente(string $nombre, ?string $telefono, array $ficha = [], ?string $correo = null): string
    {
        $usuario = (string) Str::uuid();
        DB::table('usuarios')->insert([
            'id' => $usuario, 'nombre' => $nombre, 'apellido' => self::APELLIDO, 'email' => $correo,
            'password_hash' => null, 'rol' => 'cliente', 'telefono' => $telefono, 'activo' => 1,
        ]);
        $paciente = (string) Str::uuid();
        DB::table('pacientes')->insert(array_merge(['id' => $paciente, 'usuario_id' => $usuario], $ficha));

        return $paciente;
    }

    private function cuenta(): object
    {
        return DB::table('usuarios')->where('apellido', self::APELLIDO)->orderByDesc('creado_en')->firstOrFail();
    }

    private function cuentaDelPaciente(string $paciente): object
    {
        return DB::table('usuarios')->join('pacientes', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('pacientes.id', $paciente)->select('usuarios.*')->first();
    }

    private function cuentasDePrueba(): int
    {
        return DB::table('usuarios')->where('apellido', self::APELLIDO)->count();
    }

    private function proximoDiaLaborable(): string
    {
        $dia = RelojClinica::hoy()->addDay();
        while ($dia->isWeekend()) {
            $dia = $dia->addDay();
        }

        return $dia->toDateString();
    }

    private function simularGoogle(array $datos): void
    {
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn((new UsuarioGoogle)->setRaw([])->map($datos));
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);
    }

    private function errorEmail(): ?string
    {
        $errores = session('errors');

        return $errores instanceof ViewErrorBag ? ($errores->first('email') ?: null) : null;
    }

    private function almacenQueFalla(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->app->forgetInstance(Auditoria::class);
    }

    /** @param  list<string>  $datos */
    private function assertSinDatosPersonales(object $evento, array $datos): void
    {
        $fila = json_encode([$evento->campos, $evento->detalle, $evento->identificador_intentado, $evento->recurso_id]);
        foreach (array_filter($datos) as $dato) {
            $this->assertStringNotContainsString($dato, $fila, "El evento no lleva «{$dato}».");
        }
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    /** Consulta relativa a un nodo (`.//…`). */
    private function dentro(DOMElement $nodo, string $consulta): \DOMNodeList
    {
        return (new DOMXPath($nodo->ownerDocument))->query($consulta, $nodo);
    }

    private function aviso(TestResponse $respuesta): DOMElement
    {
        $aviso = $this->xpath($respuesta->assertOk()->getContent())
            ->query('//*[contains(concat(" ", normalize-space(@class), " "), " aviso-duplicado ")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $aviso, 'La pantalla muestra el aviso de duplicado.');

        return $aviso;
    }

    private function campo(DOMXPath $xpath, string $nombre): DOMElement
    {
        $campo = $xpath->query("//form//*[@name='{$nombre}'][not(@type='hidden')]")->item(0);
        $this->assertInstanceOf(DOMElement::class, $campo, "Existe el campo «{$nombre}».");

        return $campo;
    }

    private function bloque054(): string
    {
        $css = file_get_contents(public_path('css/app.css'));
        $inicio = strpos($css, '/* Spec 054 */');
        $fin = strpos($css, '/* Fin spec 054 */');
        $this->assertNotFalse($inicio, 'Empieza el bloque 054.');
        $this->assertNotFalse($fin, 'Termina el bloque 054.');

        return substr($css, $inicio, $fin - $inicio);
    }
}
