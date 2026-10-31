<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use App\Auditoria\AuditoriaNoDisponible;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\Support\AlmacenAuditoriaQueFalla;
use Tests\Support\CapturaLogs;
use Tests\TestCase;

/**
 * Spec 020, CA1, CA3, CA10, CA11 y CA14: el servicio `Auditoria` (plan → Contratos, API de registro).
 *
 * Los casos que necesitan una petición (IP, sesión, autenticarse a mitad) pasan por rutas de prueba
 * del grupo `web` registradas en `setUp`. Los fallos del almacén se simulan con
 * `AlmacenAuditoriaQueFalla`, que decora el almacén real.
 */
class RegistroAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::get('/_pruebas/020/registrar', function (Auditoria $auditoria) {
                $auditoria->registrar(
                    'auditoria_consultada',
                    'exito',
                    recursoTipo: 'auditoria',
                    recursoId: 'recurso_77',
                    campos: ['nombre', 'telefono'],
                    detalle: ['pagina' => 2, 'con_filtros' => true, 'desde' => '2026-10-01'],
                );

                return response()->json(['request_id' => Context::get('request_id')]);
            });

            Route::get('/_pruebas/020/autenticar-y-registrar/{id}', function (string $id, Auditoria $auditoria) {
                Auth::login(Usuario::findOrFail($id));
                $auditoria->registrar('prueba_tras_autenticarse', 'exito');

                return 'ok';
            });

            Route::get('/_pruebas/020/actor-explicito/{id}', function (string $id, Auditoria $auditoria) {
                Log::info('prueba.actor_explicito');
                $auditoria->registrar('prueba_actor_explicito', 'exito', actor: Usuario::findOrFail($id));

                return 'ok';
            });
        });
    }

    private function auditoria(): Auditoria
    {
        return app(Auditoria::class);
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Auditoría',
            'email' => 'auditoria.'.Str::random(8).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** Sustituye el almacén por uno que falla al escribir y delega el resto en el real. */
    private function almacenQueFalla(): void
    {
        $this->app->extend(AlmacenAuditoria::class, fn (AlmacenAuditoria $real) => new AlmacenAuditoriaQueFalla($real));
        $this->app->forgetInstance(Auditoria::class);
    }

    private function eventos(): Collection
    {
        return DB::table('auditoria')->orderBy('id')->get();
    }

    public function test_un_evento_guarda_todos_los_campos(): void
    {
        Carbon::setTestNow('2026-10-29 12:00:00.123456');
        $admin = $this->crearUsuario('admin');

        $respuesta = $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->get('/_pruebas/020/registrar');

        $respuesta->assertOk();
        $eventos = $this->eventos();
        $this->assertCount(1, $eventos);
        $evento = $eventos->first();
        $this->assertSame('2026-10-29 12:00:00.123456', $evento->ocurrido_en);
        $this->assertSame($admin->id, $evento->actor_id);
        $this->assertSame('admin', $evento->actor_rol);
        $this->assertNull($evento->identificador_intentado);
        $this->assertSame('auditoria_consultada', $evento->accion);
        $this->assertSame('auditoria', $evento->recurso_tipo);
        $this->assertSame('recurso_77', $evento->recurso_id);
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame(['nombre', 'telefono'], json_decode($evento->campos, true));
        $this->assertSame(['pagina' => 2, 'con_filtros' => true, 'desde' => '2026-10-01'], json_decode($evento->detalle, true));
        $this->assertSame('198.51.100.7', $evento->ip);
        $this->assertMatchesRegularExpression(self::UUID, $evento->request_id);
        $this->assertSame($respuesta->json('request_id'), $evento->request_id, 'El evento lleva el request_id del Context de la petición.');
    }

    public function test_sin_sesion_se_guarda_el_identificador_intentado(): void
    {
        $this->auditoria()->registrar('acceso_fallido', 'denegado', identificadorIntentado: 'alguien@example.test');

        $evento = $this->eventos()->sole();
        $this->assertNull($evento->actor_id);
        $this->assertNull($evento->actor_rol);
        $this->assertSame('alguien@example.test', $evento->identificador_intentado);
    }

    public function test_un_resultado_fuera_del_vocabulario_se_rechaza(): void
    {
        foreach (['denegado_403', 'ok', 'EXITO', ''] as $resultado) {
            try {
                $this->auditoria()->registrar('prueba_vocabulario', $resultado);
                $this->fail("El resultado «{$resultado}» debía rechazarse.");
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertCount(0, $this->eventos());
    }

    public function test_los_campos_solo_admiten_nombres(): void
    {
        foreach ([['nombre', 'Juan Pérez'], ['telefono' => '5550102'], ['Nombre'], [7]] as $campos) {
            try {
                $this->auditoria()->registrar('paciente_actualizado', 'exito', 'pacientes', 'p1', campos: $campos);
                $this->fail('Los campos '.json_encode($campos, JSON_UNESCAPED_UNICODE).' debían rechazarse.');
            } catch (InvalidArgumentException) {
            }
        }
        $this->assertCount(0, $this->eventos());

        $this->auditoria()->registrar('paciente_actualizado', 'exito', 'pacientes', 'p1', campos: ['telefono', 'alergias']);
        $this->assertSame(['telefono', 'alergias'], json_decode($this->eventos()->sole()->campos, true));
    }

    /** /review R9 (TM12): `$` admite un salto de línea final; los formatos se anclan al final del texto. */
    public function test_un_valor_con_salto_de_linea_final_se_rechaza(): void
    {
        foreach ([
            'recurso_id' => fn () => $this->auditoria()->registrar('prueba_salto', 'exito', 'pacientes', "p1\n"),
            'acción' => fn () => $this->auditoria()->registrar("x\n", 'exito'),
            'detalle' => fn () => $this->auditoria()->registrar('prueba_salto', 'exito', detalle: ['valor' => "v\n"]),
        ] as $que => $registrar) {
            try {
                $registrar();
                $this->fail("{$que} con salto de línea final debía rechazarse.");
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertCount(0, $this->eventos());
    }

    public function test_el_detalle_rechaza_valores_no_escalares(): void
    {
        foreach ([['filtros' => ['actor' => 'x']], ['nada' => null], ['decimal' => 1.5]] as $detalle) {
            try {
                $this->auditoria()->registrar('prueba_detalle', 'exito', detalle: $detalle);
                $this->fail('El detalle '.json_encode($detalle).' debía rechazarse.');
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertCount(0, $this->eventos());
    }

    public function test_el_detalle_rechaza_texto_libre(): void
    {
        foreach ([['motivo' => 'Dolor de muelas'], ['nota' => 'alérgico'], ['correo' => 'ana@example.test'], ['largo' => str_repeat('a', 65)]] as $detalle) {
            try {
                $this->auditoria()->registrar('prueba_detalle', 'exito', detalle: $detalle);
                $this->fail('El detalle '.json_encode($detalle, JSON_UNESCAPED_UNICODE).' debía rechazarse.');
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertCount(0, $this->eventos());
    }

    public function test_el_actor_es_el_usuario_autenticado(): void
    {
        $dentista = $this->crearUsuario('dentista');
        $this->actingAs($dentista);

        $this->auditoria()->registrar('prueba_actor', 'exito');

        $evento = $this->eventos()->sole();
        $this->assertSame($dentista->id, $evento->actor_id);
        $this->assertSame('dentista', $evento->actor_rol);
    }

    public function test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor(): void
    {
        $asistente = $this->crearUsuario('asistente');

        $this->get('/_pruebas/020/autenticar-y-registrar/'.$asistente->id)->assertOk();

        $evento = $this->eventos()->sole();
        $this->assertSame($asistente->id, $evento->actor_id);
        $this->assertSame('asistente', $evento->actor_rol);
    }

    public function test_el_actor_explicito_guarda_su_id_y_su_rol(): void
    {
        $cliente = $this->crearUsuario('cliente');

        $this->auditoria()->registrar('contrasena_restablecida', 'exito', actor: $cliente);

        $evento = $this->eventos()->sole();
        $this->assertSame($cliente->id, $evento->actor_id);
        $this->assertSame('cliente', $evento->actor_rol);
        $this->assertNull($evento->identificador_intentado);
    }

    public function test_con_actor_explicito_el_evento_lleva_esa_cuenta_y_los_logs_la_sesion(): void
    {
        $admin = $this->crearUsuario('admin');
        $cliente = $this->crearUsuario('cliente');
        $logs = CapturaLogs::instalar();

        $this->actingAs($admin)->get('/_pruebas/020/actor-explicito/'.$cliente->id)->assertOk();

        $evento = $this->eventos()->sole();
        $this->assertSame($cliente->id, $evento->actor_id);
        $this->assertSame('cliente', $evento->actor_rol);
        $log = collect($logs->registros())->firstWhere('mensaje', 'prueba.actor_explicito');
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log['extra']['actor_id'] ?? null, 'Los logs llevan el usuario de la sesión (CA10).');
        $this->assertSame($evento->request_id, $log['extra']['request_id'] ?? null);
    }

    public function test_un_actor_sin_guardar_se_rechaza_sin_registrar(): void
    {
        $sinGuardar = new Usuario(['nombre' => 'Sin', 'apellido' => 'Guardar', 'email' => 'sin.guardar@example.test', 'rol' => 'cliente']);

        $this->expectException(InvalidArgumentException::class);
        try {
            $this->auditoria()->registrar('prueba_actor', 'exito', actor: $sinGuardar);
        } finally {
            $this->assertCount(0, $this->eventos());
        }
    }

    public function test_actor_e_identificador_intentado_a_la_vez_se_rechazan(): void
    {
        $cliente = $this->crearUsuario('cliente');

        $this->expectException(InvalidArgumentException::class);
        try {
            $this->auditoria()->registrar('prueba_actor', 'exito', identificadorIntentado: 'otra@example.test', actor: $cliente);
        } finally {
            $this->assertCount(0, $this->eventos());
        }
    }

    public function test_fuera_de_http_se_genera_un_request_id(): void
    {
        Context::forget('request_id');

        $this->auditoria()->registrar('prueba_consola', 'exito');
        $this->auditoria()->registrar('prueba_consola', 'exito');

        [$primero, $segundo] = $this->eventos()->all();
        $this->assertMatchesRegularExpression(self::UUID, $primero->request_id);
        $this->assertSame($primero->request_id, $segundo->request_id, 'Se reutiliza en la misma ejecución.');
        $this->assertSame($primero->request_id, Context::get('request_id'), 'Se añade al Context para los logs.');
        $this->assertNull($primero->ip, 'Fuera de HTTP no hay dirección de origen.');
    }

    public function test_en_modo_critico_un_fallo_detiene_la_accion(): void
    {
        $this->almacenQueFalla();

        try {
            $this->auditoria()->registrar('expediente_consultado', 'exito', 'expedientes', 'e1', critico: true);
            $this->fail('El modo crítico debía detener la acción.');
        } catch (AuditoriaNoDisponible $e) {
            $this->assertNull($e->getPrevious(), 'La excepción del almacén nunca se encadena (TM13).');
        }
    }

    public function test_en_modo_critico_una_escritura_fallida_no_deja_cambios(): void
    {
        $this->almacenQueFalla();
        $id = (string) Str::uuid();

        try {
            DB::transaction(function () use ($id) {
                DB::table('servicios')->insert(['id' => $id, 'nombre' => 'Servicio de la escritura fallida', 'duracion_min' => 30, 'precio' => 0]);
                $this->auditoria()->registrar('servicio_creado', 'exito', 'servicios', $id, critico: true);
            });
            $this->fail('El modo crítico debía detener la escritura.');
        } catch (AuditoriaNoDisponible) {
        }

        $this->assertFalse(DB::table('servicios')->where('id', $id)->exists(), 'La transacción revierte el cambio.');
    }

    public function test_en_modo_no_critico_la_accion_continua_y_se_registra_el_fallo(): void
    {
        $this->almacenQueFalla();
        $logs = CapturaLogs::instalar();

        $this->auditoria()->registrar('acceso_denegado', 'denegado', 'citas', 'c1');

        $fallos = collect($logs->registros('seguridad'))->where('mensaje', 'auditoria.fallo');
        $this->assertCount(1, $fallos);
        $this->assertFalse($fallos->first()['contexto']['critico']);
    }

    public function test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log(): void
    {
        $this->almacenQueFalla();
        $logs = CapturaLogs::instalar();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99']);
        $this->auditoria()->registrar('acceso_fallido', 'denegado', 'sesion', 'valor_del_evento_77', identificadorIntentado: 'intentado.secreto@example.test');
        try {
            $this->auditoria()->registrar('expediente_consultado', 'exito', 'expedientes', 'otro_valor_88', critico: true);
        } catch (AuditoriaNoDisponible) {
        }

        $texto = $logs->comoTexto();
        $this->assertNotSame('', $texto, 'Hay líneas de fallo que revisar.');
        foreach ([
            'insert into', 'auditoria`', AlmacenAuditoriaQueFalla::IP_DE_PRUEBA, AlmacenAuditoriaQueFalla::IDENTIFICADOR_DE_PRUEBA,
            'gone away', 'valor_del_evento_77', 'otro_valor_88', 'intentado.secreto@example.test',
        ] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $texto, "«{$prohibido}» no puede llegar al log.");
        }
    }

    public function test_un_fallo_del_almacen_deja_una_linea_en_el_canal_de_seguridad(): void
    {
        $this->almacenQueFalla();
        $logs = CapturaLogs::instalar();

        $this->auditoria()->registrar('acceso_denegado', 'denegado');

        $linea = collect($logs->registros('seguridad'))->sole(fn ($registro) => $registro['mensaje'] === 'auditoria.fallo');
        $this->assertSame('error', $linea['nivel']);
        $this->assertCount(0, collect($logs->registros())->where('mensaje', 'auditoria.fallo'), 'La línea va solo al canal seguridad (D16).');
    }

    public function test_el_log_de_fallo_solo_lleva_las_claves_previstas(): void
    {
        $this->almacenQueFalla();
        $logs = CapturaLogs::instalar();

        $this->auditoria()->registrar('acceso_denegado', 'denegado', 'citas', 'c1');

        $linea = collect($logs->registros('seguridad'))->sole(fn ($registro) => $registro['mensaje'] === 'auditoria.fallo');
        $this->assertSame(['accion', 'critico', 'error'], array_keys($linea['contexto']));
        $this->assertSame('acceso_denegado', $linea['contexto']['accion']);
        $this->assertFalse($linea['contexto']['critico']);
        $this->assertStringContainsString('QueryException', $linea['contexto']['error']);
        $this->assertStringContainsString(AlmacenAuditoriaQueFalla::SQLSTATE, $linea['contexto']['error']);
    }
}
