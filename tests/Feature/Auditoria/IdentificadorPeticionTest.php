<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\Auditoria;
use App\Models\Usuario;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\CapturaLogs;
use Tests\TestCase;

/**
 * Spec 020, CA8, CA9, CA10 y CA13 (TM3, TM11): el identificador de petición `X-Request-Id`
 * y el actor en el `Context` de los logs (plan D5).
 *
 * Las rutas de prueba del grupo `web` escriben un log y un evento con el servicio `Auditoria`;
 * dos de ellas autentican o cierran sesión antes de escribirlos.
 */
class IdentificadorPeticionTest extends TestCase
{
    use DatabaseTransactions;

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private const VALIDO = '3f2b8c1e-9a4d-4c7b-8e2f-1a6b5c4d3e2f';

    private CapturaLogs $logs;

    protected function setUp(): void
    {
        parent::setUp();

        $escribir = function (string $accion) {
            Log::info('prueba.correlacion', ['accion' => $accion]);
            app(Auditoria::class)->registrar($accion, 'exito');
        };

        Route::middleware('web')->group(function () use ($escribir) {
            Route::get('/_pruebas/020/log-y-evento', function () use ($escribir) {
                $escribir('prueba_correlacion');

                return 'ok';
            });
            Route::get('/_pruebas/020/entrar/{id}', function (string $id) use ($escribir) {
                Auth::login(Usuario::findOrFail($id));
                $escribir('prueba_tras_entrar');

                return 'ok';
            });
            Route::get('/_pruebas/020/salir', function () use ($escribir) {
                Auth::logout();
                $escribir('prueba_tras_salir');

                return 'ok';
            });
        });

        $this->logs = CapturaLogs::instalar();
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Correlación',
            'email' => 'correlacion.'.Str::random(8).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** La siguiente petición del test va sin sesión: actingAs dura todo el test. */
    private function sinSesion(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    private function logDe(string $accion): array
    {
        return collect($this->logs->registros())->first(
            fn ($registro) => $registro['mensaje'] === 'prueba.correlacion' && ($registro['contexto']['accion'] ?? null) === $accion
        ) ?? $this->fail("No hay log de {$accion}.");
    }

    public function test_se_reutiliza_un_identificador_valido(): void
    {
        $respuesta = $this->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/log-y-evento');

        $respuesta->assertOk()->assertHeader('X-Request-Id', self::VALIDO);
        $this->assertSame(self::VALIDO, DB::table('auditoria')->sole()->request_id);
    }

    public function test_se_genera_uno_si_no_llega(): void
    {
        $respuesta = $this->get('/_pruebas/020/log-y-evento');

        $respuesta->assertOk();
        $this->assertMatchesRegularExpression(self::UUID, (string) $respuesta->headers->get('X-Request-Id'));
        $this->assertSame($respuesta->headers->get('X-Request-Id'), DB::table('auditoria')->sole()->request_id);
    }

    public function test_las_respuestas_de_error_tambien_lo_llevan(): void
    {
        $this->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/no-existe')
            ->assertNotFound()->assertHeader('X-Request-Id', self::VALIDO);

        $cliente = $this->crearUsuario('cliente');
        $prohibida = $this->actingAs($cliente)->get('/admin');
        $prohibida->assertForbidden();
        $this->assertMatchesRegularExpression(self::UUID, (string) $prohibida->headers->get('X-Request-Id'));
    }

    public function test_un_identificador_malformado_se_sustituye(): void
    {
        foreach (['abc', '3f2b8c1e-9a4d-4c7b-8e2f-1a6b5c4d3e2g', "3f2b8c1e-9a4d-4c7b-8e2f-1a6b5c4d3e2f\nX-Inyectada: 1", '<script>alert(1)</script>'] as $malformado) {
            $respuesta = $this->withHeader('X-Request-Id', $malformado)->get('/_pruebas/020/log-y-evento');

            $devuelto = (string) $respuesta->headers->get('X-Request-Id');
            $this->assertMatchesRegularExpression(self::UUID, $devuelto);
            $this->assertNotSame($malformado, $devuelto);
        }
    }

    public function test_un_identificador_demasiado_largo_se_sustituye(): void
    {
        $largo = self::VALIDO.'-'.str_repeat('a', 300);

        $respuesta = $this->withHeader('X-Request-Id', $largo)->get('/_pruebas/020/log-y-evento');

        $devuelto = (string) $respuesta->headers->get('X-Request-Id');
        $this->assertMatchesRegularExpression(self::UUID, $devuelto);
        $this->assertNotSame(self::VALIDO, $devuelto, 'No se recorta: se sustituye entero.');
    }

    public function test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria(): void
    {
        $malformado = 'inyectado-<b>no-es-uuid</b>-'.Str::random(6);

        $respuesta = $this->withHeader('X-Request-Id', $malformado)->get('/_pruebas/020/log-y-evento');

        $respuesta->assertOk();
        $this->assertStringNotContainsString($malformado, $respuesta->getContent());
        $this->assertStringNotContainsString($malformado, (string) $respuesta->headers);
        $this->assertStringNotContainsString($malformado, $this->logs->comoTexto());
        $this->assertFalse(DB::table('auditoria')->get()->contains(fn ($evento) => str_contains(json_encode($evento), $malformado)));
    }

    public function test_logs_y_eventos_comparten_request_id_y_actor(): void
    {
        $admin = $this->crearUsuario('admin');

        $respuesta = $this->actingAs($admin)->get('/_pruebas/020/log-y-evento');

        $evento = DB::table('auditoria')->sole();
        $log = $this->logDe('prueba_correlacion');
        $this->assertSame($respuesta->headers->get('X-Request-Id'), $log['extra']['request_id'] ?? null);
        $this->assertSame($evento->request_id, $log['extra']['request_id'] ?? null);
        $this->assertSame($admin->id, $log['extra']['actor_id'] ?? null);
        $this->assertSame($admin->id, $evento->actor_id);
    }

    public function test_tras_autenticarse_o_cerrar_sesion_logs_y_eventos_llevan_el_mismo_actor(): void
    {
        $dentista = $this->crearUsuario('dentista');

        $this->get('/_pruebas/020/entrar/'.$dentista->id)->assertOk();
        $tras = DB::table('auditoria')->where('accion', 'prueba_tras_entrar')->sole();
        $this->assertSame($dentista->id, $tras->actor_id);
        $this->assertSame($dentista->id, $this->logDe('prueba_tras_entrar')['extra']['actor_id'] ?? null);

        $this->get('/_pruebas/020/salir')->assertOk();
        $salida = DB::table('auditoria')->where('accion', 'prueba_tras_salir')->sole();
        $this->assertNull($salida->actor_id);
        $this->assertArrayNotHasKey('actor_id', $this->logDe('prueba_tras_salir')['extra']);
    }

    public function test_una_peticion_anonima_no_hereda_el_actor_de_la_anterior(): void
    {
        $admin = $this->crearUsuario('admin');
        $this->actingAs($admin)->get('/_pruebas/020/log-y-evento')->assertOk();
        $this->assertSame($admin->id, Context::get('actor_id'), 'La primera petición deja el actor en el Context.');

        $this->sinSesion();
        Log::info('prueba.correlacion', ['accion' => 'antes_de_la_anonima']);
        $this->get('/_pruebas/020/log-y-evento')->assertOk();

        $logs = collect($this->logs->registros())->where('mensaje', 'prueba.correlacion')->values();
        $this->assertArrayNotHasKey('actor_id', $logs->last()['extra'], 'La petición anónima no lleva actor_id.');
        $this->assertNull(DB::table('auditoria')->orderByDesc('id')->first()->actor_id);
    }

    /** A62: con el descubrimiento automático, un solo listener por evento. */
    public function test_cada_listener_del_actor_se_ejecuta_una_vez(): void
    {
        $crudos = Event::getRawListeners();
        foreach ([Login::class => 'handleLogin', Logout::class => 'handleLogout'] as $evento => $metodo) {
            $nuestros = collect($crudos[$evento] ?? [])->filter(fn ($listener) => is_string($listener) && str_contains($listener, 'ActualizarActorDelContexto'));
            $this->assertSame(["App\\Listeners\\ActualizarActorDelContexto@{$metodo}"], $nuestros->values()->all(), "{$evento} tiene un solo listener del actor.");
        }

        $cliente = $this->crearUsuario('cliente');
        $cambios = 0;
        Context::forget('actor_id');
        Event::listen(Login::class, function () use (&$cambios) {
            $cambios += Context::get('actor_id') !== null ? 1 : 0;
        });
        Auth::login($cliente);
        $this->assertSame($cliente->id, Context::get('actor_id'));
        $this->assertSame(1, $cambios);
    }

    public function test_un_identificador_repetido_no_transfiere_el_actor(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($admin)->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/log-y-evento')->assertOk();
        $this->sinSesion();
        $this->actingAs($asistente)->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/log-y-evento')->assertOk();

        $eventos = DB::table('auditoria')->where('request_id', self::VALIDO)->orderBy('id')->get();
        $this->assertCount(2, $eventos, 'Un identificador válido repetido se acepta.');
        $this->assertSame([$admin->id, $asistente->id], $eventos->pluck('actor_id')->all());
        $this->assertNotSame($eventos[0]->id, $eventos[1]->id);
    }

    /**
     * TM11, caracterización: se comprueba haciendo que `ContextoActor` tome temporalmente el
     * actor de un `request_id` ya visto; entonces debe fallar.
     */
    public function test_un_identificador_de_admin_reutilizado_no_da_acceso(): void
    {
        $admin = $this->crearUsuario('admin');
        $this->actingAs($admin)->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/log-y-evento')->assertOk();

        $this->sinSesion();
        $this->withHeader('X-Request-Id', self::VALIDO)->get('/admin')->assertRedirect('/login');
        $this->withHeader('X-Request-Id', self::VALIDO)->get('/_pruebas/020/log-y-evento')->assertOk();

        $ultimo = DB::table('auditoria')->orderByDesc('id')->first();
        $this->assertNull($ultimo->actor_id, 'Sin sesión no hay actor, aunque el identificador fuera de un admin.');
        $this->assertArrayNotHasKey('actor_id', collect($this->logs->registros())->where('mensaje', 'prueba.correlacion')->last()['extra']);
    }
}
