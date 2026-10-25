<?php

namespace Tests\Feature;

use App\Avisos\Vigilante;
use App\Mail\AvisoProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * Spec 032, CA1, CA2 y CA4: el vigilante que lanza `dentissa:vigilar` cada
 * minuto (plan D8). Lee lo nuevo del log de acceso de nginx (formato
 * `combined`) y del canal `seguridad`, cuenta en una ventana de 5 minutos y
 * avisa por correo con un silencio de 30 minutos por tipo de aviso.
 *
 * Los logs y el estado viven en una carpeta temporal de cada test y el reloj
 * se fija con `travelTo`. Sin estado, la primera ejecución empieza al final del
 * log de acceso, así que cada test arranca con `iniciar()`.
 */
class VigilanteTest extends TestCase
{
    private const DESTINO = 'responsable@example.test';

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carpeta = sys_get_temp_dir().'/vigilante-'.Str::random(10);
        File::ensureDirectoryExists($this->carpeta);
        File::put($this->logAcceso(), '');

        config([
            'avisos.destino' => self::DESTINO,
            'avisos.log_acceso' => $this->logAcceso(),
            'avisos.log_seguridad' => $this->carpeta.'/seguridad.log',
            'avisos.estado' => $this->carpeta.'/avisos/estado.json',
        ]);

        Mail::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-25 10:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);

        parent::tearDown();
    }

    private function logAcceso(): string
    {
        return $this->carpeta.'/dentissa-access.log';
    }

    private function vigilar(): bool
    {
        return app(Vigilante::class)->ejecutar();
    }

    /** Primera ejecución: deja el desplazamiento al final del log. */
    private function iniciar(): void
    {
        $this->vigilar();
    }

    /** Línea del formato `combined` de nginx con la hora actual del test. */
    private function lineaAcceso(int $codigo, string $peticion = 'GET /login HTTP/1.1', string $usuario = '-', ?CarbonImmutable $momento = null): string
    {
        $hora = ($momento ?? CarbonImmutable::now())->format('d/M/Y:H:i:s O');

        return "203.0.113.5 - {$usuario} [{$hora}] \"{$peticion}\" {$codigo} 512 \"-\" \"Mozilla/5.0\"";
    }

    /** Añade al log de acceso `$cuantas` líneas con el código dado. */
    private function escribirAcceso(int $codigo, int $cuantas): void
    {
        $this->escribirLineas(array_fill(0, $cuantas, $this->lineaAcceso($codigo)));
    }

    /** @param list<string> $lineas */
    private function escribirLineas(array $lineas): void
    {
        File::append($this->logAcceso(), implode("\n", $lineas)."\n");
    }

    /**
     * Línea del canal `seguridad` con la forma del `JsonFormatter` (contrato del
     * plan), en el archivo diario de su fecha.
     *
     * @param  array<string, mixed>  $extra
     */
    private function escribirFallo(string $origen, ?string $cuenta, string $tipo = 'fallido', ?CarbonImmutable $momento = null, array $extra = []): void
    {
        $momento ??= CarbonImmutable::now();

        $linea = json_encode([
            'message' => 'acceso_fallido',
            'context' => ['tipo' => $tipo, 'metodo' => 'contrasena', 'origen' => $origen, 'cuenta' => $cuenta],
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'seguridad',
            'datetime' => $momento->format('Y-m-d\TH:i:s.uP'),
            'extra' => (object) $extra,
        ]);

        File::append($this->carpeta.'/seguridad-'.$momento->format('Y-m-d').'.log', $linea."\n");
    }

    /** `$cuantos` fallos; el origen o la cuenta cambian en cada uno si se pide. */
    private function escribirFallos(int $cuantos, bool $mismoOrigen = true, bool $mismaCuenta = true): void
    {
        for ($i = 0; $i < $cuantos; $i++) {
            $this->escribirFallo(
                $mismoOrigen ? 'aaaaaaaaaaaaaaaa' : sprintf('%016x', $i + 1),
                $mismaCuenta ? 'bbbbbbbbbbbbbbbb' : sprintf('%016x', 1000 + $i),
            );
        }
    }

    private function assertAvisoDeAccesos(int $recuento): void
    {
        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'accesos'
            && $aviso->recuento === $recuento
            && $aviso->hasTo(self::DESTINO));
    }

    private function assertAvisoDeErrores(int $recuento): void
    {
        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'errores'
            && $aviso->recuento === $recuento
            && $aviso->hasTo(self::DESTINO));
    }

    public function test_mas_del_1_por_ciento_de_5xx_avisa(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 197);
        $this->escribirAcceso(502, 3);

        $this->vigilar();

        Mail::assertSentCount(1);
        $this->assertAvisoDeErrores(3);
    }

    public function test_el_1_por_ciento_no_avisa(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 198);
        $this->escribirAcceso(500, 2);

        $this->vigilar();

        Mail::assertNothingSent();
    }

    public function test_ventana_de_5_minutos(): void
    {
        $this->iniciar();
        $this->escribirAcceso(500, 3);
        $this->escribirAcceso(200, 97);

        // Se leen 6 minutos después: por su hora, ya no están en la ventana.
        $this->travel(6)->minutes();
        $this->escribirAcceso(200, 100);
        $this->vigilar();

        Mail::assertNothingSent();
        $this->assertSame(['peticiones' => 100, 'errores' => 0], app(Vigilante::class)->ventanaDeErrores());
    }

    public function test_ignora_las_lineas_que_no_tienen_el_formato(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 50);
        $this->escribirLineas([
            'basura 500 500 500',
            '500',
            '[25/Oct/2026:10:00:00 +0000] 500',
            '203.0.113.5 - - [25/Oct/2026:10:00:00 +0000] GET / 500 1 "-" "-"',
            '',
        ]);
        $this->escribirAcceso(200, 50);

        $this->vigilar();

        Mail::assertNothingSent();
        $this->assertSame(['peticiones' => 100, 'errores' => 0], app(Vigilante::class)->ventanaDeErrores());
    }

    /**
     * /analyze A7 (TM15): `$remote_user` admite espacios y corchetes, y nginx
     * escapa las comillas como `\x22`. La hora es el último `[…]` antes de la
     * primera comilla y el código, el número que sigue a la petición.
     */
    public function test_no_se_engana_con_corchetes_ni_espacios(): void
    {
        $this->iniciar();
        $antigua = CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC')->format('d/M/Y:H:i:s O');

        // Usuario con una hora falsa y un «500» falso; la respuesta real es 200.
        $falsas = array_fill(0, 50, $this->lineaAcceso(
            200,
            'GET /?q=\x22 500 1 \x22 HTTP/1.1',
            "x [{$antigua}] \\x22GET /\\x22 500",
        ));
        // Usuario con un «200» falso; la respuesta real es 500.
        $reales = array_fill(0, 3, $this->lineaAcceso(500, 'GET / HTTP/1.1', '\x22GET /\x22 200'));

        $this->escribirLineas($falsas);
        $this->escribirLineas($reales);
        $this->escribirAcceso(200, 47);

        $this->vigilar();

        $this->assertSame(['peticiones' => 100, 'errores' => 3], app(Vigilante::class)->ventanaDeErrores());
        $this->assertAvisoDeErrores(3);
    }

    public function test_solo_lee_lo_nuevo(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 100);

        $this->vigilar();
        $this->vigilar();

        $this->assertSame(['peticiones' => 100, 'errores' => 0], app(Vigilante::class)->ventanaDeErrores());
    }

    public function test_detecta_log_truncado(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 100);
        $this->vigilar();

        // El log se vacía y vuelve a crecer menos que lo ya leído.
        File::put($this->logAcceso(), '');
        $this->escribirAcceso(500, 3);
        $this->escribirAcceso(200, 7);
        $this->vigilar();

        $this->assertSame(['peticiones' => 110, 'errores' => 3], app(Vigilante::class)->ventanaDeErrores());
        $this->assertAvisoDeErrores(3);
    }

    public function test_detecta_log_rotado(): void
    {
        $this->iniciar();
        $this->escribirAcceso(200, 100);
        $this->vigilar();

        // Rotación: el log pasa a .1 y se crea otro con otro inodo, más largo
        // que lo leído para que no baste mirar el tamaño.
        File::move($this->logAcceso(), $this->logAcceso().'.1');
        File::put($this->logAcceso(), '');
        $this->escribirAcceso(500, 3);
        $this->escribirAcceso(200, 197);
        $this->vigilar();

        $this->assertSame(['peticiones' => 300, 'errores' => 3], app(Vigilante::class)->ventanaDeErrores());
        $this->assertAvisoDeErrores(3);
    }

    /** /analyze A10: sin estado no se cuenta lo que ya había en el log. */
    public function test_primera_ejecucion_empieza_al_final(): void
    {
        $this->escribirAcceso(500, 50);
        $this->escribirAcceso(200, 50);

        $this->vigilar();
        $this->escribirAcceso(200, 100);
        $this->vigilar();

        Mail::assertNothingSent();
        $this->assertSame(['peticiones' => 100, 'errores' => 0], app(Vigilante::class)->ventanaDeErrores());
    }

    /** /analyze A10 (TM12): lo que supera el tope se lee en la ejecución siguiente. */
    public function test_lo_que_supera_el_tope_se_lee_despues(): void
    {
        $this->iniciar();
        $linea = $this->lineaAcceso(200);
        // Tope que corta a mitad de la línea 21.
        config(['avisos.tope_bytes' => 20 * (strlen($linea) + 1) + 10]);

        $this->escribirAcceso(200, 20);
        $this->escribirAcceso(500, 3);

        $this->vigilar();
        Mail::assertNothingSent();
        $this->assertSame(['peticiones' => 20, 'errores' => 0], app(Vigilante::class)->ventanaDeErrores());

        $this->vigilar();
        $this->assertSame(['peticiones' => 23, 'errores' => 3], app(Vigilante::class)->ventanaDeErrores());
        $this->assertAvisoDeErrores(3);
    }

    /** TM14: el estado no guarda nada de la petición ni de quien la hace. */
    public function test_el_estado_no_guarda_peticiones(): void
    {
        $this->iniciar();
        $this->escribirLineas(array_fill(0, 3, $this->lineaAcceso(500, 'GET /pacientes?buscar=Maria%20Lopez HTTP/1.1', 'mlopez')));
        $this->escribirAcceso(200, 97);

        $this->vigilar();

        $estado = File::get(config('avisos.estado'));
        $this->assertStringNotContainsString('buscar', $estado);
        $this->assertStringNotContainsString('Maria', $estado);
        $this->assertStringNotContainsString('mlopez', $estado);
        $this->assertStringNotContainsString('203.0.113', $estado);
        $this->assertStringNotContainsString('Mozilla', $estado);
    }

    /** /analyze A36: un log en otro formato no apaga el aviso en silencio. */
    public function test_avisa_si_el_log_cambia_de_formato(): void
    {
        $this->iniciar();
        $this->escribirLineas(array_fill(0, 25, '{"time":"2026-10-25T10:00:00Z","status":500,"uri":"/login"}'));

        $this->vigilar();

        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'formato');
    }

    /** /analyze A49: el cambio de formato también se ve con poco tráfico. */
    public function test_avisa_del_formato_con_poco_trafico(): void
    {
        $this->iniciar();
        $otra = '{"time":"2026-10-25T10:00:00Z","status":200,"uri":"/login"}';

        foreach ([2, 2, 1] as $cuantas) {
            $this->escribirLineas(array_fill(0, $cuantas, $otra));
            $this->vigilar();
            $this->travel(6)->minutes();
        }

        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'formato');
    }

    /** /analyze A49: cinco líneas buenas sueltas no son un cambio de formato. */
    public function test_pocas_lineas_buenas_no_avisan_del_formato(): void
    {
        $this->iniciar();

        foreach ([2, 2, 1] as $cuantas) {
            $this->escribirAcceso(200, $cuantas);
            $this->vigilar();
            $this->travel(6)->minutes();
        }

        Mail::assertNothingSent();
    }

    public function test_mas_de_10_fallos_por_origen_avisa(): void
    {
        $this->iniciar();
        $this->escribirFallos(11, mismoOrigen: true, mismaCuenta: false);

        $this->vigilar();

        Mail::assertSentCount(1);
        $this->assertAvisoDeAccesos(11);
    }

    public function test_mas_de_10_fallos_por_cuenta_avisa(): void
    {
        $this->iniciar();
        $this->escribirFallos(11, mismoOrigen: false, mismaCuenta: true);

        $this->vigilar();

        Mail::assertSentCount(1);
        $this->assertAvisoDeAccesos(11);
    }

    public function test_10_fallos_no_avisa(): void
    {
        $this->iniciar();
        $this->escribirFallos(10);
        // Once en total, pero ni el origen ni la cuenta pasan de 10.
        $this->escribirFallo('cccccccccccccccc', 'dddddddddddddddd');

        $this->vigilar();

        Mail::assertNothingSent();
    }

    public function test_cuenta_los_fallos_de_ayer_dentro_de_la_ventana(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 00:02:00', 'UTC'));
        $this->iniciar();

        for ($i = 0; $i < 6; $i++) {
            $this->escribirFallo('aaaaaaaaaaaaaaaa', null, momento: CarbonImmutable::parse('2026-10-25 23:58:30', 'UTC'));
        }
        for ($i = 0; $i < 5; $i++) {
            $this->escribirFallo('aaaaaaaaaaaaaaaa', null, momento: CarbonImmutable::parse('2026-10-26 00:01:00', 'UTC'));
        }

        $this->vigilar();

        $this->assertAvisoDeAccesos(11);
    }

    /** /analyze A11: la 020 añadirá `request_id` y `actor_id` en `extra`. */
    public function test_lee_lineas_con_extra(): void
    {
        $this->iniciar();

        for ($i = 0; $i < 11; $i++) {
            $this->escribirFallo('aaaaaaaaaaaaaaaa', null, extra: ['request_id' => (string) Str::uuid(), 'actor_id' => null]);
        }

        $this->vigilar();

        $this->assertAvisoDeAccesos(11);
    }

    /** /analyze A44: el canal `seguridad` se lee con el mismo tope que el log de acceso. */
    public function test_el_canal_seguridad_se_lee_con_tope(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);
        $archivo = $this->carpeta.'/seguridad-2026-10-25.log';
        $porLinea = intdiv(File::size($archivo), 11);
        config(['avisos.tope_bytes' => 5 * $porLinea + 3]);

        $this->vigilar();
        $this->vigilar();
        Mail::assertNothingSent();

        $this->vigilar();
        $this->assertAvisoDeAccesos(11);
    }

    public function test_silencio_de_30_minutos_acumula(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);
        $this->vigilar();

        $this->travel(10)->minutes();
        $this->escribirFallos(12);
        $this->vigilar();

        $this->travel(5)->minutes();
        $this->escribirFallos(3);
        $this->vigilar();

        Mail::assertSentCount(1);

        $this->travel(16)->minutes();
        $this->vigilar();

        Mail::assertSentCount(2);
        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'accesos'
            && $aviso->recuento === 15
            && $aviso->anterior !== null);
    }

    public function test_envia_lo_pendiente_al_acabar_el_silencio(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);
        $this->vigilar();

        $this->travel(10)->minutes();
        $this->escribirFallos(12);
        $this->vigilar();

        // La ráfaga terminó: en la ventana ya no hay fallos.
        $this->travel(21)->minutes();
        $this->vigilar();

        Mail::assertSentCount(2);
        Mail::assertSent(AvisoProduccion::class, fn (AvisoProduccion $aviso) => $aviso->tipo === 'accesos'
            && $aviso->recuento === 12);
    }

    public function test_los_tipos_tienen_silencio_propio(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);
        $this->vigilar();

        $this->travel(5)->minutes();
        $this->escribirAcceso(500, 3);
        $this->escribirAcceso(200, 97);
        $this->vigilar();

        Mail::assertSentCount(2);
        $this->assertAvisoDeAccesos(11);
        $this->assertAvisoDeErrores(3);
    }

    /** /analyze A9: 11 fallos y 10 ejecuciones dan un solo aviso con 11. */
    public function test_cada_evento_se_cuenta_una_vez(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);

        for ($minuto = 0; $minuto < 10; $minuto++) {
            $this->vigilar();
            $this->travel(1)->minutes();
        }
        $this->travel(25)->minutes();
        $this->vigilar();

        Mail::assertSentCount(1);
        $this->assertAvisoDeAccesos(11);
    }

    public function test_un_envio_fallido_no_marca_el_aviso_como_enviado(): void
    {
        $this->iniciar();
        $this->escribirFallos(11);

        Log::spy();
        Mail::shouldReceive('to')->andThrow(new TransportException('535 credenciales de envio@example.test rechazadas'));

        $this->assertFalse($this->vigilar());
        Log::shouldHaveReceived('warning')->with('aviso no enviado', ['tipo' => 'accesos'])->once();

        // Al minuto siguiente el envío funciona y el aviso sale.
        Mail::fake();
        $this->travel(1)->minutes();

        $this->assertTrue($this->vigilar());
        $this->assertAvisoDeAccesos(11);
    }
}
