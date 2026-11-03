<?php

namespace Tests\Feature;

use App\Auditoria\AlmacenAuditoria;
use App\Mail\ContrasenaCambiada;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\TestCase;

/**
 * Spec 041, CA12 (TM9, plan D5): el restablecimiento queda en el registro de auditoría de la 020,
 * en modo crítico y en la misma transacción que el cambio. Si el registro no se puede escribir,
 * nada cambia (contraseña, versión de sesión y token) y no se envía el aviso.
 */
class RecuperarContrasenaAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const NUEVA = 'contrasena-nueva-segura';

    private function crearUsuario(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'auditoria.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('contrasena-anterior'),
        ]);
    }

    private function restablecer(Usuario $usuario, string $token)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.23'])->post('/contrasena/nueva', [
            'token' => $token,
            'cuenta' => $usuario->id,
            'password' => self::NUEVA,
            'password_confirmation' => self::NUEVA,
        ]);
    }

    public function test_el_restablecimiento_queda_registrado(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);
        $antes = now()->subSecond();

        $respuesta = $this->restablecer($usuario, $token)->assertRedirect(url('/login'));

        $eventos = DB::table('auditoria')->where('accion', 'contrasena_restablecida')->where('recurso_id', $usuario->id)->get();
        $this->assertCount(1, $eventos, 'Un evento por restablecimiento.');
        $evento = $eventos->first();
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame($usuario->id, $evento->actor_id, 'La cuenta es el actor (D2).');
        $this->assertSame('cliente', $evento->actor_rol);
        $this->assertSame('usuario', $evento->recurso_tipo);
        $this->assertSame($usuario->id, $evento->recurso_id, 'Y el sujeto.');
        $this->assertGreaterThanOrEqual($antes->format('Y-m-d H:i:s'), substr($evento->ocurrido_en, 0, 19), 'Con su fecha.');
        $this->assertSame('198.51.100.23', $evento->ip);
        $this->assertSame($respuesta->headers->get('X-Request-Id'), $evento->request_id, 'Con el request_id de la petición.');
        $this->assertSame(['password_hash'], json_decode($evento->campos, true), 'Solo el nombre del campo, sin valores.');

        $guardado = json_encode((array) $evento);
        foreach ([$token, self::NUEVA, $usuario->email] as $dato) {
            $this->assertStringNotContainsString($dato, $guardado, 'Sin la contraseña, el enlace ni el correo.');
        }
    }

    public function test_sin_registro_la_contrasena_no_cambia(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $usuario = $this->crearUsuario();
        $token = Password::broker()->createToken($usuario);

        $this->restablecer($usuario, $token)->assertStatus(503);

        $fresco = $usuario->fresh();
        $this->assertTrue(Hash::check('contrasena-anterior', $fresco->password_hash), 'La contraseña no cambió (D5).');
        $this->assertSame(0, (int) $fresco->version_sesion, 'La versión de sesión no subió.');
        $this->assertTrue(Password::broker()->tokenExists($fresco, $token), 'El token sigue: la transacción se deshizo entera.');
    }

    public function test_sin_registro_no_se_envia_el_aviso(): void
    {
        Mail::fake();
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $usuario = $this->crearUsuario();

        $this->restablecer($usuario, Password::broker()->createToken($usuario))->assertStatus(503);

        Mail::assertNotSent(ContrasenaCambiada::class);
    }
}
