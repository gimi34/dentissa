<?php

namespace Tests\Feature\Auditoria;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Spec 020, CA2, CA4, CA6, CA7 y CA12 (TM4, TM8, TM12): la consulta del registro en
 * `/admin/auditoria`, según `design.md` (`c4`).
 *
 * Las horas se muestran y los días de los filtros se leen en la hora de la clínica
 * (`clinica.zona_horaria`); en la base todo está en UTC (decisión del usuario, 2026-10-29).
 * Los filtros inválidos vuelven a la consulta con los errores en la sesión (A84). La propia
 * consulta deja su evento `auditoria_consultada`, así que los filtros de los tests usan valores
 * que ese evento no tiene.
 */
class ConsultaAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/admin/auditoria';

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinica.zona_horaria' => 'America/Mexico_City']);
        $this->admin = $this->crearUsuario('admin');
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Consulta',
            'email' => 'consulta.'.Str::random(8).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /** Inserta un evento sin pasar por `Auditoria`. `ocurrido_en` va en UTC. */
    private function evento(array $valores = []): array
    {
        $fila = array_merge([
            'ocurrido_en' => '2026-10-20 15:00:00.000000',
            'actor_id' => (string) Str::uuid(),
            'actor_rol' => 'dentista',
            'accion' => 'prueba_consulta',
            'recurso_tipo' => 'citas',
            'recurso_id' => 'r_'.Str::lower(Str::random(10)),
            'resultado' => 'exito',
            'ip' => '192.0.2.10',
            'request_id' => (string) Str::uuid(),
        ], $valores);
        DB::table('auditoria')->insert($fila);

        return $fila;
    }

    private function consultar(array $filtros = []): TestResponse
    {
        return $this->actingAs($this->admin)->get(self::URL.($filtros ? '?'.http_build_query($filtros) : ''));
    }

    private function consultadas(): Collection
    {
        return DB::table('auditoria')->where('accion', 'auditoria_consultada')->get();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function test_el_admin_consulta_el_registro(): void
    {
        $evento = $this->evento(['accion' => 'acceso_denegado', 'resultado' => 'denegado', 'detalle' => json_encode(['accion_solicitada' => 'admin.dashboard'])]);

        $respuesta = $this->consultar();

        $respuesta->assertOk();
        $respuesta->assertSee('<h1 class="topbar-title">Auditoría</h1>', false);
        $respuesta->assertSee('Eventos');
        $respuesta->assertSee($evento['recurso_id']);
        $respuesta->assertSee($evento['actor_id']);
        $respuesta->assertSee($evento['request_id']);
        $respuesta->assertSee('192.0.2.10');
        $respuesta->assertSee('Solicitó admin.dashboard');
        $respuesta->assertSee('Denegado');
        // 15:00 UTC del 20 de octubre son las 09:00 en la clínica.
        $respuesta->assertSee('09:00:00');
        $respuesta->assertSee('20 oct 2026');
    }

    public function test_el_admin_filtra_por_actor_accion_recurso_y_fechas(): void
    {
        $actor = (string) Str::uuid();
        $del = $this->evento(['actor_id' => $actor]);
        $otro = $this->evento();
        $this->assertFiltra(['actor' => $actor], $del['recurso_id'], $otro['recurso_id']);

        $denegado = $this->evento(['accion' => 'acceso_denegado']);
        $this->assertFiltra(['accion' => 'acceso_denegado'], $denegado['recurso_id'], $otro['recurso_id']);

        $expediente = $this->evento(['recurso_tipo' => 'expedientes']);
        $this->assertFiltra(['recurso_tipo' => 'expedientes'], $expediente['recurso_id'], $otro['recurso_id']);

        $this->assertFiltra(['recurso_id' => $del['recurso_id']], $del['recurso_id'], $otro['recurso_id']);

        $antiguo = $this->evento(['ocurrido_en' => '2026-09-02 15:00:00.000000']);
        $this->assertFiltra(['desde' => '2026-09-01', 'hasta' => '2026-09-03'], $antiguo['recurso_id'], $otro['recurso_id']);
    }

    /** /review R33: se mira dentro de la tabla, porque el valor del filtro también está en su campo. */
    private function assertFiltra(array $filtros, string $aparece, string $noAparece): void
    {
        $tabla = $this->xpath($this->consultar($filtros)->assertOk()->getContent())
            ->query('//table[contains(@class, "tabla-auditoria")]')->item(0);
        $this->assertNotNull($tabla, 'Hay tabla de eventos.');
        $this->assertStringContainsString($aparece, $tabla->textContent);
        $this->assertStringNotContainsString($noAparece, $tabla->textContent);
    }

    public function test_los_selectores_ofrecen_los_valores_registrados(): void
    {
        $this->evento(['accion' => 'acceso_denegado', 'recurso_tipo' => 'citas']);
        $this->evento(['accion' => 'expediente_consultado', 'recurso_tipo' => 'expedientes']);

        $xpath = $this->xpath($this->consultar()->assertOk()->getContent());

        $acciones = array_map(fn ($nodo) => $nodo->getAttribute('value'), iterator_to_array($xpath->query('//select[@name="accion"]/option')));
        $tipos = array_map(fn ($nodo) => $nodo->getAttribute('value'), iterator_to_array($xpath->query('//select[@name="recurso_tipo"]/option')));
        $this->assertSame('', $acciones[0], 'La primera opción es «Todas».');
        $this->assertSame('Todas', trim($xpath->query('//select[@name="accion"]/option')->item(0)->textContent));
        $this->assertContains('acceso_denegado', $acciones);
        $this->assertContains('expediente_consultado', $acciones);
        $this->assertSame(array_values(array_unique($acciones)), $acciones, 'Sin repetidos.');
        $this->assertContains('citas', $tipos);
        $this->assertContains('expedientes', $tipos);
        $this->assertSame('', $tipos[0]);
    }

    public function test_el_filtro_por_actor_solo_admite_identificadores(): void
    {
        foreach (['melissa lopez', 'ana@example.test', 'Ana'] as $actor) {
            $this->from(self::URL)->consultar(['actor' => $actor])->assertRedirect(self::URL)->assertSessionHasErrors('actor');
        }

        $this->consultar(['actor' => (string) Str::uuid()])->assertOk();
    }

    /** TM12: el texto libre de un filtro nunca llega al registro. */
    public function test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra(): void
    {
        $this->from(self::URL)->consultar(['recurso_id' => 'Juan Pérez', 'actor' => 'Juan Pérez'])
            ->assertRedirect(self::URL)->assertSessionHasErrors(['recurso_id', 'actor']);

        $this->assertFalse(DB::table('auditoria')->get()->contains(fn ($evento) => str_contains(json_encode($evento, JSON_UNESCAPED_UNICODE), 'Juan')));
        $this->assertCount(0, $this->consultadas());
    }

    /** TM8, TM12: las fechas solo como AAAA-MM-DD. */
    public function test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar(): void
    {
        foreach (['2026-10-01T10:00', '2026-10-01 10:00', '2026-10-01+06:00', '01/10/2026', '2026-13-01'] as $fecha) {
            $this->from(self::URL)->consultar(['desde' => $fecha])->assertRedirect(self::URL)->assertSessionHasErrors('desde');
            $this->from(self::URL)->consultar(['hasta' => $fecha])->assertRedirect(self::URL)->assertSessionHasErrors('hasta');
        }

        $this->assertCount(0, $this->consultadas());
    }

    /** A84 (T030): el error queda en el HTML junto a su campo, con el aviso y sin la lista. */
    public function test_el_error_de_un_filtro_queda_junto_a_su_campo(): void
    {
        $this->evento();

        $html = $this->from(self::URL)->followingRedirects()->consultar(['actor' => 'melissa lopez'])->assertOk()->getContent();

        $xpath = $this->xpath($html);
        $campo = $xpath->query('//input[@name="actor"]')->item(0);
        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame('melissa lopez', $campo->getAttribute('value'), 'Conserva lo escrito.');
        $error = $xpath->query('//*[@id="f-actor-error"]')->item(0);
        $this->assertNotNull($error, 'El error tiene su id.');
        $this->assertStringContainsString('Escribe el identificador del usuario', $error->textContent);
        $this->assertContains('f-actor-error', explode(' ', $campo->getAttribute('aria-describedby')));
        $aviso = $xpath->query('//*[@role="alert"]')->item(0);
        $this->assertNotNull($aviso);
        $this->assertStringContainsString('Revisa el filtro marcado: no se buscó nada.', $aviso->textContent);
        $this->assertSame(0, $xpath->query('//table[contains(@class, "tabla-auditoria")]')->length, 'Sin lista.');
        $this->assertCount(0, $this->consultadas(), 'Un filtro rechazado no se registra.');
    }

    /** El día «hasta» entra completo, en la hora de la clínica (UTC−6). */
    public function test_el_rango_incluye_el_dia_hasta(): void
    {
        $finDelDia = $this->evento(['ocurrido_en' => '2026-10-16 05:30:00.000000']);    // 15 oct, 23:30 en la clínica
        $diaSiguiente = $this->evento(['ocurrido_en' => '2026-10-16 06:30:00.000000']); // 16 oct, 00:30
        $diaAnterior = $this->evento(['ocurrido_en' => '2026-10-15 05:30:00.000000']);  // 14 oct, 23:30

        $respuesta = $this->consultar(['desde' => '2026-10-15', 'hasta' => '2026-10-15'])->assertOk();

        $respuesta->assertSee($finDelDia['recurso_id']);
        $respuesta->assertDontSee($diaSiguiente['recurso_id']);
        $respuesta->assertDontSee($diaAnterior['recurso_id']);
        $respuesta->assertSee('23:30:00');
    }

    public function test_los_resultados_se_paginan_de_25_del_mas_reciente_al_mas_antiguo(): void
    {
        $base = Carbon::parse('2026-10-01 12:00:00', 'UTC');
        foreach (range(1, 30) as $n) {
            $this->evento(['accion' => 'prueba_pagina', 'recurso_id' => sprintf('pag_%02d', $n), 'ocurrido_en' => $base->copy()->addMinutes($n)->format('Y-m-d H:i:s.u')]);
        }

        $primera = $this->consultar(['accion' => 'prueba_pagina'])->assertOk();
        $primera->assertSeeInOrder(array_map(fn ($n) => sprintf('pag_%02d', $n), range(30, 6)));
        $primera->assertDontSee('pag_05');
        $primera->assertSee('Mostrando 1–25 de 30 · página 1 de 2');
        $primera->assertSee('rel="next"', false);
        $primera->assertDontSee('rel="prev"', false);

        $segunda = $this->consultar(['accion' => 'prueba_pagina', 'page' => 2])->assertOk();
        $segunda->assertSeeInOrder(['pag_05', 'pag_04', 'pag_03', 'pag_02', 'pag_01']);
        $segunda->assertDontSee('pag_06');
        $segunda->assertSee('Mostrando 26–30 de 30 · página 2 de 2');
        $segunda->assertSee('accion=prueba_pagina', false);
    }

    public function test_sin_resultados_se_muestra_el_estado_vacio(): void
    {
        $xpath = $this->xpath($this->consultar(['accion' => 'accion_que_nunca_ocurrio'])->assertOk()->getContent());

        $vacio = $xpath->query('//*[@role="status"]')->item(0);
        $this->assertNotNull($vacio);
        $this->assertStringContainsString('No hay eventos con estos filtros', $vacio->textContent);
        $this->assertSame(1, $xpath->query('//*[@role="status"]//a[@href="'.url(self::URL).'" or @href="'.self::URL.'"][contains(., "Quitar los filtros")]')->length);
        $this->assertSame(0, $xpath->query('//table[contains(@class, "tabla-auditoria")]')->length);
    }

    public function test_el_menu_de_auditoria_solo_para_admin(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('href="/admin/auditoria"', false);

        foreach (['dentista' => '/dentista', 'asistente' => '/asistente', 'cliente' => '/cliente'] as $rol => $inicio) {
            $html = $this->actingAs($this->crearUsuario($rol))->get($inicio)->getContent();
            $this->assertStringNotContainsString('/admin/auditoria', $html, "El menú de {$rol} no tiene «Auditoría».");
        }
    }

    public function test_auditoria_es_la_seccion_actual_del_menu(): void
    {
        $xpath = $this->xpath($this->consultar()->assertOk()->getContent());

        $enlace = $xpath->query('//a[@href="/admin/auditoria"][contains(concat(" ", @class, " "), " nav-item ")]')->item(0);
        $this->assertNotNull($enlace);
        $this->assertStringContainsString('Auditoría', $enlace->textContent);
        $this->assertContains('active', explode(' ', $enlace->getAttribute('class')));
        $this->assertSame('page', $enlace->getAttribute('aria-current'));
        $this->assertSame(1, $xpath->query('//a[contains(concat(" ", @class, " "), " nav-item ")][@aria-current="page"]')->length, 'Solo una sección actual.');
    }

    /** TM4, CA7: el personal no admin y el paciente no consultan, y su intento queda registrado. */
    public function test_un_no_admin_no_consulta_el_registro(): void
    {
        foreach (['dentista', 'asistente', 'cliente'] as $rol) {
            $usuario = $this->crearUsuario($rol);

            $this->actingAs($usuario)->get(self::URL)->assertForbidden();

            $evento = DB::table('auditoria')->where('accion', 'acceso_denegado')->where('actor_id', $usuario->id)->sole();
            $this->assertSame('denegado', $evento->resultado);
            $this->assertSame(['accion_solicitada' => 'admin.auditoria.index'], json_decode($evento->detalle, true));
            $this->assertSame('auditoria', $evento->recurso_tipo);
        }
        $this->assertCount(0, $this->consultadas());
    }

    /** TM8. */
    public function test_filtros_invalidos_se_rechazan(): void
    {
        foreach ([
            ['page' => 'abc'], ['page' => '0'], ['page' => '-1'],
            ['accion' => 'Acceso Denegado'], ['accion' => str_repeat('a', 61)], ['recurso_tipo' => 'citas;drop'],
            ['recurso_id' => str_repeat('a', 65)], ['desde' => '2026-10-10', 'hasta' => '2026-10-09'],
        ] as $filtros) {
            $respuesta = $this->from(self::URL)->consultar($filtros);
            $respuesta->assertRedirect(self::URL);
            $respuesta->assertSessionHasErrors(array_key_last($filtros));
        }

        $this->assertCount(0, $this->consultadas());
    }

    /** /review R7 (P4): un filtro enviado como lista vuelve con su error, sin un 500 al reponerlo. */
    public function test_un_filtro_enviado_como_lista_vuelve_con_su_error(): void
    {
        foreach (['actor', 'recurso_id', 'desde'] as $campo) {
            $html = $this->actingAs($this->admin)->from(self::URL)->followingRedirects()
                ->get(self::URL.'?'.$campo.'[]=x')->assertOk()->getContent();

            $xpath = $this->xpath($html);
            $input = $xpath->query('//input[@name="'.$campo.'"]')->item(0);
            $this->assertSame('true', $input?->getAttribute('aria-invalid'), "{$campo}: el campo queda marcado.");
            $error = collect(explode(' ', $input->getAttribute('aria-describedby')))->first(fn ($id) => str_ends_with($id, '-error'));
            $this->assertNotNull($error, "{$campo}: el campo apunta a su error.");
            $this->assertNotNull($xpath->query('//*[@id="'.$error.'"]')->item(0), "{$campo}: el error está junto a su campo.");
        }

        $this->assertCount(0, $this->consultadas(), 'Un filtro rechazado no se registra.');
    }

    public function test_la_consulta_queda_registrada_con_sus_filtros(): void
    {
        $this->consultar(['accion' => 'acceso_denegado', 'desde' => '2026-10-01', 'recurso_id' => ''])->assertOk();

        $evento = $this->consultadas()->sole();
        $this->assertSame('exito', $evento->resultado);
        $this->assertSame($this->admin->id, $evento->actor_id);
        $this->assertSame('auditoria', $evento->recurso_tipo);
        $this->assertSame(['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], json_decode($evento->detalle, true), 'Solo los filtros validados y con valor.');
    }

    public function test_sin_sesion_la_consulta_redirige_al_login(): void
    {
        $this->get(self::URL)->assertRedirect('/login');

        $this->assertCount(0, DB::table('auditoria')->get());
    }

    /**
     * CA2, caracterización: pasa hoy; se comprueba añadiendo temporalmente una ruta `DELETE`
     * de prueba en `routes/web.php`; entonces debe fallar.
     */
    public function test_no_existen_rutas_de_modificacion(): void
    {
        $deAuditoria = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_starts_with($ruta->uri(), 'admin/auditoria'));

        foreach ($deAuditoria as $ruta) {
            $this->assertSame([], array_diff($ruta->methods(), ['GET', 'HEAD']), 'La ruta '.$ruta->uri().' solo admite lectura.');
        }
        foreach (['post', 'put', 'patch', 'delete'] as $metodo) {
            $this->actingAs($this->admin)->{$metodo}(self::URL.'/1')->assertStatus(404);
            $estado = $this->actingAs($this->admin)->{$metodo}(self::URL)->getStatusCode();
            $this->assertContains($estado, [404, 405], "{$metodo} sobre la consulta no modifica nada.");
        }
    }
}
