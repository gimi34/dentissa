<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Páginas de error propias y salud (spec 044, CA7–CA12; TM1–TM3, TM6, TM7): 404, 403, 419, 429 y 500 en
 * español, con la marca y sus salidas, sin detalles internos ni consultas a la base, y `/up` sin indexar.
 *
 * Las rutas de prueba van en `setUp()` dentro del grupo `web`, como en `DenegacionesAuditadasTest`; las
 * rutas de reserva de la spec se resuelven siempre después que ellas.
 */
class PaginasErrorTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const DETALLE = 'detalle-interno-044-no-debe-verse';

    private const EXTERNO = 'https://malo.example';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Cache::flush();

        Route::middleware('web')->prefix('_pruebas/044')->group(function () {
            Route::get('/caducada', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
            Route::get('/limite', fn () => throw new ThrottleRequestsException('Too Many Attempts.'));
            Route::get('/fallo', fn () => throw new RuntimeException(self::DETALLE));
            Route::get('/no-encontrada', fn () => abort(404, self::DETALLE));
            Route::get('/denegada-con-mensaje', fn () => throw new AuthorizationException('Como prueba no puedes hacer esto.'));
            Route::get('/denegada-generica', fn () => throw new AuthorizationException);
            Route::get('/denegada-con-detalle', fn () => abort(403, self::DETALLE));
        });
    }

    // ── 404 (CA7, CA11) ────────────────────────────────────────────────────────────────

    public function test_una_direccion_inexistente_sin_sesion_ofrece_inicio_login_y_registro(): void
    {
        $respuesta = $this->get('/no-existe-044')->assertNotFound();
        $xpath = $this->xpath($respuesta->getContent());

        $this->assertSame(['No encontramos esta página'], $this->h1($xpath));
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));
        $this->assertSame(1, $this->enlacesA($xpath, '/login', 'Iniciar sesión'));
        $this->assertSame(1, $this->enlacesA($xpath, '/registro', 'Crear cuenta'));
        $this->assertSame(0, $this->enlacesConTexto($xpath, 'Ir a mi panel'), 'Sin sesión, sin «Ir a mi panel».');
        $this->assertSame(1, $xpath->query('//meta[@name="robots"][@content="noindex"]')->length, 'La 404 no se indexa.');
    }

    /**
     * /review R4: con una sesión de verdad (inicio de sesión y guard olvidado), no con `actingAs`, para que el usuario
     * solo pueda salir de la sesión que abre la ruta de reserva GET dentro del grupo `web` (D3, D4).
     */
    public function test_una_direccion_inexistente_con_sesion_ofrece_ir_a_mi_panel(): void
    {
        $this->post('/login', ['email' => $this->paciente1->email, 'password' => 'secreto-de-pruebas'])->assertRedirect();
        $this->app['auth']->forgetGuards();

        $xpath = $this->xpath($this->get('/no-existe-044')->assertNotFound()->getContent());

        $this->assertSame(1, $this->enlacesA($xpath, '/cliente', 'Ir a mi panel'));
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));
        $this->assertSame(0, $this->enlacesA($xpath, '/login', 'Iniciar sesión'), 'Con sesión, sin «Iniciar sesión».');
    }

    public function test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404(): void
    {
        // /review R3: los siete verbos; HEAD no lleva cuerpo.
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $verbo) {
            $respuesta = $this->call($verbo, '/no-existe-044')->assertNotFound();
            if ($verbo !== 'HEAD') {
                $this->assertSame(['No encontramos esta página'], $this->h1($this->xpath($respuesta->getContent())), "{$verbo}: la página propia.");
            }
        }
    }

    /**
     * /review R3: en los tests la comprobación CSRF está desactivada, así que se mira la ruta resuelta: la reserva de
     * los verbos distintos de GET no pasa por la sesión ni por la comprobación CSRF (si no, un POST daría 419), y la
     * de GET sí pasa por la sesión (la 404 sabe si hay usuario).
     */
    public function test_la_reserva_de_los_otros_verbos_no_lleva_sesion_ni_csrf(): void
    {
        // Una petición antes, para que el kernel registre en el router los grupos de middleware (`web`).
        $this->get('/no-existe-044')->assertNotFound();

        $middleware = fn (string $verbo) => implode(' ', array_map(
            fn ($m) => is_string($m) ? $m : get_class($m),
            app('router')->gatherRouteMiddleware(Route::getRoutes()->match(Request::create('/no-existe-044', $verbo)))
        ));

        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $verbo) {
            $lista = $middleware($verbo);
            $this->assertStringNotContainsString('StartSession', $lista, "{$verbo}: la reserva no abre sesión.");
            $this->assertDoesNotMatchRegularExpression('/Csrf|Forgery/', $lista, "{$verbo}: la reserva no pasa por la comprobación CSRF.");
        }
        $this->assertStringContainsString('StartSession', $middleware('GET'), 'GET: la reserva sí pasa por la sesión (control).');
    }

    public function test_un_verbo_equivocado_responde_404(): void
    {
        $xpath = $this->xpath($this->get('/logout')->assertNotFound()->getContent());

        $this->assertSame(['No encontramos esta página'], $this->h1($xpath), 'GET /logout (solo admite POST) responde la 404 propia.');
    }

    public function test_la_404_no_muestra_el_mensaje_de_la_excepcion(): void
    {
        $this->get('/_pruebas/044/no-encontrada')->assertNotFound()
            ->assertSee('No encontramos esta página')
            ->assertDontSee(self::DETALLE, false);
    }

    /** Caracterización del código (TM3): la cita de otro y una inexistente responden la misma 404. */
    public function test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente(): void
    {
        $ajena = $this->cita($this->d2, $this->p2, 'agendada', now('America/Mexico_City')->addDays(5)->setTime(10, 0)->format('Y-m-d H:i'));

        $deOtro = $this->actingAs($this->paciente1)->get("/cliente/citas/{$ajena}");
        $inexistente = $this->actingAs($this->paciente1)->get('/cliente/citas/'.Str::uuid());

        $deOtro->assertNotFound();
        $inexistente->assertNotFound();
        $this->assertSame($inexistente->getContent(), $deOtro->getContent(), 'El cuerpo no revela si la cita existe.');
        $this->assertStringContainsString('No encontramos esta página', $deOtro->getContent());
    }

    // ── 403 (CA8, TM7) ─────────────────────────────────────────────────────────────────

    public function test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje(): void
    {
        $xpath = $this->xpath($this->actingAs($this->dentista1)->get('/admin')->assertForbidden()->getContent());
        $this->assertSame(['No tienes acceso a esta página'], $this->h1($xpath));
        $this->assertSame(1, $this->enlacesA($xpath, '/dentista', 'Ir a mi panel'));
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));

        $this->actingAs($this->dentista1)->get('/_pruebas/044/denegada-con-mensaje')->assertForbidden()
            ->assertSee('No tienes acceso a esta página')
            ->assertSee('Como prueba no puedes hacer esto.');
        $this->actingAs($this->dentista1)->get('/_pruebas/044/denegada-generica')->assertForbidden()
            ->assertSee('No tienes acceso a esta página')
            ->assertDontSee('This action is unauthorized.');
        // /review R5: solo la explicación de un permiso (Response::deny), nunca el texto de otra excepción 403.
        $this->actingAs($this->dentista1)->get('/_pruebas/044/denegada-con-detalle')->assertForbidden()
            ->assertSee('No tienes acceso a esta página')
            ->assertDontSee(self::DETALLE, false);
    }

    /** Caracterización (TM7): la página nueva no cambia el registro del acceso denegado (spec 020). */
    public function test_el_acceso_denegado_sigue_en_la_auditoria(): void
    {
        $this->actingAs($this->dentista1)->get('/admin')->assertForbidden();

        $evento = DB::table('auditoria')->where('accion', 'acceso_denegado')->sole();
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'admin.dashboard'], json_decode($evento->detalle, true));
    }

    // ── 419, 429 y 500 (CA9, CA10, TM2, TM6) ──────────────────────────────────────────

    public function test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion(): void
    {
        $xpath = $this->xpath($this->get('/_pruebas/044/caducada')->assertStatus(419)->getContent());

        $this->assertSame(['La página caducó'], $this->h1($xpath));
        $this->assertSame(1, $this->enlacesA($xpath, '/login', 'Volver a iniciar sesión'));
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));
    }

    public function test_demasiados_intentos_explica_que_esperar(): void
    {
        $respuesta = $this->get('/_pruebas/044/limite')->assertStatus(429);
        $xpath = $this->xpath($respuesta->getContent());

        $this->assertSame(['Demasiados intentos'], $this->h1($xpath));
        $respuesta->assertSee('Espera unos minutos y vuelve a probar');
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));
    }

    /** Caracterización: los límites de hoy siguen con su propio aviso, no con la 429 nueva (CA10, /analyze A2). */
    public function test_los_limites_actuales_siguen_con_su_propio_aviso(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/registro', ['email' => "otro{$i}@example.test"]);
        }
        $this->post('/registro', ['email' => 'otro@example.test'])
            ->assertStatus(302)
            ->assertSessionHasErrors('email');

        $ultima = null;
        for ($i = 0; $i < 31; $i++) {
            $ultima = $this->get('/contrasena/nueva/'.Str::random(64).'?cuenta='.Str::uuid());
        }
        $ultima->assertStatus(429)->assertDontSee('Error 429', false)->assertSee('Demasiados intentos');
    }

    public function test_la_500_no_muestra_detalles_internos(): void
    {
        config(['app.debug' => false]);

        $respuesta = $this->get('/_pruebas/044/fallo')->assertStatus(500);
        $xpath = $this->xpath($respuesta->getContent());

        $this->assertSame(['Algo salió mal'], $this->h1($xpath));
        $respuesta->assertSee('Vuelve a intentarlo más tarde');
        $this->assertSame(1, $this->enlacesA($xpath, '/', 'Ir a la página de inicio'));
        $respuesta->assertDontSee(self::DETALLE, false)
            ->assertDontSee('RuntimeException', false)
            ->assertDontSee(base_path(), false)
            ->assertDontSee('#0 ', false);
    }

    /** TM6 y /review R22: ninguna página de error consulta la base, con usuario resuelto y sin él. */
    public function test_pintar_la_500_no_consulta_la_base(): void
    {
        $paginas = [
            '500' => new HttpException(500),
            '404' => new HttpException(404),
            '403' => new HttpException(403),
        ];
        foreach ([false, true] as $conUsuario) {
            if ($conUsuario) {
                $this->actingAs($this->paciente1);
            }
            foreach ($paginas as $codigo => $excepcion) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                view("errors.{$codigo}", ['exception' => $excepcion])->render();
                $this->assertSame([], DB::getQueryLog(), "La {$codigo} ".($conUsuario ? 'con' : 'sin').' usuario no consulta la base.');
            }
        }
    }

    public function test_las_paginas_de_error_llevan_el_identificador_de_peticion(): void
    {
        config(['app.debug' => false]);
        $paginas = [
            '404' => fn () => $this->get('/no-existe-044'),
            '403' => fn () => $this->actingAs($this->dentista1)->get('/admin'),
            '419' => fn () => $this->get('/_pruebas/044/caducada'),
            '429' => fn () => $this->get('/_pruebas/044/limite'),
            '500' => fn () => $this->get('/_pruebas/044/fallo'),
        ];

        foreach ($paginas as $codigo => $pedir) {
            $respuesta = $pedir();
            $respuesta->assertStatus((int) $codigo);
            $id = (string) $respuesta->headers->get('X-Request-Id');
            $this->assertNotSame('', $id, "{$codigo}: lleva X-Request-Id.");
            if ((int) $codigo === 500) {
                $this->assertStringContainsString($id, $respuesta->getContent(), '500: el identificador también en la página.');
            } else {
                $this->assertStringNotContainsString($id, $respuesta->getContent(), "{$codigo}: el identificador solo en la cabecera (A7).");
            }
        }
    }

    // ── Enlaces de vuelta con destino fijo (CA11, TM1) ─────────────────────────────────

    public function test_los_enlaces_de_vuelta_ignoran_un_destino_externo(): void
    {
        $consulta = '?volver='.urlencode(self::EXTERNO).'&redirect='.urlencode(self::EXTERNO).'&next='.urlencode(self::EXTERNO);

        foreach (['/login', '/registro', '/contrasena/olvidada', '/no-existe-044'] as $ruta) {
            $this->assertStringNotContainsString('malo.example', $this->get($ruta.$consulta)->getContent(), "{$ruta}: ningún enlace al destino de la dirección.");
        }
        $this->assertStringNotContainsString('malo.example', $this->actingAs($this->dentista1)->get('/admin'.$consulta)->getContent(), '403: ningún enlace al destino de la dirección.');
        $this->actingAs($this->paciente1)->post('/logout'.$consulta)->assertRedirect(route('inicio'));
    }

    // ── Salud (CA12) ───────────────────────────────────────────────────────────────────

    public function test_la_comprobacion_de_salud_no_se_indexa(): void
    {
        $respuesta = $this->get('/up')->assertOk();

        $this->assertStringContainsString('noindex', (string) $respuesta->headers->get('X-Robots-Tag'));
    }

    // ── Ayudas ─────────────────────────────────────────────────────────────────────────

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new DOMXPath($dom);
    }

    /** Textos de los h1 de la página. */
    private function h1(DOMXPath $xpath): array
    {
        $textos = [];
        foreach ($xpath->query('//h1') as $h1) {
            $textos[] = trim(preg_replace('/\s+/', ' ', $h1->textContent));
        }

        return $textos;
    }

    private function ruta(string $href): string
    {
        $ruta = parse_url($href, PHP_URL_PATH);
        if ($ruta === null || $ruta === '') {
            return parse_url($href, PHP_URL_HOST) !== null ? '/' : $href;
        }

        return $ruta;
    }

    private function enlacesA(DOMXPath $xpath, string $ruta, string $texto): int
    {
        $total = 0;
        foreach ($xpath->query('//a[@href]') as $enlace) {
            if ($this->ruta($enlace->getAttribute('href')) === $ruta && trim(preg_replace('/\s+/', ' ', $enlace->textContent)) === $texto) {
                $total++;
            }
        }

        return $total;
    }

    private function enlacesConTexto(DOMXPath $xpath, string $texto): int
    {
        $total = 0;
        foreach ($xpath->query('//a') as $enlace) {
            if (trim($enlace->textContent) === $texto) {
                $total++;
            }
        }

        return $total;
    }
}
