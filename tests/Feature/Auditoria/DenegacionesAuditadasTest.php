<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\Auditoria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 020, CA3 y CA4 (TM12): las denegaciones de usuarios autenticados quedan registradas con
 * la acción y el recurso solicitados, también las que responden «no encontrado»; un recurso
 * que no existe no genera evento (plan D4, «Qué es una denegación en el registro central»).
 *
 * Con rutas reales de la 025–028; para `denegar()`, la no duplicación y el 403 posterior a una
 * comprobación que no rechaza, rutas y un Gate de prueba en `setUp`.
 */
class DenegacionesAuditadasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearEscenario();

        Gate::define('prueba-siempre-no', fn () => false);
        // Una ruta sin `auth` que deniega: lo único que la separa de una denegación es la sesión.
        Route::middleware('web')->get('/_pruebas/020/publica/{id}', fn () => abort(403))->name('pruebas.publica.ver');
        Route::middleware(['web', 'auth'])->prefix('_pruebas/020')->name('pruebas.')->group(function () {
            Route::get('/recursos/{id}', fn () => abort(403))->name('recursos.ver');
            Route::get('/recursos/{id}/denegar', function (string $id) {
                app(Auditoria::class)->denegar('pruebas_denegar', 'recursos', $id);
            })->name('recursos.denegar');
            Route::get('/recursos/{id}/comprobar', function () {
                Gate::allows('prueba-siempre-no');
                abort(403);
            })->name('recursos.comprobar');
        });
    }

    private function denegaciones(): Collection
    {
        return DB::table('auditoria')->where('accion', 'acceso_denegado')->orderBy('id')->get();
    }

    /** Una cita de P2 (de D2), en el futuro, para pedirla como otro paciente o como D1. */
    private function citaDeOtro(): string
    {
        return $this->cita($this->d2, $this->p2, 'agendada', now('America/Mexico_City')->addDays(5)->setTime(10, 0)->format('Y-m-d H:i'));
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> método, ruta y nombre de ruta */
    public static function rutasDeCitaDelPaciente(): array
    {
        return [
            'detalle' => ['GET', '/cliente/citas/%s', 'cliente.citas.show'],
            'reprogramar (formulario)' => ['GET', '/cliente/citas/%s/reprogramar', 'cliente.citas.reprogramar'],
            'reprogramar (envío)' => ['PATCH', '/cliente/citas/%s/reprogramar', 'cliente.citas.reprogramar.update'],
            'cancelar' => ['PATCH', '/cliente/citas/%s/cancelar', 'cliente.citas.cancelar'],
        ];
    }

    public function test_entrar_al_area_de_otro_rol_queda_registrado(): void
    {
        $this->actingAs($this->dentista1)->get('/admin')->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertSame('acceso_denegado', $evento->accion);
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'admin.dashboard'], json_decode($evento->detalle, true));
        $this->assertSame('dashboard', $evento->recurso_tipo);
        $this->assertNull($evento->recurso_id);
        $this->assertSame($this->dentista1->id, $evento->actor_id);
        $this->assertSame('dentista', $evento->actor_rol);
    }

    /** A66: la cita de otro paciente que existe se oculta con un 404, pero deja su evento. */
    #[DataProvider('rutasDeCitaDelPaciente')]
    public function test_una_cita_ajena_denegada_como_no_encontrada_queda_registrada(string $metodo, string $ruta, string $nombre): void
    {
        $cita = $this->citaDeOtro();

        $this->actingAs($this->paciente1)->call($metodo, sprintf($ruta, $cita), ['fecha' => '2030-01-07', 'hora' => '10:00'])->assertNotFound();

        $evento = $this->denegaciones()->sole();
        $this->assertSame('denegado_404', $evento->resultado);
        $this->assertSame(['accion_solicitada' => $nombre], json_decode($evento->detalle, true));
        $this->assertSame('citas', $evento->recurso_tipo);
        $this->assertSame($cita, $evento->recurso_id);
        $this->assertSame($this->paciente1->id, $evento->actor_id);
        $this->assertSame('cita_de_otro', $this->estadoTrasLaDenegacion($cita), 'La cita ajena no cambia.');
    }

    /**
     * A66, A75 (caracterización): pasa hoy, porque nada se registra; se comprueba, ya con T027,
     * haciendo que el registro central registre cualquier 404 de un usuario autenticado.
     */
    #[DataProvider('rutasDeCitaDelPaciente')]
    public function test_una_cita_que_no_existe_no_genera_evento(string $metodo, string $ruta, string $nombre): void
    {
        $this->actingAs($this->paciente1)->call($metodo, sprintf($ruta, (string) Str::uuid()), ['fecha' => '2030-01-07', 'hora' => '10:00'])->assertNotFound();

        $this->assertCount(0, $this->denegaciones());
    }

    /**
     * /review R5 (CA4, A66): con la colación PAD SPACE, `<id>%20` encuentra la cita ajena; tiene que
     * responder como la inexistente y dejar el evento con el id guardado, no un 500.
     *
     * @return array<string, array{0: string, 1: string}> ruta y nombre de ruta
     */
    public static function rutasGetDeCitaDelPaciente(): array
    {
        return [
            'detalle' => ['/cliente/citas/%s', 'cliente.citas.show'],
            'reprogramar (formulario)' => ['/cliente/citas/%s/reprogramar', 'cliente.citas.reprogramar'],
        ];
    }

    #[DataProvider('rutasGetDeCitaDelPaciente')]
    public function test_una_cita_ajena_con_espacio_final_responde_como_la_inexistente(string $ruta, string $nombre): void
    {
        $cita = $this->citaDeOtro();

        $this->actingAs($this->paciente1)->get(sprintf($ruta, $cita.'%20'))->assertNotFound();
        $evento = $this->denegaciones()->sole();
        $this->assertSame('denegado_404', $evento->resultado);
        $this->assertSame($cita, $evento->recurso_id, 'El evento lleva el id guardado.');
        $this->assertSame(['accion_solicitada' => $nombre], json_decode($evento->detalle, true));

        $this->actingAs($this->paciente1)->get(sprintf($ruta, Str::uuid().'%20'))->assertNotFound();
        $this->assertCount(1, $this->denegaciones(), 'La inexistente no deja evento.');
    }

    /** /review R6 (CA4): `cliente.citas.show` solo lleva `auth`; sin ficha de paciente también se audita. */
    public function test_un_usuario_sin_ficha_de_paciente_deja_su_denegacion(): void
    {
        $cita = $this->citaDeOtro();

        $this->actingAs($this->dentista1)->get('/cliente/citas/'.$cita)->assertNotFound();
        $evento = $this->denegaciones()->sole();
        $this->assertSame('denegado_404', $evento->resultado);
        $this->assertSame($cita, $evento->recurso_id);
        $this->assertSame($this->dentista1->id, $evento->actor_id);
        $this->assertSame(['accion_solicitada' => 'cliente.citas.show'], json_decode($evento->detalle, true));

        $this->actingAs($this->dentista1)->get('/cliente/citas/'.Str::uuid())->assertNotFound();
        $this->assertCount(1, $this->denegaciones(), 'Una cita que no existe no deja evento.');
    }

    /** A66: el paciente no puede saber si la cita existe. */
    #[DataProvider('rutasDeCitaDelPaciente')]
    public function test_la_cita_ajena_y_la_inexistente_responden_igual(string $metodo, string $ruta, string $nombre): void
    {
        $datos = ['fecha' => '2030-01-07', 'hora' => '10:00'];
        $ajena = $this->actingAs($this->paciente1)->call($metodo, sprintf($ruta, $this->citaDeOtro()), $datos);
        $inexistente = $this->actingAs($this->paciente1)->call($metodo, sprintf($ruta, (string) Str::uuid()), $datos);

        $this->assertSame(404, $ajena->getStatusCode());
        $this->assertSame($inexistente->getStatusCode(), $ajena->getStatusCode());
        $this->assertSame($inexistente->getContent(), $ajena->getContent());
    }

    public function test_un_dentista_fuera_de_alcance_queda_registrado(): void
    {
        $cita = $this->citaDeOtro();

        $this->actingAs($this->dentista1)->patch("/dentista/citas/{$cita}/estado", ['estado' => 'confirmada'])->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'dentista.citas.estado'], json_decode($evento->detalle, true));
        $this->assertSame('citas', $evento->recurso_tipo);
        $this->assertSame($cita, $evento->recurso_id);
    }

    /** 027: para quien no es dentista, el horario no existe (404 de recurso inexistente). */
    public function test_el_horario_de_quien_no_es_dentista_no_genera_evento(): void
    {
        $asistente = DB::table('personal')->where('usuario_id', $this->asistente->id)->value('id');

        $this->actingAs($this->admin)->post("/admin/personal/{$asistente}/horarios", ['dias' => []])->assertNotFound();

        $this->assertCount(0, $this->denegaciones());
    }

    /** A75 (caracterización): se comprueba, ya con T027, registrando cualquier 404 autenticado. */
    public function test_un_recurso_que_no_existe_no_genera_evento(): void
    {
        $this->actingAs($this->dentista1)->patch('/dentista/citas/'.Str::uuid().'/estado', ['estado' => 'confirmada'])->assertNotFound();

        $this->assertCount(0, $this->denegaciones());
    }

    /** A75 (caracterización): ídem. */
    public function test_una_ruta_inexistente_no_genera_evento(): void
    {
        $this->actingAs($this->dentista1)->get('/dentista/no-existe')->assertNotFound();

        $this->assertCount(0, $this->denegaciones());
    }

    public function test_un_abort_403_de_controlador_queda_registrado(): void
    {
        $this->actingAs($this->asistente)->get('/_pruebas/020/recursos/abc_123')->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertSame('denegado', $evento->resultado);
        $this->assertSame(['accion_solicitada' => 'pruebas.recursos.ver'], json_decode($evento->detalle, true));
        $this->assertSame('recursos', $evento->recurso_tipo);
        $this->assertSame('abc_123', $evento->recurso_id);
    }

    public function test_una_denegacion_con_denegar_se_registra_una_sola_vez(): void
    {
        $this->actingAs($this->asistente)->get('/_pruebas/020/recursos/r_9/denegar')->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertSame(['accion_solicitada' => 'pruebas_denegar'], json_decode($evento->detalle, true));
        $this->assertSame('recursos', $evento->recurso_tipo);
        $this->assertSame('r_9', $evento->recurso_id);
    }

    public function test_una_comprobacion_denegada_que_no_rechaza_no_impide_registrar_el_403_posterior(): void
    {
        $this->actingAs($this->asistente)->get('/_pruebas/020/recursos/r_10/comprobar')->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertSame(['accion_solicitada' => 'pruebas.recursos.comprobar'], json_decode($evento->detalle, true));
        $this->assertSame('r_10', $evento->recurso_id);
    }

    /** TM12: un segmento de URL que no es un identificador no llega al registro. */
    public function test_un_id_de_ruta_con_formato_invalido_no_se_guarda(): void
    {
        $this->actingAs($this->asistente)->get('/_pruebas/020/recursos/'.rawurlencode('Juan Pérez <b>'))->assertForbidden();

        $evento = $this->denegaciones()->sole();
        $this->assertNull($evento->recurso_id);
        $this->assertStringNotContainsString('Juan', json_encode($evento));
    }

    /** /review R9 (TM12): un segmento con salto de línea final no llega al registro como identificador. */
    public function test_un_id_de_ruta_con_salto_de_linea_final_no_se_guarda(): void
    {
        $this->actingAs($this->asistente)->get('/_pruebas/020/recursos/p1%0A')->assertForbidden();

        $this->assertNull($this->denegaciones()->sole()->recurso_id);
    }

    /**
     * Caracterización: pasa hoy; se comprueba, ya con T027, quitando la condición de usuario
     * autenticado del registro central.
     */
    public function test_un_visitante_sin_sesion_no_genera_evento(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/_pruebas/020/recursos/abc')->assertRedirect('/login');
        $this->get('/_pruebas/020/publica/abc')->assertForbidden();
        $this->get('/cliente/citas/'.$this->citaDeOtro())->assertRedirect('/login');

        $this->assertCount(0, DB::table('auditoria')->get());
    }

    /** La cita sigue en su estado y con su fecha: ninguna ruta del paciente la tocó. */
    private function estadoTrasLaDenegacion(string $cita): string
    {
        $fila = DB::table('citas')->where('id', $cita)->first(['estado', 'paciente_id']);

        return $fila->estado === 'agendada' && $fila->paciente_id === $this->p2 ? 'cita_de_otro' : 'cambiada';
    }
}
