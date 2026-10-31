<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use Tests\TestCase;

/**
 * Spec 032, CA2 (TM5, TM8): cada inicio de sesión fallido o bloqueado deja una
 * línea en el canal `seguridad`, que el vigilante cuenta para avisar de las
 * ráfagas. La línea no lleva la IP, el correo ni el id: solo huellas HMAC del
 * origen y de la cuenta, que son las mismas que agrupa el limitador (plan D7).
 *
 * El canal escribe en una carpeta temporal de cada test.
 */
class RegistroFallosAccesoTest extends TestCase
{
    use DatabaseTransactions;

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carpeta = sys_get_temp_dir().'/seguridad-'.Str::random(10);
        File::ensureDirectoryExists($this->carpeta);
        config(['logging.channels.seguridad.path' => $this->carpeta.'/seguridad.log']);
        Log::forgetChannel('seguridad');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);

        parent::tearDown();
    }

    private function crearUsuario(int $activo = 1): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'fallos.'.Str::random(8).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => $activo,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    private function intentarLogin(string $email, string $password, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/login')
            ->post('/login', ['email' => $email, 'password' => $password]);
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

    /** Texto de todos los archivos del canal. */
    private function textoDelCanal(): string
    {
        return implode('', array_map('file_get_contents', glob($this->carpeta.'/seguridad-*.log') ?: []));
    }

    /** @return list<array<string, mixed>> */
    private function lineas(): array
    {
        $texto = trim($this->textoDelCanal());

        return $texto === '' ? [] : array_map(
            fn (string $linea) => json_decode($linea, true, flags: JSON_THROW_ON_ERROR),
            explode("\n", $texto),
        );
    }

    /** @return array<string, mixed> */
    private function unicaLinea(): array
    {
        $lineas = $this->lineas();
        $this->assertCount(1, $lineas, 'se esperaba exactamente una línea en el canal seguridad');

        return $lineas[0];
    }

    public function test_la_contrasena_equivocada_contra_una_cuenta_escribe_un_fallo(): void
    {
        $usuario = $this->crearUsuario();

        $this->intentarLogin($usuario->email, 'contrasena-equivocada');

        $contexto = $this->unicaLinea()['context'];
        $this->assertSame('fallido', $contexto['tipo']);
        $this->assertSame('contrasena', $contexto['metodo']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $contexto['origen']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $contexto['cuenta']);
    }

    public function test_un_correo_sin_cuenta_escribe_un_fallo(): void
    {
        $this->intentarLogin('nadie.'.Str::random(8).'@example.test', 'contrasena-equivocada');

        $contexto = $this->unicaLinea()['context'];
        $this->assertSame('fallido', $contexto['tipo']);
        $this->assertSame('contrasena', $contexto['metodo']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $contexto['cuenta']);
    }

    public function test_la_vuelta_de_google_fallida_escribe_un_fallo_sin_cuenta(): void
    {
        $this->get('/auth/google/callback?error=access_denied&state=cualquiera')
            ->assertRedirect(route('login'));

        $contexto = $this->unicaLinea()['context'];
        $this->assertSame('fallido', $contexto['tipo']);
        $this->assertSame('google', $contexto['metodo']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $contexto['origen']);
        $this->assertNull($contexto['cuenta']);
    }

    public function test_el_bloqueo_del_login_tiene_la_huella_de_sus_fallos(): void
    {
        $usuario = $this->crearUsuario();

        for ($i = 0; $i < 6; $i++) {
            $this->intentarLogin($usuario->email, 'contrasena-equivocada', '10.0.2.1');
        }

        $lineas = $this->lineas();
        $this->assertCount(6, $lineas);
        $fallo = $lineas[0]['context'];
        $bloqueo = $lineas[5]['context'];

        $this->assertSame('fallido', $fallo['tipo']);
        $this->assertSame('bloqueado', $bloqueo['tipo']);
        $this->assertSame('contrasena', $bloqueo['metodo']);
        $this->assertSame($fallo['origen'], $bloqueo['origen']);
        $this->assertSame($fallo['cuenta'], $bloqueo['cuenta']);
    }

    /** /analyze A33: el bloqueo de la vuelta de Google no trae correo. */
    public function test_el_bloqueo_de_la_vuelta_de_google_escribe_sin_cuenta(): void
    {
        $this->simularGoogle();

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.2'])
                ->get('/auth/google/callback')
                ->assertRedirect(route('register.contacto'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.2'])
            ->get('/auth/google/callback')
            ->assertRedirect(route('login'));

        $contexto = $this->unicaLinea()['context'];
        $this->assertSame('bloqueado', $contexto['tipo']);
        $this->assertSame('google', $contexto['metodo']);
        $this->assertNull($contexto['cuenta']);
    }

    public function test_un_login_correcto_no_escribe_nada(): void
    {
        $usuario = $this->crearUsuario();

        $this->intentarLogin($usuario->email, 'secreto-de-pruebas')->assertRedirect('/cliente');

        $this->assertSame([], $this->lineas());
    }

    public function test_el_registro_no_contiene_ip_ni_correo(): void
    {
        $usuario = $this->crearUsuario();

        $this->intentarLogin($usuario->email, 'contrasena-equivocada', '203.0.113.77');
        $this->intentarLogin('nadie.'.Str::random(8).'@example.test', 'contrasena-equivocada', '2001:db8:7:7::1');

        $texto = $this->textoDelCanal();
        $this->assertNotSame('', $texto);
        $this->assertStringNotContainsString('203.0.113.77', $texto);
        $this->assertStringNotContainsString('2001:db8', $texto);
        $this->assertStringNotContainsString('@example.test', $texto);
        $this->assertStringNotContainsString(Str::before($usuario->email, '@'), $texto);
        $this->assertStringNotContainsString('cuenta:', $texto);
        $this->assertStringNotContainsString('correo:', $texto);
    }

    public function test_el_mismo_origen_y_la_misma_cuenta_dan_la_misma_huella(): void
    {
        $usuario = $this->crearUsuario();
        $otro = $this->crearUsuario();

        $this->intentarLogin($usuario->email, 'contrasena-equivocada', '10.0.3.1');
        $this->intentarLogin($usuario->email, 'contrasena-equivocada', '10.0.3.1');
        $this->intentarLogin($otro->email, 'contrasena-equivocada', '10.0.3.2');

        $lineas = $this->lineas();
        $this->assertCount(3, $lineas);
        [$primera, $segunda, $tercera] = array_column($lineas, 'context');

        $this->assertSame($primera['origen'], $segunda['origen']);
        $this->assertSame($primera['cuenta'], $segunda['cuenta']);
        $this->assertNotSame($primera['origen'], $tercera['origen']);
        $this->assertNotSame($primera['cuenta'], $tercera['cuenta']);
    }

    /**
     * /analyze A12: una cuenta inactiva da la huella de su cuenta, como el
     * limitador. Las variantes de ancho completo de su correo entran en la misma
     * cuenta por la colación de la base, así que comparten huella; si contara el
     * correo en minúsculas, la variante daría otra.
     */
    public function test_una_cuenta_inactiva_da_la_huella_de_su_cuenta(): void
    {
        $usuario = $this->crearUsuario(activo: 0);
        $variante = 'ｆ'.substr($usuario->email, 1);

        $this->intentarLogin($usuario->email, 'secreto-de-pruebas');
        $this->intentarLogin($variante, 'secreto-de-pruebas');

        $lineas = $this->lineas();
        $this->assertCount(2, $lineas);
        [$primera, $segunda] = array_column($lineas, 'context');

        $this->assertSame('fallido', $primera['tipo']);
        $this->assertSame($primera['cuenta'], $segunda['cuenta']);
    }

    /** /analyze A11 y A45: la forma de la línea es la del contrato del plan. */
    public function test_la_linea_tiene_la_forma_del_contrato(): void
    {
        $this->intentarLogin('nadie.'.Str::random(8).'@example.test', 'contrasena-equivocada');

        $linea = $this->unicaLinea();

        $this->assertSame('acceso_fallido', $linea['message']);
        $this->assertSame(['tipo', 'metodo', 'origen', 'cuenta'], array_keys($linea['context']));
        $this->assertSame('INFO', $linea['level_name']);
        $this->assertSame('seguridad', $linea['channel']);
        $this->assertArrayHasKey('datetime', $linea);
        $this->assertArrayHasKey('extra', $linea);
    }
}
