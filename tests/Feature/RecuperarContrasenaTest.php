<?php

namespace Tests\Feature;

use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 041: recuperar la contraseña por correo.
 *
 *  - Pedir el enlace (T010): el login enlaza a «¿Olvidaste tu contraseña?» (CA1), la pantalla
 *    tiene su vuelta al login, una cuenta activa de cualquier rol recibe el correo y la pantalla
 *    responde «Revisa tu correo» (CA2), el correo escrito se valida y no se queda en la sesión
 *    (TM14), y con el interruptor apagado no hay rutas ni enlace (plan D8).
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

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('solicitar-enlace');
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
