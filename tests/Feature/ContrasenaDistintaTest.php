<?php

namespace Tests\Feature;

use App\Mail\ContrasenaCambiada;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 043: la contraseña nueva tiene que ser distinta de la actual, al restablecerla por el enlace
 * del correo (CA1, CA3–CA5, CA7, TM3, TM4) y al cambiarla desde el perfil (CA2–CA6, CA8, TM1, TM4,
 * TM5).
 *
 * Datos de prueba: la actual va en minúsculas y sin espacios en los extremos (`TrimStrings` recorta
 * `password_actual`, /analyze A8); las diferencias mínimas de CA3 cambian una letra a mayúscula o
 * añaden un espacio final, con caracteres simples (bcrypt distingue 72 bytes, /analyze A13).
 */
class ContrasenaDistintaTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE = 'Elige una contraseña distinta de la actual';

    private const ACTUAL = 'contrasena-actual-larga';

    private const RESTABLECER = '/contrasena/nueva';

    protected function setUp(): void
    {
        parent::setUp();
        // Los limitadores guardan su cuenta en el caché (`array` en los tests).
        Cache::flush();
    }

    private function crearUsuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'distinta.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make(self::ACTUAL),
        ], $datos));
    }

    private function enlace(Usuario $usuario, string $token): string
    {
        return self::RESTABLECER.'/'.$token.'?cuenta='.$usuario->id;
    }

    private function token(Usuario $usuario): string
    {
        return Password::broker()->createToken($usuario);
    }

    private function restablecer(Usuario $usuario, string $token, string $nueva, ?string $confirmacion = null, ?string $cuenta = null)
    {
        return $this->post(self::RESTABLECER, [
            'token' => $token,
            'cuenta' => $cuenta ?? $usuario->id,
            'password' => $nueva,
            'password_confirmation' => $confirmacion ?? $nueva,
        ]);
    }

    private function assertContrasena(Usuario $usuario, string $esperada, string $mensaje = 'La contraseña no cambió.'): void
    {
        $this->assertTrue(Hash::check($esperada, (string) $usuario->fresh()->password_hash), $mensaje);
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

    /** La sesión no guarda la entrada del formulario ni la contraseña escrita (TM4). */
    private function assertSinContrasenaEnLaSesion(string $contrasena): void
    {
        $this->assertArrayNotHasKey('_old_input', session()->all(), 'La respuesta no conserva la entrada.');
        $this->assertStringNotContainsString($contrasena, json_encode(session()->all()), 'La contraseña no queda en la sesión.');
    }

    // ── Recuperación por el enlace del correo ────────────────────────────────────────────────────

    public function test_restablecer_con_la_actual_se_rechaza_junto_al_campo(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $this->restablecer($usuario, $token, self::ACTUAL)
            ->assertRedirect(url($this->enlace($usuario, $token)))
            ->assertSessionHasErrors(['password' => self::MENSAJE]);
        $this->assertContrasena($usuario, self::ACTUAL);

        $xpath = $this->xpath($this->get($this->enlace($usuario, $token))->assertOk()->getContent());
        $this->assertSame(self::MENSAJE, $this->texto($xpath, '//*[@id="clave-error"]'), 'El mensaje sale junto al campo de la contraseña nueva.');
        $clave = $xpath->query('//input[@id="clave"]')->item(0);
        $this->assertTrue($clave->hasAttribute('autofocus'), 'El foco va a ese campo.');
        $this->assertSame('true', $clave->getAttribute('aria-invalid'));
        $this->assertStringContainsString('clave-error', $clave->getAttribute('aria-describedby'), 'El error queda asociado al campo.');
    }

    public function test_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        $this->restablecer($usuario, $this->token($usuario), self::ACTUAL)->assertSessionHasErrors('password');

        $this->assertSame(0, (int) $usuario->fresh()->version_sesion, 'No se cierra ninguna sesión.');
        Mail::assertNotSent(ContrasenaCambiada::class);
        $this->assertSame(0, DB::table('auditoria')->where('accion', 'contrasena_restablecida')->where('recurso_id', $usuario->id)->count(), 'No se registra un restablecimiento.');
    }

    public function test_tras_el_rechazo_el_enlace_sigue_sirviendo(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        $this->restablecer($usuario, $token, self::ACTUAL)->assertSessionHasErrors(['password' => self::MENSAJE]);
        $this->get($this->enlace($usuario, $token))->assertOk();

        $this->restablecer($usuario, $token, 'otra-contrasena-nueva')->assertRedirect(url('/login'));
        $this->assertContrasena($usuario, 'otra-contrasena-nueva', 'El mismo enlace fija después otra contraseña.');
    }

    public function test_una_diferencia_minima_se_acepta_en_la_recuperacion(): void
    {
        foreach (['Contrasena-actual-larga', self::ACTUAL.' '] as $nueva) {
            Cache::flush();
            $usuario = $this->crearUsuario();

            $this->restablecer($usuario, $this->token($usuario), $nueva)->assertRedirect(url('/login'))->assertSessionHasNoErrors();
            $this->assertContrasena($usuario, $nueva, "«{$nueva}» se acepta como distinta (CA3).");
        }
    }

    public function test_una_cuenta_sin_contrasena_fija_la_suya_por_el_enlace(): void
    {
        $usuario = $this->crearUsuario(['password_hash' => null, 'google_id' => 'google-'.Str::random(12)]);

        $this->restablecer($usuario, $this->token($usuario), self::ACTUAL)->assertRedirect(url('/login'))->assertSessionHasNoErrors();
        $this->assertContrasena($usuario, self::ACTUAL, 'La cuenta sin contraseña fija la suya (CA4).');
    }

    public function test_los_errores_de_siempre_van_antes_en_la_recuperacion(): void
    {
        // Una contraseña heredada de menos de 8 caracteres, repetida: el error es el de longitud.
        $heredada = $this->crearUsuario(['password_hash' => Hash::make('corta77')]);
        $this->restablecer($heredada, $this->token($heredada), 'corta77')
            ->assertSessionHasErrors(['password' => 'Tiene que tener al menos 8 caracteres.']);

        // La actual con una confirmación distinta: solo el error de la confirmación.
        Cache::flush();
        $this->flushSession();
        $usuario = $this->crearUsuario();
        $this->restablecer($usuario, $this->token($usuario), self::ACTUAL, 'otra-confirmacion')
            ->assertSessionHasErrors(['password_confirmation' => 'Las dos contraseñas no coinciden.'])
            ->assertSessionDoesntHaveErrors('password');
        $this->assertContrasena($usuario, self::ACTUAL);
    }

    public function test_los_rechazos_cuentan_para_el_limite_del_enlace(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->token($usuario);

        // Cinco intentos con la actual, rechazados por «distinta».
        for ($i = 0; $i < 5; $i++) {
            $this->restablecer($usuario, $token, self::ACTUAL)->assertSessionHasErrors(['password' => self::MENSAJE]);
        }
        // El sexto, aunque sea válido, pide esperar y no cambia nada (CA7).
        $this->restablecer($usuario, $token, 'otra-contrasena-nueva')
            ->assertRedirect(url($this->enlace($usuario, $token)))
            ->assertSessionHas('espera', true);
        $this->assertContrasena($usuario, self::ACTUAL);
    }

    public function test_con_un_enlace_de_otra_cuenta_nunca_se_compara(): void
    {
        $a = $this->crearUsuario();
        $b = $this->crearUsuario(['password_hash' => Hash::make('contrasena-de-la-otra')]);

        // El token de A con la cuenta de B y la contraseña de B como nueva.
        $respuesta = $this->restablecer($a, $this->token($a), 'contrasena-de-la-otra', null, $b->id);

        $respuesta->assertOk()->assertSessionHasNoErrors();
        $this->assertSame('Este enlace ya no sirve', $this->texto($this->xpath($respuesta->getContent()), '//h1'), 'El enlace se rechaza antes de comparar (TM3).');
        $this->assertStringNotContainsString(self::MENSAJE, $respuesta->getContent());
        $this->assertContrasena($a, self::ACTUAL);
        $this->assertContrasena($b, 'contrasena-de-la-otra');
    }

    public function test_la_contrasena_rechazada_no_queda_en_la_sesion(): void
    {
        $usuario = $this->crearUsuario();

        $this->restablecer($usuario, $this->token($usuario), self::ACTUAL)->assertSessionHasErrors(['password' => self::MENSAJE]);
        $this->assertSinContrasenaEnLaSesion(self::ACTUAL);
    }
}
