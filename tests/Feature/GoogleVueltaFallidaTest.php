<?php

namespace Tests\Feature;

use App\Models\Usuario;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PeticionPsr;
use GuzzleHttp\Psr7\Response as RespuestaPsr;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

/**
 * Spec 031, CA5 (TM6, TM7): la vuelta del acceso con Google cuando falla.
 *
 * Una vuelta sin inicio previo (o con el estado caducado o manipulado), cancelada
 * en Google o con un error del proveedor vuelve al inicio de sesión con un
 * mensaje, sin sesión y sin crear ni vincular cuentas. Emite `Failed` para que la
 * auditoría de la spec 021 la registre como login fallido (excepción P7), sigue
 * contando para el límite de intentos de la vuelta de Google (030 CA8) y solo el
 * error del proveedor se reporta al log (/analyze A12).
 */
class GoogleVueltaFallidaTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE = 'No se pudo iniciar sesión con Google. Inténtalo de nuevo.';

    private const MENSAJE_LIMITE = '/^Demasiados intentos\. Vuelve a intentarlo en \d+ segundos\.$/';

    /** Error en `email` que dejó la última respuesta en la sesión (ver LimiteIntentosTest). */
    private function errorEmail(): ?string
    {
        $errores = session('errors');

        if ($errores instanceof ViewErrorBag) {
            return $errores->first('email') ?: null;
        }

        return $errores['default']['messages']['email'][0] ?? null;
    }

    /** Socialite simulado cuyo `user()` lanza la excepción dada. */
    private function proveedorQueFalla(\Throwable $error): void
    {
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andThrow($error);
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);
    }

    private function errorDelProveedor(): ClientException
    {
        return new ClientException(
            'Client error: `POST https://oauth2.googleapis.com/token` resulted in a `401 Unauthorized` response',
            new PeticionPsr('POST', 'https://oauth2.googleapis.com/token'),
            new RespuestaPsr(401, [], '{"error": "invalid_client"}'),
        );
    }

    private function volverDeGoogle(string $consulta = '', string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/auth/google/callback'.$consulta);
    }

    private function assertVuelveAlLoginSinSesion($respuesta): void
    {
        $respuesta->assertRedirect(route('login'));
        $this->assertSame(self::MENSAJE, $this->errorEmail());
        $this->assertGuest();
        $this->assertNull(session('google_registro'));
    }

    public function test_sin_inicio_previo_vuelve_al_login_con_mensaje(): void
    {
        $this->proveedorQueFalla(new InvalidStateException);
        $usuarios = Usuario::count();

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle());
        $this->assertSame($usuarios, Usuario::count());
    }

    public function test_cancelar_en_google_vuelve_al_login_sin_llamar_al_proveedor(): void
    {
        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldNotReceive('user');
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?error=access_denied&state=cualquiera'));
    }

    public function test_un_error_del_proveedor_vuelve_al_login(): void
    {
        $this->proveedorQueFalla($this->errorDelProveedor());

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=manipulado&state=cualquiera'));
    }

    public function test_el_fallo_no_vincula_una_cuenta_existente(): void
    {
        $cuenta = Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'Sin Google',
            'email' => 'vuelta.'.Str::random(8).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
        $this->proveedorQueFalla(new InvalidStateException);

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=manipulado&state=manipulado'));
        $this->assertNull($cuenta->fresh()->google_id);
    }

    public function test_el_fallo_cuenta_como_login_fallido(): void
    {
        Event::fake([Failed::class]);
        $this->proveedorQueFalla(new InvalidStateException);

        $this->volverDeGoogle()->assertRedirect(route('login'));

        Event::assertDispatchedTimes(Failed::class, 1);
        Event::assertDispatched(Failed::class, fn (Failed $evento) => $evento->guard === 'web' && $evento->user === null);
    }

    public function test_los_fallos_siguen_contando_para_el_limite(): void
    {
        $this->proveedorQueFalla(new InvalidStateException);

        for ($i = 0; $i < 5; $i++) {
            $this->volverDeGoogle('', '10.0.31.1')->assertRedirect(route('login'));
            $this->assertSame(self::MENSAJE, $this->errorEmail());
        }

        $this->volverDeGoogle('', '10.0.31.1')->assertRedirect(route('login'));
        $this->assertMatchesRegularExpression(self::MENSAJE_LIMITE, (string) $this->errorEmail());
    }

    public function test_el_error_del_proveedor_se_reporta(): void
    {
        Exceptions::fake();
        $this->proveedorQueFalla($this->errorDelProveedor());

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=manipulado&state=cualquiera', '10.0.31.2'));
        Exceptions::assertReported(ClientException::class);
    }

    public function test_el_estado_invalido_no_se_reporta(): void
    {
        Exceptions::fake();
        $this->proveedorQueFalla(new InvalidStateException);

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=manipulado&state=cualquiera', '10.0.31.3'));
        Exceptions::assertNothingReported();
    }

    public function test_un_error_de_red_vuelve_al_login_y_se_reporta(): void
    {
        Exceptions::fake();
        $this->proveedorQueFalla(new ConnectException(
            'cURL error 6: Could not resolve host: oauth2.googleapis.com',
            new PeticionPsr('POST', 'https://oauth2.googleapis.com/token'),
        ));

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=valido&state=cualquiera', '10.0.31.4'));
        Exceptions::assertReported(ConnectException::class);
    }

    /**
     * Credenciales de prueba para que Socialite construya el proveedor real de Google: el
     * comprobador del `state` lanza la excepción antes de cualquier llamada a Google (TM6).
     */
    private function proveedorRealDePrueba(): void
    {
        config(['services.google' => [
            'client_id' => 'cliente-de-pruebas',
            'client_secret' => 'secreto-de-pruebas',
            'redirect' => 'http://localhost/auth/google/callback',
        ]]);
    }

    public function test_sin_state_en_la_sesion_el_comprobador_real_vuelve_al_login(): void
    {
        Exceptions::fake();
        $this->proveedorRealDePrueba();

        $this->assertVuelveAlLoginSinSesion($this->volverDeGoogle('?code=robado&state=inventado', '10.0.31.5'));
        Exceptions::assertNothingReported();
    }

    public function test_un_state_distinto_del_guardado_vuelve_al_login(): void
    {
        Exceptions::fake();
        $this->proveedorRealDePrueba();

        $respuesta = $this->withSession(['state' => 'el-que-guardo-la-app'])
            ->withServerVariables(['REMOTE_ADDR' => '10.0.31.6'])
            ->get('/auth/google/callback?code=robado&state=otro-distinto');

        $this->assertVuelveAlLoginSinSesion($respuesta);
        Exceptions::assertNothingReported();
    }
}
