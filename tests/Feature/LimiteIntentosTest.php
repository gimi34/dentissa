<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use Tests\TestCase;

/**
 * Spec 030, CA8 (RS4): límite de intentos en el inicio de sesión, el alta y la
 * vuelta del acceso con Google.
 *
 *  - Login: 5 fallos por minuto por correo e IP; el siguiente intento (el 6.º)
 *    se rechaza sin intentar autenticar y el éxito limpia el contador.
 *  - Alta (`/registro` y `/registro/contacto`, contador común) y vuelta de
 *    Google: 5 peticiones por minuto por IP, sea cual sea su resultado.
 *  - El mensaje es el mismo en los tres casos y no revela si la cuenta existe.
 *  - El rechazo del login y de Google emite `Lockout` para que la spec 020 lo
 *    audite como login fallido (excepción P7, /analyze A4); el del alta no es un
 *    login y no lo emite (/analyze A42).
 *
 * Cada test arranca con el limitador limpio: `CACHE_STORE=array` vive lo que
 * dura la aplicación de un test.
 */
class LimiteIntentosTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE = '/^Demasiados intentos\. Vuelve a intentarlo en \d+ segundos\.$/';

    private function crearUsuario(string $password = 'secreto-de-pruebas'): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'limite.'.Str::random(8).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make($password),
        ]);
    }

    private function intentarLogin(string $email, string $password, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/login')
            ->post('/login', ['email' => $email, 'password' => $password]);
    }

    private function fallarLogin(string $email, int $veces, string $ip = '127.0.0.1'): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $this->intentarLogin($email, 'contrasena-equivocada', $ip);
        }
    }

    /**
     * Error en `email` que dejó la última respuesta en la sesión. Con
     * `session.serialization=json`, al guardar la sesión la bolsa de errores
     * queda como arreglo (`Store::prepareErrorBagForSerialization`).
     */
    private function errorEmail(): ?string
    {
        $errores = session('errors');

        if ($errores instanceof ViewErrorBag) {
            return $errores->first('email') ?: null;
        }

        return $errores['default']['messages']['email'][0] ?? null;
    }

    /** Simula la vuelta de Google con una cuenta que aún no existe. */
    private function simularGoogle(): void
    {
        $usuarioGoogle = (new UsuarioGoogle)->setRaw([
            'given_name' => 'Persona',
            'family_name' => 'De Google',
        ])->map([
            'id' => 'google-'.Str::random(10),
            'name' => 'Persona De Google',
            'email' => 'google.'.Str::random(8).'@example.test',
            'avatar' => null,
        ]);

        $proveedor = Mockery::mock(Provider::class);
        $proveedor->shouldReceive('user')->andReturn($usuarioGoogle);
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);
    }

    private function volverDeGoogle(string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/auth/google/callback');
    }

    /** Petición de alta inválida: cuenta para el límite aunque no cree nada. */
    private function intentarAlta(string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/registro')
            ->post('/registro', ['nombre' => '']);
    }

    private function altaValida(string $email): array
    {
        return [
            'nombre' => 'Persona',
            'apellido' => 'Nueva',
            'email' => $email,
            'password' => 'secreto-de-pruebas',
            'password_confirmation' => 'secreto-de-pruebas',
        ];
    }

    public function test_tras_cinco_fallos_el_login_se_bloquea_un_minuto(): void
    {
        $usuario = $this->crearUsuario();

        $this->fallarLogin($usuario->email, 5);

        $respuesta = $this->intentarLogin($usuario->email, 'secreto-de-pruebas');

        $respuesta->assertRedirect('/login');
        $this->assertGuest();
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());

        $this->travel(61)->seconds();

        $this->intentarLogin($usuario->email, 'secreto-de-pruebas')->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_mensaje_no_revela_si_la_cuenta_existe(): void
    {
        $usuario = $this->crearUsuario();
        $inexistente = 'nadie.'.Str::random(8).'@example.test';

        $this->fallarLogin($usuario->email, 5);
        $this->intentarLogin($usuario->email, 'contrasena-equivocada');
        $conCuenta = (string) $this->errorEmail();

        $this->fallarLogin($inexistente, 5);
        $this->intentarLogin($inexistente, 'contrasena-equivocada');
        $sinCuenta = (string) $this->errorEmail();

        $this->assertMatchesRegularExpression(self::MENSAJE, $conCuenta);
        $this->assertMatchesRegularExpression(self::MENSAJE, $sinCuenta);
        $this->assertSame(
            preg_replace('/\d+/', 'N', $conCuenta),
            preg_replace('/\d+/', 'N', $sinCuenta),
        );
    }

    public function test_el_bloqueo_es_por_correo_y_direccion(): void
    {
        $bloqueado = $this->crearUsuario();
        $otro = $this->crearUsuario();

        $this->fallarLogin($bloqueado->email, 5, '10.0.0.1');

        // Otro correo desde la misma dirección entra.
        $this->intentarLogin($otro->email, 'secreto-de-pruebas', '10.0.0.1')
            ->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($otro);
        $this->post('/logout');

        // El mismo correo desde otra dirección entra.
        $this->intentarLogin($bloqueado->email, 'secreto-de-pruebas', '10.0.0.2')
            ->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($bloqueado);
    }

    /**
     * /review R1: la base compara el correo con `utf8mb4_unicode_ci`, que iguala
     * mayúsculas, acentos y letras de ancho completo (`ｌimite.…` entra en la
     * cuenta de `limite.…`). Laravel ya pliega los acentos al limpiar la clave del
     * limitador, pero no el ancho completo: cada variante abriría un contador
     * nuevo. Las variantes de la misma cuenta deben compartir el límite.
     */
    public function test_las_variantes_del_correo_comparten_el_limite(): void
    {
        $usuario = $this->crearUsuario();
        $sufijo = substr($usuario->email, strlen('limite'));
        $variantes = [
            'ｌimite'.$sufijo,
            'lｉmite'.$sufijo,
            'liｍite'.$sufijo,
            'limｉte'.$sufijo,
            'limiｔe'.$sufijo,
        ];

        foreach ($variantes as $variante) {
            $this->intentarLogin($variante, 'contrasena-equivocada');
        }

        $this->intentarLogin($usuario->email, 'secreto-de-pruebas');

        $this->assertGuest();
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
    }

    /** /review R5: un login correcto reinicia el contador de fallos de la cuenta. */
    public function test_un_login_correcto_reinicia_el_contador(): void
    {
        $usuario = $this->crearUsuario();

        $this->fallarLogin($usuario->email, 4);
        $this->intentarLogin($usuario->email, 'secreto-de-pruebas')->assertRedirect('/cliente');
        $this->post('/logout');

        $this->fallarLogin($usuario->email, 5);
        $this->assertSame('Credenciales incorrectas', $this->errorEmail());

        $this->intentarLogin($usuario->email, 'contrasena-equivocada');
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
    }

    /**
     * /review R6: con IPv6, un cliente suele controlar un /64 entero. Los límites
     * cuentan las direcciones de un mismo /64 como un solo origen.
     */
    public function test_las_ipv6_del_mismo_prefijo_comparten_el_limite(): void
    {
        // Alta: cinco direcciones del mismo /64 agotan el límite.
        foreach (['a', 'b', 'c', 'd', 'e'] as $interfaz) {
            $this->intentarAlta("2001:db8:1:2::{$interfaz}");
        }
        $this->intentarAlta('2001:db8:1:2::f');
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());

        // Desde otro /64 el alta sigue abierta.
        $otro = 'alta.'.Str::random(8).'@example.test';
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:1:3::1'])
            ->post('/registro', $this->altaValida($otro));
        $this->assertDatabaseHas('usuarios', ['email' => $otro]);
        $this->post('/logout');

        // Login: cinco fallos de la misma cuenta desde direcciones del mismo /64.
        $usuario = $this->crearUsuario();
        foreach (['1', '2', '3', '4', '5'] as $interfaz) {
            $this->intentarLogin($usuario->email, 'contrasena-equivocada', "2001:db8:9:9::{$interfaz}");
        }
        $this->intentarLogin($usuario->email, 'secreto-de-pruebas', '2001:db8:9:9::6');
        $this->assertGuest();
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
    }

    public function test_el_alta_admite_cinco_intentos_por_minuto_y_direccion(): void
    {
        // Contador común: tres en `/registro` y dos en `/registro/contacto`.
        for ($i = 0; $i < 3; $i++) {
            $this->intentarAlta();
        }
        for ($i = 0; $i < 2; $i++) {
            $this->from('/registro/contacto')->post('/registro/contacto', []);
        }

        $email = 'alta.'.Str::random(8).'@example.test';
        $respuesta = $this->from('/registro')->post('/registro', $this->altaValida($email));

        $respuesta->assertRedirect('/registro');
        $this->assertDatabaseMissing('usuarios', ['email' => $email]);
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());

        $anterior = session('_old_input', []);
        $this->assertSame($email, $anterior['email'] ?? null);
        $this->assertArrayNotHasKey('password', $anterior);
        $this->assertArrayNotHasKey('password_confirmation', $anterior);

        // Desde otra dirección el alta sigue abierta.
        $otro = 'alta.'.Str::random(8).'@example.test';
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->post('/registro', $this->altaValida($otro));
        $this->assertDatabaseHas('usuarios', ['email' => $otro]);
    }

    public function test_la_vuelta_de_google_admite_cinco_por_minuto(): void
    {
        $this->simularGoogle();

        for ($i = 0; $i < 5; $i++) {
            $this->volverDeGoogle()->assertRedirect(route('register.contacto'));
        }

        $this->volverDeGoogle()->assertRedirect(route('login'));
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
    }

    public function test_el_rechazo_por_limite_emite_el_evento_de_bloqueo(): void
    {
        Event::fake([Lockout::class]);

        // Alta: rechazada, pero no es un login.
        for ($i = 0; $i < 6; $i++) {
            $this->intentarAlta('10.0.1.1');
        }
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
        Event::assertNotDispatched(Lockout::class);

        // Login: el sexto intento se rechaza y emite el evento.
        $usuario = $this->crearUsuario();
        $this->fallarLogin($usuario->email, 6, '10.0.1.2');
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
        Event::assertDispatchedTimes(Lockout::class, 1);

        // Google: la sexta vuelta se rechaza y emite el evento.
        $this->simularGoogle();
        for ($i = 0; $i < 6; $i++) {
            $this->volverDeGoogle('10.0.1.3');
        }
        $this->assertMatchesRegularExpression(self::MENSAJE, (string) $this->errorEmail());
        Event::assertDispatchedTimes(Lockout::class, 2);
    }
}
