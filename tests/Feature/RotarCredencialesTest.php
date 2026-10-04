<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 030, CA5 (TM5, RS6.c): `dentissa:rotar-credenciales --confirmar` deja
 * sin valor las contraseñas del volcado que figuran en el README.
 *
 *  - Cada cuenta con contraseña recibe una aleatoria de 20 caracteres, que se
 *    muestra una sola vez por la salida estándar (tabla correo → contraseña).
 *  - Las cuentas solo de Google (sin contraseña) no cambian.
 *  - Sin `--confirmar` no cambia nada y termina con código 1.
 *  - Ninguna contraseña nueva llega al log (P8).
 */
class RotarCredencialesTest extends TestCase
{
    use DatabaseTransactions;

    private function crearUsuario(?string $password, array $valores = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'Del Volcado',
            'email' => 'volcado.'.Str::random(8).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => $password === null ? null : Hash::make($password),
        ], $valores));
    }

    /** @return array{0: int, 1: string} código de salida y salida del comando */
    private function rotar(bool $confirmar = true): array
    {
        $codigo = Artisan::call('dentissa:rotar-credenciales', $confirmar ? ['--confirmar' => true] : []);

        return [$codigo, Artisan::output()];
    }

    /** Contraseña nueva que la tabla de salida muestra para un correo. */
    private function contrasenaMostrada(string $salida, string $email): ?string
    {
        $patron = '/\|\s*'.preg_quote($email, '/').'\s*\|\s*([A-Za-z0-9]{20})\s*\|/';

        return preg_match($patron, $salida, $m) ? $m[1] : null;
    }

    public function test_con_confirmar_la_contrasena_anterior_deja_de_valer_y_la_mostrada_si(): void
    {
        $usuario = $this->crearUsuario('contrasena-del-readme');

        [$codigo, $salida] = $this->rotar();

        $this->assertSame(0, $codigo);
        $nueva = $this->contrasenaMostrada($salida, $usuario->email);
        $this->assertNotNull($nueva, 'La salida no muestra una contraseña de 20 caracteres para la cuenta.');
        $this->assertFalse(Auth::validate(['email' => $usuario->email, 'password' => 'contrasena-del-readme']));
        $this->assertTrue(Auth::validate(['email' => $usuario->email, 'password' => $nueva]));
    }

    public function test_una_cuenta_solo_de_google_no_cambia(): void
    {
        $google = $this->crearUsuario(null, ['google_id' => 'google-'.Str::random(10)]);
        $conContrasena = $this->crearUsuario('contrasena-del-readme');

        [$codigo, $salida] = $this->rotar();

        $this->assertSame(0, $codigo);
        $this->assertNull($google->fresh()->password_hash);
        $this->assertStringNotContainsString($google->email, $salida);
        // El comando sí actuó sobre las demás cuentas.
        $this->assertNotNull($this->contrasenaMostrada($salida, $conContrasena->email));
    }

    public function test_sin_confirmar_no_cambia_nada_y_devuelve_1(): void
    {
        $usuario = $this->crearUsuario('contrasena-del-readme');
        $hashAnterior = $usuario->password_hash;

        [$codigo] = $this->rotar(confirmar: false);

        $this->assertSame(1, $codigo);
        $this->assertSame($hashAnterior, $usuario->fresh()->password_hash);
        $this->assertTrue(Auth::validate(['email' => $usuario->email, 'password' => 'contrasena-del-readme']));
    }

    public function test_no_escribe_las_contrasenas_en_el_log(): void
    {
        $registrado = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registrado) {
            $registrado[] = $evento->message.' '.json_encode($evento->context);
        });

        $usuario = $this->crearUsuario('contrasena-del-readme');

        [$codigo, $salida] = $this->rotar();

        $this->assertSame(0, $codigo);
        $nueva = $this->contrasenaMostrada($salida, $usuario->email);
        $this->assertNotNull($nueva);
        foreach ($registrado as $linea) {
            $this->assertStringNotContainsString($nueva, $linea, 'Una contraseña nueva llegó al log.');
        }
    }
}
