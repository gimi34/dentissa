<?php

namespace Tests\Feature;

use App\Mail\ContrasenaCambiada;
use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use Tests\TestCase;

/**
 * Spec 041: recuperar la contraseña por correo.
 *
 *  - Pedir el enlace (T010): el login enlaza a «¿Olvidaste tu contraseña?» (CA1), la pantalla
 *    tiene su vuelta al login, una cuenta activa de cualquier rol recibe el correo y la pantalla
 *    responde «Revisa tu correo» (CA2), el correo escrito se valida y no se queda en la sesión
 *    (TM14), y con el interruptor apagado no hay rutas ni enlace (plan D8).
 *
 *  - Fijar la contraseña (T011): con un enlace vigente se reemplaza y se vuelve al login con el
 *    aviso (CA3); el error de campo vuelve a la URL del enlace con el error junto al campo y el
 *    enlace sigue sirviendo (CA4); caducado, usado o sustituido se rechaza con «Este enlace ya no
 *    sirve» (CA5); la cuenta de Google crea su contraseña y sigue con Google (CA6); el aviso de
 *    contraseña cambiada llega a la cuenta (CA7); sin token CSRF se rechaza (TM10).
 *
 * El correo sale después de responder (`afterResponse()`, plan D6); en los tests el kernel termina
 * la petición, así que `Mail::fake()` lo ve. `phpunit.xml` enciende el interruptor
 * (`RECUPERAR_CONTRASENA=true`, /analyze A10).
 */
class RecuperarContrasenaTest extends TestCase
{
    use DatabaseTransactions;

    private const SOLICITUD = '/contrasena/olvidada';

    private const ENVIADA = '/contrasena/enviada';

    private const RESTABLECER = '/contrasena/nueva';

    private const NUEVA = 'contrasena-nueva-segura';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('solicitar-enlace');
        RateLimiter::clear('fijar-contrasena');
    }

    private function enlace(Usuario $usuario, string $token): string
    {
        return self::RESTABLECER.'/'.$token.'?cuenta='.$usuario->id;
    }

    private function token(Usuario $usuario): string
    {
        return Password::broker()->createToken($usuario);
    }

    private function restablecer(Usuario $usuario, string $token, array $cambios = [])
    {
        RateLimiter::clear('fijar-contrasena');

        return $this->post(self::RESTABLECER, array_merge([
            'token' => $token,
            'cuenta' => $usuario->id,
            'password' => self::NUEVA,
            'password_confirmation' => self::NUEVA,
        ], $cambios));
    }

    private function contrasenaSinCambios(Usuario $usuario, string $anterior = 'contrasena-anterior'): void
    {
        $this->assertTrue(Hash::check($anterior, (string) $usuario->fresh()->password_hash), 'La contraseña no cambió.');
    }

    /** La pantalla «Este enlace ya no sirve» (CA5), con la opción de pedir otro. */
    private function assertEnlaceYaNoSirve(string $html): void
    {
        $xpath = $this->xpath($html);
        $this->assertSame('Este enlace ya no sirve', $this->texto($xpath, '//h1'));
        $this->assertSame(1, $xpath->query('//*[@role="alert"]')->length, 'El aviso lleva role="alert".');
        $pedir = $xpath->query('//a[normalize-space(.)="Pedir un enlace nuevo"]');
        $this->assertSame(1, $pedir->length, 'Ofrece pedir uno nuevo (CA5).');
        $this->assertSame(url(self::SOLICITUD), $pedir->item(0)->getAttribute('href'));
    }

    private function crearUsuario(string $rol = 'cliente', array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'recuperar.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('contrasena-anterior'),
        ], $datos));
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function texto(DOMXPath $xpath, string $consulta): string
    {
        $nodo = $xpath->query($consulta)->item(0);

        return $nodo === null ? '' : trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
    }

    public function test_el_login_enlaza_a_recuperar(): void
    {
        $xpath = $this->xpath($this->get('/login')->assertOk()->getContent());

        $enlaces = $xpath->query('//a[normalize-space(.)="¿Olvidaste tu contraseña?"]');
        $this->assertSame(1, $enlaces->length, 'El login tiene un solo enlace «¿Olvidaste tu contraseña?».');
        $this->assertSame(url(self::SOLICITUD), $enlaces->item(0)->getAttribute('href'), 'Lleva a la pantalla para pedir el enlace (CA1).');
    }

    public function test_la_pantalla_de_solicitud_tiene_volver(): void
    {
        $xpath = $this->xpath($this->get(self::SOLICITUD)->assertOk()->getContent());

        $this->assertSame('¿Olvidaste tu contraseña?', $this->texto($xpath, '//h1'));
        $formulario = $xpath->query('//form[@method="POST"]')->item(0);
        $this->assertNotNull($formulario, 'Hay un formulario para pedir el enlace.');
        $this->assertSame(url(self::SOLICITUD), $formulario->getAttribute('action'));
        $this->assertSame(1, $xpath->query('.//input[@name="email"][@type="email"]', $formulario)->length, 'Con el campo del correo.');
        $volver = $xpath->query('//a[normalize-space(.)="Volver a iniciar sesión"]');
        $this->assertSame(1, $volver->length, 'Una forma visible de volver al inicio de sesión (CA1).');
        $this->assertSame(url('/login'), $volver->item(0)->getAttribute('href'));
    }

    public function test_una_cuenta_activa_recibe_el_enlace(): void
    {
        Mail::fake();

        foreach (['cliente', 'asistente', 'dentista', 'admin'] as $rol) {
            RateLimiter::clear('solicitar-enlace');
            $usuario = $this->crearUsuario($rol);

            $this->from(self::SOLICITUD)->post(self::SOLICITUD, ['email' => $usuario->email])
                ->assertRedirect(self::ENVIADA);

            Mail::assertSent(EnlaceRestablecimiento::class, fn ($correo) => $correo->hasTo($usuario->email));

            $xpath = $this->xpath($this->get(self::ENVIADA)->assertOk()->getContent());
            $this->assertSame('Revisa tu correo', $this->texto($xpath, '//h1'), "Mensaje genérico para {$rol} (CA2).");
            $this->assertStringContainsString($usuario->email, $this->texto($xpath, '//main'), 'Repite el correo escrito.');
        }

        Mail::assertSent(EnlaceRestablecimiento::class, 4);
    }

    public function test_valida_cada_campo(): void
    {
        Mail::fake();

        // Pedir el enlace: correo requerido, con formato RFC y de hasta 255 caracteres.
        foreach (['' => 'vacío', 'no-es-un-correo' => 'sin formato', str_repeat('a', 250).'@example.test' => 'de más de 255'] as $correo => $caso) {
            RateLimiter::clear('solicitar-enlace');
            $this->from(self::SOLICITUD)->post(self::SOLICITUD, ['email' => $correo])
                ->assertRedirect(self::SOLICITUD)
                ->assertSessionHasErrors('email');
        }
        Mail::assertNothingSent();

        // Fijar la contraseña: contraseña requerida, de 8 a 255 caracteres, y confirmación igual.
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);
        foreach ([
            'password' => [
                ['password' => '', 'password_confirmation' => ''],
                ['password' => '1234567', 'password_confirmation' => '1234567'],
                ['password' => str_repeat('a', 256), 'password_confirmation' => str_repeat('a', 256)],
            ],
            'password_confirmation' => [['password_confirmation' => ''], ['password_confirmation' => 'otra-contrasena']],
        ] as $campo => $casos) {
            foreach ($casos as $cambios) {
                $this->restablecer($usuario, $token, $cambios)
                    ->assertRedirect(url($this->enlace($usuario, $token)))
                    ->assertSessionHasErrors($campo);
            }
        }
        $this->contrasenaSinCambios($usuario);

        // A33: un token o una cuenta sin formato válido (faltan, arreglo, cuenta que no es UUID) no
        // vuelve a la URL del enlace: «Este enlace ya no sirve».
        foreach ([
            ['token' => null], ['token' => ['x']], ['token' => str_repeat('a', 256)],
            ['cuenta' => null], ['cuenta' => ['x']], ['cuenta' => 'no-es-un-uuid'],
        ] as $cambios) {
            $respuesta = $this->restablecer($usuario, $token, $cambios);
            $destino = (string) $respuesta->headers->get('Location');
            $this->assertFalse($respuesta->isRedirect() && str_contains($destino, self::RESTABLECER.'/'), 'No vuelve a la URL del enlace.');
            $this->assertEnlaceYaNoSirve($respuesta->isRedirect() ? $this->get($destino)->getContent() : $respuesta->getContent());
        }
        $this->contrasenaSinCambios($usuario);
    }

    public function test_el_correo_no_queda_en_la_sesion_tras_mostrarlo(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        $this->from(self::SOLICITUD)->post(self::SOLICITUD, ['email' => $usuario->email])->assertRedirect(self::ENVIADA);
        $this->get(self::ENVIADA)->assertOk()->assertSee($usuario->email);
        $this->get('/login')->assertOk();

        // TM14: el flash dura una petición; tras mostrar «Revisa tu correo», el correo ya no está.
        $this->assertStringNotContainsString($usuario->email, json_encode(session()->all()), 'El correo no queda en la sesión.');
        // Sin el flash, «Revisa tu correo» lleva a pedir el enlace.
        $this->get(self::ENVIADA)->assertRedirect(self::SOLICITUD);
    }

    public function test_restablece_y_vuelve_al_login_con_exito(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $xpath = $this->xpath($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertSame('Elige tu contraseña nueva', $this->texto($xpath, '//h1'));

        $this->restablecer($usuario, $token)->assertRedirect(url('/login'));
        $this->assertTrue(Hash::check(self::NUEVA, $usuario->fresh()->password_hash), 'La contraseña se reemplazó (CA3).');
        $this->assertGuest();

        $xpath = $this->xpath($this->get('/login')->assertOk()->getContent());
        $this->assertStringContainsString('Tu contraseña cambió.', $this->texto($xpath, '//*[@role="status"]'), 'Aviso de éxito con role="status".');
    }

    public function test_entra_con_la_nueva_y_no_con_la_anterior(): void
    {
        $usuario = $this->crearUsuario();
        $this->restablecer($usuario, $this->token($usuario))->assertRedirect(url('/login'));

        $this->post('/login', ['email' => $usuario->email, 'password' => 'contrasena-anterior']);
        $this->assertGuest();

        $this->post('/login', ['email' => $usuario->email, 'password' => self::NUEVA]);
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_restablecer_envia_el_aviso_a_la_cuenta(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        $this->restablecer($usuario, $this->token($usuario))->assertRedirect(url('/login'));

        Mail::assertSent(ContrasenaCambiada::class, fn ($correo) => $correo->hasTo($usuario->email));
        Mail::assertNotSent(EnlaceRestablecimiento::class);
    }

    public function test_rechaza_menos_de_ocho_caracteres(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $this->restablecer($usuario, $token, ['password' => '1234567', 'password_confirmation' => '1234567'])
            ->assertRedirect(url($this->enlace($usuario, $token)))
            ->assertSessionHasErrors('password');
        $this->contrasenaSinCambios($usuario);

        $xpath = $this->xpath($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertErrorJuntoAlCampo($xpath, 'password');
    }

    public function test_rechaza_confirmacion_distinta_junto_al_campo(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $this->restablecer($usuario, $token, ['password_confirmation' => 'otra-contrasena-distinta'])
            ->assertRedirect(url($this->enlace($usuario, $token)))
            ->assertSessionHasErrors('password_confirmation');
        $this->contrasenaSinCambios($usuario);

        $xpath = $this->xpath($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertSame('Las dos contraseñas no coinciden.', $this->assertErrorJuntoAlCampo($xpath, 'password_confirmation'));
    }

    /** El campo lleva aria-invalid y aria-describedby al texto de su error, que devuelve (CA4). */
    private function assertErrorJuntoAlCampo(DOMXPath $xpath, string $campo): string
    {
        $entrada = $xpath->query('//input[@name="'.$campo.'"]')->item(0);
        $this->assertNotNull($entrada, "Está el campo {$campo}.");
        $this->assertSame('true', $entrada->getAttribute('aria-invalid'), "{$campo} con aria-invalid.");
        $error = '';
        foreach (preg_split('/\s+/', trim($entrada->getAttribute('aria-describedby'))) as $id) {
            $nodo = $id === '' ? null : $xpath->query('//*[@id="'.$id.'"]')->item(0);
            if ($nodo !== null && str_contains($nodo->getAttribute('class'), 'error')) {
                $error = trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
            }
        }
        $this->assertNotSame('', $error, "{$campo}: el error está enlazado por aria-describedby.");

        return $error;
    }

    public function test_tras_un_error_el_enlace_sigue_sirviendo(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $this->restablecer($usuario, $token, ['password' => 'corta', 'password_confirmation' => 'corta'])->assertSessionHasErrors('password');
        $this->get($this->enlace($usuario, $token))->assertOk()->assertSee('Elige tu contraseña nueva');

        $this->restablecer($usuario, $token)->assertRedirect(url('/login'));
        $this->assertTrue(Hash::check(self::NUEVA, $usuario->fresh()->password_hash), 'El mismo enlace sirvió tras el error (CA4).');
    }

    public function test_el_error_de_campo_vuelve_a_la_url_del_token_sin_referer(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        // /analyze A26: con no-referrer el POST no lleva Referer y la URL previa de la sesión es otra.
        $this->get('/login')->assertOk();
        $this->restablecer($usuario, $token, ['password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertRedirect(url($this->enlace($usuario, $token)))
            ->assertSessionHasErrors('password');
    }

    public function test_rechaza_un_enlace_caducado(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);
        $this->travel(61)->minutes();

        $this->assertRechazado($usuario, $token);
    }

    public function test_rechaza_un_enlace_usado(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);
        $this->restablecer($usuario, $token)->assertRedirect(url('/login'));

        $this->assertRechazado($usuario, $token, self::NUEVA);
    }

    public function test_rechaza_un_enlace_sustituido(): void
    {
        $usuario = $this->crearUsuario();
        $anterior = $this->token($usuario);
        $this->token($usuario);

        $this->assertRechazado($usuario, $anterior);
    }

    /** CA5: el enlace no sirve ni al abrirlo ni al enviarlo, y la contraseña no cambia. */
    private function assertRechazado(Usuario $usuario, string $token, string $vigente = 'contrasena-anterior'): void
    {
        $this->assertEnlaceYaNoSirve($this->get($this->enlace($usuario, $token))->assertOk()->getContent());

        RateLimiter::clear('fijar-contrasena');
        $respuesta = $this->followingRedirects()->post(self::RESTABLECER, [
            'token' => $token,
            'cuenta' => $usuario->id,
            'password' => 'otra-contrasena-mas',
            'password_confirmation' => 'otra-contrasena-mas',
        ]);
        $this->assertEnlaceYaNoSirve($respuesta->getContent());
        $this->contrasenaSinCambios($usuario, $vigente);
    }

    public function test_una_cuenta_de_google_crea_su_contrasena_y_sigue_con_google(): void
    {
        Mail::fake();
        $google = 'google-'.Str::random(12);
        $usuario = $this->crearUsuario('cliente', ['google_id' => $google, 'password_hash' => null]);

        $this->post(self::SOLICITUD, ['email' => $usuario->email])->assertRedirect(self::ENVIADA);
        Mail::assertSent(EnlaceRestablecimiento::class, fn ($correo) => $correo->hasTo($usuario->email));

        $this->restablecer($usuario, $this->token($usuario))->assertRedirect(url('/login'));
        $this->post('/login', ['email' => $usuario->email, 'password' => self::NUEVA]);
        $this->assertAuthenticatedAs($usuario);
        $this->post('/logout');
        $this->assertGuest();

        // CA6: sigue pudiendo entrar con Google.
        $this->assertSame($google, $usuario->fresh()->google_id);
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn((new UsuarioGoogle)->setRaw([])->map([
            'id' => $google, 'name' => 'Persona De Pruebas', 'email' => $usuario->email, 'avatar' => null,
        ]));
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);
        $this->get('/auth/google/callback');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_sin_token_csrf_se_rechaza(): void
    {
        // ValidateCsrfToken no actúa en los tests (runningUnitTests): se activa de verdad (/analyze A11).
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $usuario = $this->crearUsuario();

        $this->restablecer($usuario, $this->token($usuario))->assertStatus(419);
        $this->contrasenaSinCambios($usuario);
    }

    public function test_con_el_interruptor_apagado_no_hay_rutas_ni_enlace(): void
    {
        // Las rutas se registran al arrancar la aplicación: cambiar config() no las quita. Se apaga
        // en el entorno, se recrea la aplicación y se restaura al terminar (/analyze A32). El test
        // no escribe en la base: la transacción de DatabaseTransactions sigue en la conexión vieja.
        $this->cambiarInterruptor('false');

        try {
            $this->assertFalse(config('acceso.recuperar_contrasena'));
            $this->get(self::SOLICITUD)->assertNotFound();
            $this->post(self::SOLICITUD, ['email' => 'alguien@example.test'])->assertNotFound();
            $this->get(self::ENVIADA)->assertNotFound();
            $this->get('/contrasena/nueva/'.Str::random(64).'?cuenta='.Str::uuid())->assertNotFound();
            $this->post('/contrasena/nueva', ['token' => 'x', 'cuenta' => (string) Str::uuid()])->assertNotFound();

            $xpath = $this->xpath($this->get('/login')->assertOk()->getContent());
            $this->assertSame(0, $xpath->query('//a[contains(@href, "/contrasena")]')->length, 'El login no enlaza a la recuperación.');
            $this->assertSame(0, $xpath->query('//a[normalize-space(.)="¿Olvidaste tu contraseña?"]')->length, 'Ni muestra el enlace.');
        } finally {
            $this->cambiarInterruptor('true');
        }
    }

    private function cambiarInterruptor(string $valor): void
    {
        putenv("RECUPERAR_CONTRASENA={$valor}");
        $_ENV['RECUPERAR_CONTRASENA'] = $valor;
        $_SERVER['RECUPERAR_CONTRASENA'] = $valor;
        $this->refreshApplication();
    }
}
