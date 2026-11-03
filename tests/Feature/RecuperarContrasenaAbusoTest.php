<?php

namespace Tests\Feature;

use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\Support\CapturaLogs;
use Tests\TestCase;

/**
 * Spec 041: abusos del flujo de recuperar la contraseña.
 *
 *  - Enumeración (CA8, TM1): la misma respuesta y el mismo tiempo con cuenta activa, desactivada
 *    o sin cuenta; el trabajo que depende de la cuenta va después de responder (plan D6).
 *  - Límites (CA9, TM2, plan D15): un correo por cuenta y minuto (el del broker) y 5 envíos por
 *    minuto por origen al pedir y al fijar; todo `POST` cuenta, válido o no; abrir el enlace no
 *    gasta; al pasar el límite, la pantalla del formulario en espera, sin bucles ni errores con
 *    entradas malformadas (/analyze A24, A25, A34).
 *  - Logs (TM6, P8): ningún registro lleva el correo, el token ni la contraseña.
 *  - Enlaces ajenos o alterados (CA10, TM3, TM4) y cuentas desactivadas (CA11, TM5): se rechazan
 *    como un enlace caducado y la contraseña no cambia.
 *
 * Los limitadores guardan su cuenta en el caché (`array` en los tests): `limpiarLimitadores()` lo
 * vacía entre casos, porque su clave es `md5(nombre.origen)` y no el nombre del limitador.
 */
class RecuperarContrasenaAbusoTest extends TestCase
{
    use DatabaseTransactions;

    private const SOLICITUD = '/contrasena/olvidada';

    private const ENVIADA = '/contrasena/enviada';

    private const RESTABLECER = '/contrasena/nueva';

    private function limpiarLimitadores(): void
    {
        Cache::flush();
    }

    private function crearUsuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'abuso.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
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

    private function enlace(Usuario $usuario, string $token): string
    {
        return self::RESTABLECER.'/'.$token.'?cuenta='.$usuario->id;
    }

    private function pedir(string $correo, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(self::SOLICITUD, ['email' => $correo]);
    }

    private function fijar(array $datos, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(self::RESTABLECER, $datos);
    }

    /** La pantalla en espera (CA9): aviso «Demasiados intentos.» y el botón desactivado. */
    private function assertEnEspera(string $html): DOMXPath
    {
        $xpath = $this->xpath($html);
        $aviso = $xpath->query('//*[@role="alert"]')->item(0);
        $this->assertNotNull($aviso, 'Aviso de espera con role="alert".');
        $this->assertStringContainsString('Demasiados intentos.', $aviso->textContent);
        $this->assertSame(1, $xpath->query('//button[@type="submit"][@disabled]')->length, 'El botón queda desactivado.');

        return $xpath;
    }

    public function test_misma_respuesta_con_cuenta_activa_desactivada_o_sin_cuenta(): void
    {
        Mail::fake();
        $this->freezeTime();
        $activa = $this->crearUsuario();
        $desactivada = $this->crearUsuario(['activo' => 0]);
        $sinCuenta = 'nadie.'.Str::lower(Str::random(10)).'@example.test';

        $respuestas = [];
        foreach ([$activa->email, $desactivada->email, $sinCuenta] as $correo) {
            $this->limpiarLimitadores();
            $respuesta = $this->pedir($correo);
            $pantalla = $this->get(self::ENVIADA)->assertOk()->getContent();
            $respuestas[] = [
                'codigo' => $respuesta->getStatusCode(),
                'destino' => $respuesta->headers->get('Location'),
                // La pantalla repite el correo escrito; sin él, debe ser idéntica.
                'pantalla' => preg_replace('/name="_token" value="[^"]*"/', '', str_replace($correo, 'CORREO', $pantalla)),
            ];
        }

        $this->assertSame(302, $respuestas[0]['codigo']);
        $this->assertSame(url(self::ENVIADA), $respuestas[0]['destino']);
        $this->assertSame($respuestas[0], $respuestas[1], 'Desactivada: la misma respuesta que la activa (CA8).');
        $this->assertSame($respuestas[0], $respuestas[2], 'Sin cuenta: la misma respuesta que la activa (CA8).');

        Mail::assertSent(EnlaceRestablecimiento::class, 1);
        Mail::assertSent(EnlaceRestablecimiento::class, fn ($correo) => $correo->hasTo($activa->email));
    }

    public function test_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta(): void
    {
        Mail::fake();
        $activa = $this->crearUsuario();
        $sinCuenta = 'nadie.'.Str::lower(Str::random(10)).'@example.test';
        $kernel = $this->app->make(Kernel::class);
        $tiempos = ['activa' => [], 'sin' => []];

        // Kernel `handle` sin `terminate` (plan D6): lo que corre tras responder no cuenta, como en
        // PHP-FPM con fastcgi_finish_request. 20 solicitudes alternas, todas por debajo del límite.
        for ($i = 0; $i < 20; $i++) {
            $this->limpiarLimitadores();
            [$clave, $correo] = $i % 2 === 0 ? ['activa', $activa->email] : ['sin', $sinCuenta];
            $inicio = hrtime(true);
            $respuesta = $kernel->handle(Request::create(self::SOLICITUD, 'POST', ['email' => $correo]));
            $tiempos[$clave][] = (hrtime(true) - $inicio) / 1e6;

            $this->assertSame(302, $respuesta->getStatusCode());
            $this->assertSame(url(self::ENVIADA), $respuesta->headers->get('Location'));
        }

        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $activa->email)->count(),
            'Antes de terminar la petición, la cuenta activa aún no tiene token: el trabajo va tras responder.');
        $media = fn (array $valores) => array_sum($valores) / count($valores);
        $this->assertLessThan(300, abs($media($tiempos['activa']) - $media($tiempos['sin'])), 'Diferencia de medias menor de 300 ms (RNF).');
    }

    public function test_un_correo_por_cuenta_por_minuto(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        $this->pedir($usuario->email)->assertRedirect(self::ENVIADA);
        $this->limpiarLimitadores();
        // El segundo pedido dentro del minuto: el mismo mensaje genérico y ningún correo más (CA9).
        $this->pedir($usuario->email)->assertRedirect(self::ENVIADA);
        Mail::assertSent(EnlaceRestablecimiento::class, 1);

        $this->travel(61)->seconds();
        $this->limpiarLimitadores();
        $this->pedir($usuario->email)->assertRedirect(self::ENVIADA);
        Mail::assertSent(EnlaceRestablecimiento::class, 2);
    }

    public function test_cinco_solicitudes_por_minuto_por_origen(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->pedir("origen{$i}.".Str::lower(Str::random(6)).'@example.test')->assertRedirect(self::ENVIADA);
        }
        $sexto = 'sexto.'.Str::lower(Str::random(6)).'@example.test';
        $this->pedir($sexto)->assertRedirect(self::SOLICITUD);

        $xpath = $this->assertEnEspera($this->get(self::SOLICITUD)->assertOk()->getContent());
        $this->assertSame($sexto, $xpath->query('//input[@name="email"]')->item(0)?->getAttribute('value'), 'Conserva el correo escrito.');
        // Otro origen no está limitado.
        $this->pedir($sexto, '10.0.0.9')->assertRedirect(self::ENVIADA);
    }

    public function test_cinco_intentos_de_fijar_por_minuto_por_origen(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        $datos = ['token' => $token, 'cuenta' => $usuario->id, 'password' => 'corta', 'password_confirmation' => 'corta'];

        // Abrir el enlace no gasta intentos (D15).
        for ($i = 0; $i < 6; $i++) {
            $this->get($this->enlace($usuario, $token))->assertOk();
        }
        // Cuentan todos los POST, válidos o no: 5 permitidos, con su error de campo.
        for ($i = 0; $i < 5; $i++) {
            $this->fijar($datos)->assertRedirect(url($this->enlace($usuario, $token)))->assertSessionHasErrors('password');
        }
        // El sexto ve la espera, con el token y la cuenta del formulario y nunca la contraseña.
        $sexto = $this->fijar(array_merge($datos, ['password' => 'contrasena-valida-1', 'password_confirmation' => 'contrasena-valida-1']));
        $sexto->assertRedirect(url($this->enlace($usuario, $token)));
        $this->assertStringNotContainsString('contrasena-valida-1', json_encode(session()->all()), 'La contraseña no queda en la sesión.');
        $this->assertTrue(Hash::check('contrasena-anterior', $usuario->fresh()->password_hash), 'La contraseña no cambió.');
        // Otro origen no está limitado.
        $this->fijar($datos, '10.0.0.9')->assertSessionHasErrors('password');
    }

    public function test_la_espera_al_fijar_no_redirige_en_bucle(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        $datos = ['token' => $token, 'cuenta' => $usuario->id, 'password' => 'corta', 'password_confirmation' => 'corta'];
        for ($i = 0; $i < 5; $i++) {
            $this->fijar($datos);
        }

        $this->fijar($datos)->assertRedirect(url($this->enlace($usuario, $token)));
        // El destino responde 200 en estado de espera: no vuelve a redirigir.
        $xpath = $this->assertEnEspera($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertSame('Elige tu contraseña nueva', trim($xpath->query('//h1')->item(0)?->textContent ?? ''));
    }

    public function test_los_errores_de_campo_gastan_intentos(): void
    {
        Mail::fake();

        // Al pedir: cinco envíos con el correo mal escrito y el sexto, bien escrito, ve la espera.
        for ($i = 0; $i < 5; $i++) {
            $this->from(self::SOLICITUD)->pedir('no-es-un-correo')->assertSessionHasErrors('email');
        }
        $this->pedir($this->crearUsuario()->email)->assertRedirect(self::SOLICITUD);
        $this->assertEnEspera($this->get(self::SOLICITUD)->getContent());
        Mail::assertNothingSent();
    }

    public function test_la_espera_con_entrada_malformada_no_falla(): void
    {
        // Al fijar: pasado el límite, una entrada sin formato válido lleva a «pedir» en espera, sin error.
        $usuario = $this->crearUsuario();
        for ($i = 0; $i < 5; $i++) {
            $this->fijar(['token' => 'x', 'cuenta' => $usuario->id, 'password' => 'corta', 'password_confirmation' => 'corta']);
        }
        foreach ([
            ['token' => ['a', 'b'], 'cuenta' => $usuario->id],
            ['token' => str_repeat('a', 5000), 'cuenta' => $usuario->id],
            ['token' => '<script>"x"</script>', 'cuenta' => $usuario->id],
            ['token' => 'abc', 'cuenta' => 'no-es-un-uuid'],
            ['token' => 'abc', 'cuenta' => ['x']],
        ] as $datos) {
            $this->fijar($datos)->assertRedirect(self::SOLICITUD);
            $this->assertEnEspera($this->get(self::SOLICITUD)->assertOk()->getContent());
        }

        // Al pedir (A34): pasado el límite, el correo solo se conserva si es texto de hasta 255.
        $this->limpiarLimitadores();
        for ($i = 0; $i < 5; $i++) {
            $this->pedir('no-es-un-correo');
        }
        foreach ([['email' => ['a@b.test']], ['email' => str_repeat('a', 300).'@example.test']] as $datos) {
            $this->post(self::SOLICITUD, $datos)->assertRedirect(self::SOLICITUD);
            $xpath = $this->assertEnEspera($this->get(self::SOLICITUD)->assertOk()->getContent());
            $this->assertSame('', (string) $xpath->query('//input[@name="email"]')->item(0)?->getAttribute('value'), 'No conserva un correo malformado.');
        }
    }

    public function test_ningun_log_contiene_el_correo_el_token_ni_la_contrasena(): void
    {
        $logs = CapturaLogs::instalar();
        $usuario = $this->crearUsuario();

        $this->pedir($usuario->email)->assertRedirect(self::ENVIADA);
        $token = Password::broker()->createToken($usuario);
        $this->limpiarLimitadores();
        $this->fijar(['token' => $token, 'cuenta' => $usuario->id, 'password' => 'Zx9qw7k', 'password_confirmation' => 'Zx9qw7k']);
        $this->limpiarLimitadores();
        $this->fijar(['token' => $token, 'cuenta' => $usuario->id, 'password' => 'contrasena-nueva-segura', 'password_confirmation' => 'contrasena-nueva-segura'])
            ->assertRedirect(url('/login'));

        $texto = $logs->comoTexto();
        foreach ([$usuario->email, $token, 'contrasena-nueva-segura', 'Zx9qw7k'] as $dato) {
            $this->assertStringNotContainsString($dato, $texto, 'Ningún log lleva el correo, el token ni la contraseña (TM6).');
        }
    }

    /** Envía el formulario con ese token y esa cuenta y devuelve la pantalla final. */
    private function intentarFijar(string $token, string $cuenta): string
    {
        $this->limpiarLimitadores();

        return $this->followingRedirects()->post(self::RESTABLECER, [
            'token' => $token,
            'cuenta' => $cuenta,
            'password' => 'contrasena-del-atacante',
            'password_confirmation' => 'contrasena-del-atacante',
        ])->getContent();
    }

    private function assertEnlaceYaNoSirve(string $html): void
    {
        $xpath = $this->xpath($html);
        $this->assertSame('Este enlace ya no sirve', trim($xpath->query('//h1')->item(0)?->textContent ?? ''));
    }

    private function assertSinCambios(Usuario $usuario): void
    {
        $this->assertTrue(Hash::check('contrasena-anterior', (string) $usuario->fresh()->password_hash), 'La contraseña no cambió.');
        $this->assertFalse(Hash::check('contrasena-del-atacante', (string) $usuario->fresh()->password_hash));
    }

    public function test_rechaza_un_token_alterado(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);

        foreach ([substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a'), strrev($token), Str::random(64)] as $alterado) {
            $this->assertEnlaceYaNoSirve($this->get($this->enlace($usuario, $alterado))->assertOk()->getContent());
            $this->assertEnlaceYaNoSirve($this->intentarFijar($alterado, $usuario->id));
        }
        $this->assertSinCambios($usuario);
    }

    public function test_el_token_de_una_cuenta_no_cambia_la_contrasena_de_otra(): void
    {
        $atacante = $this->crearUsuario();
        $victima = $this->crearUsuario();
        $tokenDelAtacante = Password::broker()->createToken($atacante);

        // TM4: el token de A con la cuenta de B no sirve, ni para B ni para A.
        $this->assertEnlaceYaNoSirve($this->get($this->enlace($victima, $tokenDelAtacante))->assertOk()->getContent());
        $this->assertEnlaceYaNoSirve($this->intentarFijar($tokenDelAtacante, $victima->id));
        $this->assertSinCambios($victima);
        $this->assertSinCambios($atacante);
    }

    public function test_una_cuenta_desactivada_no_recibe_correo(): void
    {
        Mail::fake();
        $desactivada = $this->crearUsuario(['activo' => 0]);

        $this->pedir($desactivada->email)->assertRedirect(self::ENVIADA);
        $this->get(self::ENVIADA)->assertOk()->assertSee('Revisa tu correo');

        Mail::assertNothingSent();
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $desactivada->email)->count(), 'Ni se crea un token.');
    }

    public function test_una_cuenta_desactivada_no_puede_restablecer(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        // El enlace se pidió antes de desactivar la cuenta.
        $usuario->forceFill(['activo' => 0])->save();

        $this->assertEnlaceYaNoSirve($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertEnlaceYaNoSirve($this->intentarFijar($token, $usuario->id));
        $this->assertSinCambios($usuario);

        // Y sigue sin poder entrar (CA11).
        $this->post('/login', ['email' => $usuario->email, 'password' => 'contrasena-anterior']);
        $this->assertGuest();
    }
}
