<?php

namespace Tests\Feature;

use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 041, CA13 (TM15, plan D21, RS9.b, /review R4): cambiar el correo del perfil pide la contraseña
 * actual. Sin ello, con una sesión ajena se podía dirigir el enlace de restablecimiento a otro buzón.
 */
class PerfilCambioCorreoTest extends TestCase
{
    use DatabaseTransactions;

    private const PERFIL = '/cliente/perfil';

    private const CONTRASENA = 'contrasena-actual-larga';

    private function crearUsuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'perfil.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make(self::CONTRASENA),
        ], $datos));
    }

    private function guardar(Usuario $usuario, array $datos)
    {
        return $this->actingAs($usuario)->from(self::PERFIL)->put(self::PERFIL, array_merge([
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $usuario->email,
        ], $datos));
    }

    public function test_sin_la_contrasena_el_correo_no_cambia(): void
    {
        $usuario = $this->crearUsuario();
        $original = $usuario->email;

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test', 'nombre' => 'Cambiado'])
            ->assertRedirect(self::PERFIL)
            ->assertSessionHasErrors('contrasena_correo');

        $this->assertSame($original, $usuario->fresh()->email, 'El correo no cambia.');
        $this->assertSame('Persona', $usuario->fresh()->nombre, 'No se guarda nada del formulario.');
    }

    public function test_con_una_contrasena_incorrecta_el_correo_no_cambia(): void
    {
        $usuario = $this->crearUsuario();
        $original = $usuario->email;

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test', 'contrasena_correo' => 'no-es-la-contrasena'])
            ->assertSessionHasErrors('contrasena_correo');

        $this->assertSame($original, $usuario->fresh()->email);
    }

    public function test_el_error_sale_junto_al_campo(): void
    {
        $usuario = $this->crearUsuario();

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test']);
        $html = $this->actingAs($usuario)->get(self::PERFIL)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="contrasena_correo"[^>]*aria-invalid="true"[^>]*>\s*<div[^>]*field-hint-error[^>]*>Escribe tu contraseña actual/s', $html);
    }

    public function test_con_la_contrasena_correcta_el_correo_cambia(): void
    {
        $usuario = $this->crearUsuario();

        $this->guardar($usuario, ['email' => 'nuevo.buzon@example.test', 'contrasena_correo' => self::CONTRASENA])
            ->assertSessionHasNoErrors();

        $this->assertSame('nuevo.buzon@example.test', $usuario->fresh()->email);
    }

    public function test_sin_cambiar_el_correo_no_la_pide(): void
    {
        $usuario = $this->crearUsuario();

        $this->guardar($usuario, ['nombre' => 'Cambiado'])->assertSessionHasNoErrors();

        $this->assertSame('Cambiado', $usuario->fresh()->nombre);
    }

    public function test_cambiar_solo_mayusculas_no_la_pide(): void
    {
        $usuario = $this->crearUsuario();

        $this->guardar($usuario, ['email' => Str::upper($usuario->email)])->assertSessionHasNoErrors();
    }

    public function test_una_cuenta_sin_contrasena_no_puede_cambiarlo(): void
    {
        $usuario = $this->crearUsuario(['password_hash' => null, 'google_id' => 'google-'.Str::random(10)]);
        $original = $usuario->email;

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test'])->assertSessionHasErrors('email');
        $this->assertSame($original, $usuario->fresh()->email);

        // El resto del perfil sigue funcionando.
        $this->guardar($usuario->fresh(), ['nombre' => 'Cambiado'])->assertSessionHasNoErrors();
        $this->assertSame('Cambiado', $usuario->fresh()->nombre);

        $html = $this->actingAs($usuario->fresh())->get(self::PERFIL)->assertOk()->getContent();
        $this->assertStringNotContainsString('name="contrasena_correo"', $html, 'Sin contraseña que escribir.');
        $this->assertStringContainsString('Para cambiar el correo, crea primero una contraseña', $html);
    }

    /** /review R33: el error ya dice lo mismo que la ayuda. */
    public function test_la_ayuda_no_se_repite_con_el_error(): void
    {
        $usuario = $this->crearUsuario(['password_hash' => null, 'google_id' => 'google-'.Str::random(10)]);

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test']);
        $html = $this->actingAs($usuario)->get(self::PERFIL)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Para cambiar el correo, crea primero una contraseña'));
    }

    /** /review R32 (D23): con una sesión ajena no se puede adivinar la contraseña a base de intentos. */
    public function test_cinco_fallos_por_minuto_al_cambiar_el_correo(): void
    {
        $usuario = $this->crearUsuario();
        $original = $usuario->email;
        $cambio = ['email' => 'nuevo.buzon@example.test'];

        for ($i = 0; $i < 5; $i++) {
            $this->guardar($usuario, $cambio + ['contrasena_correo' => 'no-es-la-contrasena'])->assertSessionHasErrors('contrasena_correo');
        }

        // Ni la correcta se comprueba: la respuesta no revela si acertó.
        $this->guardar($usuario, $cambio + ['contrasena_correo' => self::CONTRASENA])
            ->assertSessionHasErrors(['contrasena_correo' => 'Demasiados intentos. Espera un minuto y vuelve a intentarlo.']);
        $this->assertSame($original, $usuario->fresh()->email);

        $this->travel(61)->seconds();
        $this->guardar($usuario, $cambio + ['contrasena_correo' => self::CONTRASENA])->assertSessionHasNoErrors();
        $this->assertSame('nuevo.buzon@example.test', $usuario->fresh()->email);
    }

    public function test_el_limite_se_comparte_con_el_cambio_de_contrasena(): void
    {
        $usuario = $this->crearUsuario();
        $anterior = $usuario->password_hash;
        $datos = ['password' => 'contrasena-nueva-larga', 'password_confirmation' => 'contrasena-nueva-larga'];

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($usuario)->from(self::PERFIL)->put(self::PERFIL.'/password', $datos + ['password_actual' => 'no-es-la-contrasena'])
                ->assertSessionHasErrors('password_actual');
        }

        $this->actingAs($usuario)->from(self::PERFIL)->put(self::PERFIL.'/password', $datos + ['password_actual' => self::CONTRASENA])
            ->assertSessionHasErrors(['password_actual' => 'Demasiados intentos. Espera un minuto y vuelve a intentarlo.']);
        $this->assertSame($anterior, $usuario->fresh()->password_hash);
        $this->guardar($usuario, ['email' => 'nuevo.buzon@example.test', 'contrasena_correo' => self::CONTRASENA])->assertSessionHasErrors('contrasena_correo');
    }

    /** La cadena que motiva R4: el enlace no puede llegar al buzón de quien no conoce la contraseña. */
    public function test_con_una_sesion_ajena_el_enlace_no_llega_a_otro_buzon(): void
    {
        Mail::fake();
        Cache::flush();
        $usuario = $this->crearUsuario();
        $original = $usuario->email;

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test']);
        $this->post('/logout');
        $this->app['auth']->forgetGuards();
        $this->post('/contrasena/olvidada', ['email' => 'otro.buzon@example.test'])->assertRedirect('/contrasena/enviada');

        Mail::assertNotSent(EnlaceRestablecimiento::class, fn ($correo) => $correo->hasTo('otro.buzon@example.test'));
        $this->assertSame($original, $usuario->fresh()->email);
    }

    /** Laravel guarda la entrada al fallar la validación: la contraseña no puede quedar en la sesión. */
    public function test_la_contrasena_no_queda_en_la_sesion(): void
    {
        $usuario = $this->crearUsuario();

        $this->guardar($usuario, ['email' => 'otro.buzon@example.test', 'contrasena_correo' => 'no-es-la-contrasena']);
        $this->assertArrayNotHasKey('contrasena_correo', session()->getOldInput());

        // También si falla otra validación de Laravel, la que no pasa por el controlador.
        $this->guardar($usuario, ['email' => 'no-es-un-correo', 'contrasena_correo' => 'no-es-la-contrasena'])
            ->assertSessionHasErrors('email');
        $this->assertArrayNotHasKey('contrasena_correo', session()->getOldInput());
    }
}
