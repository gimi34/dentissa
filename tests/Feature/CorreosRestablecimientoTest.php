<?php

namespace Tests\Feature;

use App\Mail\ContrasenaCambiada;
use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Feature\Concerns\ReglasCss;
use Tests\Feature\Concerns\TokensAcceso;
use Tests\Support\CapturaLogs;
use Tests\TestCase;

/**
 * Spec 041: los dos correos del acceso.
 *
 *  - El del enlace (CA2): enlace armado desde `APP_URL`, nunca desde la petición (TM12), con la
 *    caducidad en hora local, el aviso de ignorarlo y la dirección de respaldo completa.
 *  - El aviso (CA7): sin enlace de restablecimiento, con «¿No fuiste tú?» y el contacto de
 *    `clinica.contacto` (D16).
 *  - Los dos tienen versión de texto y su hoja solo lleva valores de tokens (D9, D17).
 *  - Tienen el estilo del login (D20).
 *  - Un fallo del correo solo registra la clase del error (TM6).
 */
class CorreosRestablecimientoTest extends TestCase
{
    use DatabaseTransactions, ReglasCss, TokensAcceso;

    private const HOJA = 'views/correos/acceso.css';

    private const APP_URL = 'https://dentissa.test';

    private function crearUsuario(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'correos.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => 'cliente',
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('contrasena-anterior'),
        ]);
    }

    private function direccion(Usuario $usuario, string $token): string
    {
        return self::APP_URL.'/contrasena/nueva/'.$token.'?cuenta='.$usuario->id;
    }

    /** @return array{0: string, 1: string} el HTML y el texto del correo */
    private function renderizar(object $correo): array
    {
        $html = '';
        $texto = '';
        (function () use (&$html, &$texto) {
            [$html, $texto] = $this->renderForAssertions();
        })->call($correo);

        return [$html, $texto];
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function sinEspacios(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    public function test_el_enlace_usa_app_url_aunque_el_host_sea_otro(): void
    {
        config(['app.url' => self::APP_URL]);
        Mail::fake();
        $usuario = $this->crearUsuario();

        // Por la petición, con una cabecera Host falsa (TM12).
        // Con URL absoluta el host falso llega de verdad a la petición: `withHeader` no lo cambia (R7).
        $this->post('http://sitio-malicioso.example/contrasena/olvidada', ['email' => $usuario->email]);

        Mail::assertSent(EnlaceRestablecimiento::class, function (EnlaceRestablecimiento $correo) use ($usuario) {
            [$html, $texto] = $this->renderizar($correo);
            foreach ([$html, $texto] as $version) {
                $this->assertStringNotContainsString('sitio-malicioso.example', $version, 'El enlace no sale de la petición.');
                $this->assertMatchesRegularExpression('#'.preg_quote(self::APP_URL.'/contrasena/nueva/', '#').'[A-Za-z0-9]{64}\?cuenta='.preg_quote($usuario->id, '#').'#', $version);
            }

            return true;
        });

        // Y directamente por el broker.
        Mail::fake();
        $otro = $this->crearUsuario();
        $this->assertSame(Password::RESET_LINK_SENT, Password::sendResetLink(['email' => $otro->email, 'activo' => 1]));
        Mail::assertSent(EnlaceRestablecimiento::class, fn (EnlaceRestablecimiento $correo) => $correo->hasTo($otro->email)
            && str_contains($this->renderizar($correo)[0], self::APP_URL.'/contrasena/nueva/'));
    }

    public function test_el_correo_del_enlace_tiene_caducidad_y_aviso(): void
    {
        config(['app.url' => self::APP_URL, 'clinica.zona_horaria' => 'America/Mexico_City']);
        Carbon::setTestNow('2026-10-27 20:35:00');
        $usuario = $this->crearUsuario();
        $token = Str::random(64);

        [$html, $texto] = $this->renderizar(new EnlaceRestablecimiento($usuario, $token));
        $direccion = $this->direccion($usuario, $token);

        $xpath = $this->xpath($html);
        $boton = $xpath->query('//a[normalize-space(.)="Elegir contraseña nueva"]');
        $this->assertSame(1, $boton->length, 'Con el botón.');
        $this->assertSame($direccion, $boton->item(0)->getAttribute('href'));
        $visible = $this->sinEspacios($xpath->query('//body')->item(0)->textContent);

        foreach ([$visible, $this->sinEspacios($texto)] as $version) {
            $this->assertStringContainsString('60 minutos', $version);
            // 20:35 UTC + 60 minutos, en hora de la clínica (UTC-6 ese día).
            $this->assertStringContainsString('15:35', $version, 'La hora en clinica.zona_horaria.');
            $this->assertStringContainsString('Si no lo pediste, ignora este correo', $version);
            $this->assertStringContainsString($direccion, $version, 'La dirección de respaldo completa.');
            $this->assertStringNotContainsString($usuario->email, $version, 'El correo de la cuenta no va en el cuerpo.');
        }
        $this->assertStringContainsString('Elegir contraseña nueva', $texto);
        $this->assertStringNotContainsString($usuario->email, $direccion, 'Ni en la dirección (P8).');
    }

    public function test_el_aviso_no_tiene_enlace_y_da_el_contacto(): void
    {
        config([
            'clinica.contacto.telefono' => '999 111 2222',
            'clinica.contacto.horario' => ['Martes' => '10:00–11:00'],
        ]);
        $usuario = $this->crearUsuario();

        [$html, $texto] = $this->renderizar(new ContrasenaCambiada($usuario));

        foreach ([$this->sinEspacios($this->xpath($html)->query('//body')->item(0)->textContent), $this->sinEspacios($texto)] as $version) {
            $this->assertStringContainsString('¿No fuiste tú?', $version);
            $this->assertStringContainsString('999 111 2222', $version, 'El teléfono sale de clinica.contacto (D16).');
            $this->assertStringContainsString('Martes', $version);
            $this->assertStringContainsString('10:00–11:00', $version, 'Y el horario.');
            $this->assertStringNotContainsString('753 000 0000', $version, 'Sin el teléfono escrito en la vista.');
        }
        foreach ([$html, $texto] as $version) {
            $this->assertStringNotContainsString('/contrasena', $version, 'Sin enlace de restablecimiento (CA7).');
        }
    }

    public function test_los_dos_correos_tienen_version_de_texto(): void
    {
        $usuario = $this->crearUsuario();

        foreach ([
            'enlace' => new EnlaceRestablecimiento($usuario, Str::random(64)),
            'aviso' => new ContrasenaCambiada($usuario),
        ] as $caso => $correo) {
            [$html, $texto] = $this->renderizar($correo);
            $this->assertStringContainsString('<html', $html, "HTML de {$caso}.");
            $this->assertNotSame('', trim($texto), "Texto de {$caso}.");
            $this->assertStringNotContainsString('<', $texto, "El texto de {$caso} no lleva etiquetas.");
        }
    }

    public function test_los_valores_de_la_hoja_son_tokens(): void
    {
        // El comprobador: falla con un literal fuera de las exenciones y pasa con ellas (D17).
        $this->assertFalse($this->esValorDeToken('15.5px'), 'Un tamaño que no es token.');
        $this->assertFalse($this->esValorDeToken('#123456'), 'Un color que no es token.');
        $this->assertFalse($this->esValorDeToken('1px solid #123456'));
        foreach (['0', 'auto', 'none', 'inherit', 'transparent', '100%', '16px', '#72243E', '1px solid #F0DCE4', 'Roboto, system-ui, sans-serif', '0 14px 32px rgba(114, 36, 62, 0.16)'] as $valido) {
            $this->assertTrue($this->esValorDeToken($valido), "{$valido} es un token o está exento.");
        }

        $this->assertFileExists(resource_path(self::HOJA));
        $reglas = $this->analizarCss(file_get_contents(resource_path(self::HOJA)));
        $this->assertNotEmpty($reglas, 'La hoja tiene reglas.');

        $revisadas = 0;
        foreach ($reglas as [$selector, $cuerpo]) {
            foreach ($this->declaracionesRevisadas($cuerpo) as [$propiedad, $valor]) {
                $revisadas++;
                $this->assertTrue($this->esValorDeToken($valor), "{$selector} { {$propiedad}: {$valor} } no es el valor de un token (D9).");
            }
        }
        $this->assertGreaterThan(10, $revisadas, 'La hoja declara colores, tamaños y espacios.');
    }

    /** D20: los correos adoptan el estilo del login. */
    public function test_los_correos_usan_el_estilo_del_login(): void
    {
        $usuario = $this->crearUsuario();

        foreach ([
            'enlace' => new EnlaceRestablecimiento($usuario, Str::random(64)),
            'aviso' => new ContrasenaCambiada($usuario),
        ] as $nombre => $correo) {
            [$html] = $this->renderizar($correo);
            $this->assertStringNotContainsString('troquel', $html, "{$nombre}: sin el troquel de c7.");
            $this->assertStringContainsString('linear-gradient(145deg, #993556 0%, #7A1F3A 60%, #4B1528 100%)', $html, "{$nombre}: el degradado del panel.");
            $this->assertMatchesRegularExpression('/class="correo-cab"[^>]*[; "]color: #FFFFFF/', $html, "{$nombre}: el alt del logo se lee con las imágenes bloqueadas (R8).");
            $this->assertMatchesRegularExpression('/class="correo-cab"[^>]*background-color: #72243E/', $html, "{$nombre}: color de respaldo sin degradado.");
            $this->assertStringNotContainsString('Tu sonrisa', $html, "{$nombre}: sin el lema, decisión del usuario.");
        }

        [$html] = $this->renderizar(new EnlaceRestablecimiento($usuario, Str::random(64)));
        $this->assertMatchesRegularExpression('/class="btn-primario"[^>]*display: block[^>]*border-radius: 10px/', $html, 'Botón ancho, con el radio del login.');
    }

    public function test_un_fallo_del_correo_solo_registra_la_clase(): void
    {
        $logs = CapturaLogs::instalar();
        $usuario = $this->crearUsuario();
        Mail::shouldReceive('to')->andThrow(new TransportException('Fallo SMTP con '.$usuario->email));

        // Al pedir el enlace: la respuesta no cambia.
        $this->post('/contrasena/olvidada', ['email' => $usuario->email])->assertRedirect('/contrasena/enviada');

        $this->assertSoloLaClaseDelError($logs, $usuario);
    }

    public function test_un_fallo_del_aviso_solo_registra_la_clase(): void
    {
        $logs = CapturaLogs::instalar();
        $usuario = $this->crearUsuario();
        Mail::shouldReceive('to')->andThrow(new TransportException('Fallo SMTP con '.$usuario->email));

        // Al avisar del cambio: la contraseña ya cambió y el restablecimiento termina.
        $this->post('/contrasena/nueva', [
            'token' => Password::broker()->createToken($usuario),
            'cuenta' => $usuario->id,
            'password' => 'contrasena-nueva-segura',
            'password_confirmation' => 'contrasena-nueva-segura',
        ])->assertRedirect(url('/login'));

        $this->assertTrue(Hash::check('contrasena-nueva-segura', $usuario->fresh()->password_hash));
        $this->assertSoloLaClaseDelError($logs, $usuario);
    }

    private function assertSoloLaClaseDelError(CapturaLogs $logs, Usuario $usuario): void
    {
        $fallos = array_values(array_filter($logs->registros(), fn (array $r) => $r['mensaje'] === 'restablecimiento.correo_fallido'));
        $this->assertCount(1, $fallos, 'Un registro por el correo que falló.');
        $this->assertSame('error', $fallos[0]['nivel']);
        $this->assertSame(['error' => 'TransportException'], $fallos[0]['contexto'], 'Solo la clase corta del error (P8).');
        $this->assertStringNotContainsString($usuario->email, $logs->comoTexto(), 'Sin el correo (TM6).');
    }
}
