<?php

namespace Tests\Feature;

use App\Mail\AvisoProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * Spec 032, CA5 y CA6 (TM7, TM10): el correo de aviso y el comando
 * `dentissa:vigilar`. El correo sale hacia un buzón externo, así que solo lleva
 * el tipo de aviso, recuentos, horas y cómo consultar el detalle en el
 * servidor; nunca algo leído de los logs.
 */
class AvisoProduccionTest extends TestCase
{
    private const DESTINO = 'responsable@example.test';

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carpeta = sys_get_temp_dir().'/aviso-'.Str::random(10);
        File::ensureDirectoryExists($this->carpeta);
        File::put($this->carpeta.'/dentissa-access.log', '');

        config([
            'avisos.destino' => self::DESTINO,
            'avisos.log_acceso' => $this->carpeta.'/dentissa-access.log',
            'avisos.log_seguridad' => $this->carpeta.'/seguridad.log',
            'avisos.estado' => $this->carpeta.'/avisos/estado.json',
            'clinica.zona_horaria' => 'America/Mexico_City',
        ]);

        Mail::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-25 16:05:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);

        parent::tearDown();
    }

    /** Texto completo del correo: asunto y cuerpo. */
    private function texto(AvisoProduccion $aviso): string
    {
        return $aviso->envelope()->subject."\n".$aviso->render();
    }

    /** Errores y fallos de acceso con datos personales en los logs de entrada. */
    private function escribirLogsConDatos(): void
    {
        $hora = CarbonImmutable::now()->format('d/M/Y:H:i:s O');
        $lineas = [];
        for ($i = 0; $i < 3; $i++) {
            $lineas[] = "203.0.113.77 - ana [{$hora}] \"GET /pacientes?buscar=ana.lopez@example.test HTTP/1.1\" 500 512 \"-\" \"Mozilla/5.0\"";
        }
        for ($i = 0; $i < 97; $i++) {
            $lineas[] = "203.0.113.77 - - [{$hora}] \"GET /login HTTP/1.1\" 200 512 \"-\" \"Mozilla/5.0\"";
        }
        File::append($this->carpeta.'/dentissa-access.log', implode("\n", $lineas)."\n");

        $fallo = json_encode([
            'message' => 'acceso_fallido',
            'context' => ['tipo' => 'fallido', 'metodo' => 'contrasena', 'origen' => '0123456789abcdef', 'cuenta' => 'fedcba9876543210'],
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'seguridad',
            'datetime' => CarbonImmutable::now()->format('Y-m-d\TH:i:s.uP'),
            'extra' => (object) [],
        ]);
        File::append($this->carpeta.'/seguridad-2026-10-25.log', str_repeat($fallo."\n", 11));
    }

    public function test_el_correo_no_lleva_datos_personales_ni_secretos(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode('clave-de-la-aplicacion-de-prueba'),
            'mail.mailers.smtp.password' => 'contrasena-de-aplicacion-de-prueba',
        ]);

        $this->artisan('dentissa:vigilar')->assertSuccessful();
        $this->escribirLogsConDatos();
        $this->artisan('dentissa:vigilar')->assertSuccessful();

        $textos = [];
        Mail::assertSent(AvisoProduccion::class, function (AvisoProduccion $aviso) use (&$textos) {
            $textos[$aviso->tipo] = $this->texto($aviso);

            return true;
        });
        $this->assertSame(['errores', 'accesos'], array_keys($textos));

        foreach ($textos as $texto) {
            foreach ([
                '203.0.113', 'ana.lopez', '@example.test', 'buscar', 'pacientes?', 'Mozilla',
                '0123456789abcdef', 'fedcba9876543210', 'clave-de-la-aplicacion', 'contrasena-de-aplicacion',
                config('app.key'),
            ] as $prohibido) {
                $this->assertStringNotContainsString($prohibido, $texto);
            }
        }
    }

    public function test_lleva_tipo_recuentos_horas_y_como_consultar(): void
    {
        $aviso = new AvisoProduccion(
            tipo: 'errores',
            recuento: 3,
            desde: CarbonImmutable::parse('2026-10-25 16:00:00', 'UTC'),
            hasta: CarbonImmutable::parse('2026-10-25 16:05:00', 'UTC'),
            anterior: CarbonImmutable::parse('2026-10-25 15:30:00', 'UTC'),
        );

        $this->assertStringStartsWith('[Dentissa] ', $aviso->envelope()->subject);
        $this->assertStringContainsString('Errores del servidor', $aviso->envelope()->subject);

        $cuerpo = $aviso->render();
        $this->assertMatchesRegularExpression('/\b3 errores del servidor\b/', $cuerpo);
        // Horas de la clínica (UTC-6): 16:00 y 16:05 UTC son las 10:00 y las 10:05.
        $this->assertStringContainsString('10:00', $cuerpo);
        $this->assertStringContainsString('10:05', $cuerpo);
        $this->assertStringContainsString('desde el aviso anterior', $cuerpo);
        $this->assertStringContainsString('09:30', $cuerpo);
        $this->assertStringContainsString('Cómo consultar el detalle en el servidor', $cuerpo);
        $this->assertStringContainsString('journalctl -u dentissa-vigilar', $cuerpo);
    }

    public function test_el_aviso_de_prueba_se_envia(): void
    {
        $this->artisan('dentissa:vigilar --prueba')->assertExitCode(0);

        Mail::assertSentCount(1);
        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'prueba'
            && $aviso->hasTo(self::DESTINO));
    }

    /** TM10, /analyze A21: un envío fallido se ve en el journal del temporizador. */
    public function test_vigilar_sale_con_error_si_falla_el_envio(): void
    {
        $this->artisan('dentissa:vigilar')->assertSuccessful();
        $this->escribirLogsConDatos();

        Mail::shouldReceive('to')->andThrow(new TransportException('535 credenciales rechazadas'));

        $this->artisan('dentissa:vigilar')->assertFailed();
    }
}
