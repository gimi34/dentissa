<?php

namespace Tests\Feature;

use App\Agenda\CambioDeEstadoNoPermitido;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Pantallas de la reprogramación y bloques que la spec 028 toca (CA1, CA2, CA6, CA7; TM11; P9).
 *
 * Las aserciones van sobre la sección por `id`, no sobre toda la página. Reloj fijo: lunes
 * 2026-10-05 a las 16:00 UTC (10:00 en la clínica).
 */
class ReprogramarCitaVistasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const MANANA = '2026-10-06 10:00';

    private const FUTURA = '2026-10-07 10:00';

    private const MAX_MOTIVO = 'El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.';

    private const CONFIRMAR = 'Marca la casilla para confirmar que quieres cancelar la cita.';

    private const DENTISTA_INACTIVO = 'El dentista de esta cita ya no está activo y no se puede reprogramar. Cancélala o agenda una cita nueva con otro dentista.';

    private const SIN_DURACION = 'Esta cita no tiene una duración válida y no se puede reprogramar. Cancélala o agenda una cita nueva.';

    /** Selectores cuyas reglas son nuevas en la 028 (A49): todo lo demás de app.css es previo. */
    private const AMBITO_NUEVO = '/\.formulario-accesible|\.btn-danger/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->conHorarios();
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Detalle del personal (CA1, A44) ───────────────────────────────

    public function test_el_detalle_ofrece_reprogramar_solo_si_se_puede(): void
    {
        $abierta = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $cerrada = $this->cita($this->d1, $this->p1, 'completada', self::MANANA);
        $empezada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-05 09:30');

        foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $grupo => $usuario) {
            $acciones = $this->bloque($usuario, "/{$grupo}/citas/{$abierta}", 'acciones');
            $this->assertStringContainsString("/{$grupo}/citas/{$abierta}/reprogramar", $acciones, "{$grupo}: ofrece reprogramar.");
            $this->assertStringContainsString('Agendar otra cita', $acciones);
            $this->assertStringNotContainsString('Reagendar / Nueva cita', $acciones);

            foreach (['cerrada' => $cerrada, 'empezada' => $empezada] as $caso => $cita) {
                $this->assertStringNotContainsString(
                    "/citas/{$cita}/reprogramar",
                    $this->bloque($usuario, "/{$grupo}/citas/{$cita}", 'acciones'),
                    "{$grupo}, {$caso}: sin «Reprogramar».",
                );
            }
        }
    }

    public function test_sin_dentista_activo_o_sin_duracion_no_se_ofrece_reprogramar(): void
    {
        $casos = [
            'dentista inactivo' => [self::DENTISTA_INACTIVO, fn () => DB::table('usuarios')->where('id', $this->dentista1->id)->update(['activo' => 0])],
            'sin duración' => [self::SIN_DURACION, fn (string $cita) => DB::table('citas')->where('id', $cita)->update(['duracion_min' => 0])],
        ];

        foreach ($casos as $caso => [$mensaje, $preparar]) {
            DB::table('usuarios')->where('id', $this->dentista1->id)->update(['activo' => 1]);
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
            $preparar($cita);

            foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $grupo => $usuario) {
                $acciones = $this->bloque($usuario, "/{$grupo}/citas/{$cita}", 'acciones');
                $this->assertStringNotContainsString("/citas/{$cita}/reprogramar", $acciones, "{$caso}, {$grupo}: sin «Reprogramar».");

                $html = $this->actingAs($usuario)->get("/{$grupo}/citas/{$cita}/reprogramar")->assertOk()->getContent();
                $this->assertStringContainsString(e($mensaje), $html, "{$caso}, {$grupo}: explica el motivo.");
                $this->assertStringNotContainsString('name="fecha"', $html);
                $this->assertStringNotContainsString('id="no-reagendar"', $html);
            }
        }
    }

    public function test_el_detalle_del_dentista_no_enlaza_a_reprogramar(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $html = $this->actingAs($this->dentista1)->get("/dentista/citas/{$cita}")->assertOk()->getContent();

        $this->assertStringNotContainsString('/reprogramar', $html);
    }

    // ── Pantalla de reprogramar: «No reagendar» (CA6, D10) ────────────

    public function test_la_seccion_no_reagendar_cancela_con_motivo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $seccion = $this->bloque($this->admin, "/admin/citas/{$cita}/reprogramar", 'no-reagendar');
        $this->assertStringContainsString("/admin/citas/{$cita}/estado", $seccion);
        $this->assertMatchesRegularExpression('/name="_method" value="PATCH"/', $seccion);
        $this->assertMatchesRegularExpression('/name="estado" value="cancelada"/', $seccion);
        $this->assertMatchesRegularExpression('/name="formulario" value="no-reagendar"/', $seccion);
        $this->assertStringContainsString('name="motivo"', $seccion);
        $this->assertStringContainsString('btn-danger', $seccion);
        $this->assertStringContainsString('No se puede deshacer', $seccion);
        $this->assertStringNotContainsString('confirm(', $seccion, 'D12: sin confirmación nativa.');

        $this->actingAs($this->admin)
            ->patch("/admin/citas/{$cita}/estado", ['estado' => 'cancelada', 'motivo' => 'No vuelve a la clínica', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
            ->assertStatus(302);

        $this->assertSame('cancelada', $this->estadoDe($cita));
        $this->assertSame('No vuelve a la clínica', $this->entradasDeHistorial($cita)->sole()->motivo);
    }

    // ── «No reagendar»: casilla de confirmación y vuelta al detalle (R6, R1) ──

    public function test_no_reagendar_pide_confirmar_la_cancelacion(): void
    {
        foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $grupo => $usuario) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
            $formulario = "/{$grupo}/citas/{$cita}/reprogramar";

            $casilla = $this->casillaDeConfirmar($this->bloque($usuario, $formulario, 'no-reagendar'));
            $this->assertSame('', $casilla->getAttribute('aria-invalid'), "{$grupo}: sin error, la casilla no es inválida.");

            $this->actingAs($usuario)->from($formulario)
                ->patch("/{$grupo}/citas/{$cita}/estado", ['estado' => 'cancelada', 'motivo' => 'Sin casilla', 'formulario' => 'no-reagendar'])
                ->assertRedirect($formulario);
            // Se lee antes del GET: `assertSessionHasErrors` haría que el GET ya no recibiera los errores.
            $this->assertSame(self::CONFIRMAR, $this->errorDe('confirmar'));
            $this->assertSame('agendada', $this->estadoDe($cita), "{$grupo}: sin casilla no se cancela.");
            $this->assertCount(0, $this->entradasDeHistorial($cita));

            $html = $this->actingAs($usuario)->get($formulario)->assertOk()->getContent();
            $this->assertErrorDeLaCasilla((string) $this->seccion($html, 'no-reagendar'));
            $this->assertStringNotContainsString(self::CONFIRMAR, (string) $this->seccion($html, 'nueva-fecha'));
        }
    }

    public function test_tras_cancelar_desde_no_reagendar_se_vuelve_al_detalle(): void
    {
        foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $grupo => $usuario) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

            $this->actingAs($usuario)->from("/{$grupo}/citas/{$cita}/reprogramar")
                ->patch("/{$grupo}/citas/{$cita}/estado", ['estado' => 'cancelada', 'formulario' => 'no-reagendar', 'confirmar' => '1'])
                ->assertRedirect("/{$grupo}/citas/{$cita}");
            $this->assertSame('cancelada', $this->estadoDe($cita));

            // El detalle (con su #estado), no la pantalla de reprogramar, y el aviso de éxito.
            $html = $this->actingAs($usuario)->get("/{$grupo}/citas/{$cita}")->assertOk()->getContent();
            $this->assertStringContainsString('Estado actualizado correctamente.', $html);
            $this->assertNotNull($this->seccion($html, 'estado'), "{$grupo}: aterriza en el detalle.");
            $this->assertNull($this->seccion($html, 'reprogramar-cita'), "{$grupo}: no vuelve a la pantalla de reprogramar.");
        }
    }

    public function test_cada_formulario_muestra_sus_valores_y_errores_en_su_seccion(): void
    {
        $this->cita($this->d1, $this->p2, 'agendada', self::FUTURA);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $formulario = "/admin/citas/{$cita}/reprogramar";

        // Rechazo de reprogramar con motivo: el valor se queda en su sección, no en «No reagendar».
        $this->actingAs($this->admin)->from($formulario)
            ->patch($formulario, ['fecha' => '2026-10-07', 'hora' => '10:00', 'motivo' => 'Motivo de reprogramar', 'formulario' => 'reprogramar']);
        $html = $this->actingAs($this->admin)->get($formulario)->assertOk()->getContent();
        $this->assertStringContainsString('Motivo de reprogramar', (string) $this->seccion($html, 'nueva-fecha'));
        $noReagendar = (string) $this->seccion($html, 'no-reagendar');
        $this->assertStringNotContainsString('Motivo de reprogramar', $noReagendar);
        $this->assertStringNotContainsString('Ese horario ya no está disponible', $noReagendar);
        $this->assertIdsUnicos($html);

        // Cancelación rechazada con la cita abierta (A33): el error, dentro de «No reagendar».
        $this->actingAs($this->admin)->from($formulario)
            ->patch("/admin/citas/{$cita}/estado", ['estado' => 'cancelada', 'motivo' => str_repeat('b', 501), 'formulario' => 'no-reagendar', 'confirmar' => '1']);
        $html = $this->actingAs($this->admin)->get($formulario)->getContent();
        $this->assertStringContainsString(self::MAX_MOTIVO, (string) $this->seccion($html, 'no-reagendar'));
        $this->assertStringNotContainsString(self::MAX_MOTIVO, (string) $this->seccion($html, 'nueva-fecha'));
        $this->assertIdsUnicos($html);

        // A43: sin `formulario`, los errores van a la sección de reprogramar.
        $this->actingAs($this->admin)->from($formulario)
            ->patch("/admin/citas/{$cita}/estado", ['estado' => 'cancelada', 'motivo' => str_repeat('c', 501)]);
        $html = $this->actingAs($this->admin)->get($formulario)->getContent();
        $this->assertStringContainsString(self::MAX_MOTIVO, (string) $this->seccion($html, 'nueva-fecha'));
        $this->assertStringNotContainsString(self::MAX_MOTIVO, (string) $this->seccion($html, 'no-reagendar'));

        // A43 y A33: con la cita cerrada, el mensaje se ve una sola vez y no hay «No reagendar».
        $cerrada = $this->cita($this->d1, $this->p1, 'completada', self::MANANA);
        $mensaje = CambioDeEstadoNoPermitido::cerrada('completada')->getMessage();
        $this->actingAs($this->admin)->from("/admin/citas/{$cerrada}/reprogramar")
            ->patch("/admin/citas/{$cerrada}/reprogramar", ['fecha' => '2026-10-08', 'hora' => '11:00', 'formulario' => 'reprogramar']);
        $html = $this->actingAs($this->admin)->get("/admin/citas/{$cerrada}/reprogramar")->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, e($mensaje)), 'El mensaje de la cita cerrada se ve una sola vez.');
        $this->assertNull($this->seccion($html, 'no-reagendar'));
    }

    // ── Historial (CA7, TM11) ─────────────────────────────────────────

    public function test_el_historial_muestra_la_reprogramacion(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
        $this->entradaDeReprogramacion($cita, 'Lo pidió el paciente');

        $historial = $this->bloque($this->admin, "/admin/citas/{$cita}", 'historial');

        $this->assertStringContainsString('Reprogramada del 06/10/2026 10:00 al 07/10/2026 10:00', $historial);
        $this->assertStringContainsString('Confirmada', $historial);
        $this->assertStringContainsString('Agendada', $historial);
        $this->assertStringContainsString('Lo pidió el paciente', $historial);
        $this->assertMatchesRegularExpression('/class="historial-autor"[^>]*>\s*por Adela Administra/', $historial);
    }

    public function test_el_historial_tiene_su_encabezado(): void
    {
        $conEntradas = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
        $this->entradaDeReprogramacion($conEntradas, 'Motivo');
        $sinEntradas = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');

        foreach (['con entradas' => $conEntradas, 'sin entradas' => $sinEntradas] as $caso => $cita) {
            $html = $this->actingAs($this->admin)->get("/admin/citas/{$cita}")->assertOk()->getContent();
            $encabezados = (new DOMXPath($this->dom($html)))
                ->query("//*[@id='historial']//h2[normalize-space()='Historial de cambios']");
            $this->assertSame(1, $encabezados->length, "{$caso}: «Historial de cambios» es un encabezado (WCAG 1.3.1).");
        }
    }

    public function test_el_motivo_de_la_reprogramacion_se_muestra_escapado(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
        $this->entradaDeReprogramacion($cita, '<b>negrita</b>');

        $historial = $this->bloque($this->admin, "/admin/citas/{$cita}", 'historial');

        $this->assertStringContainsString('&lt;b&gt;negrita&lt;/b&gt;', $historial);
        $this->assertStringNotContainsString('<b>negrita', $historial);
        $this->assertStringContainsString('Reprogramada del', $historial);
    }

    // ── P9: sistema de diseño y WCAG 2.1 AA (A1, A31, A32, A37, A49) ──

    public function test_los_pares_de_contraste_cumplen_aa(): void
    {
        $tokens = $this->tokensDeColor();

        // Tabla «Pares de contraste» del plan.
        $texto = [
            ['--text', '--white'], ['--text', '--p50'], ['--white', '--p500'], ['--red', '--red-bg'],
            ['--red', '--white'], ['--red', '--p50'], ['--p600', '--white'],
            ['--p600', '--p100'], ['--blue', '--blue-bg'], ['--amber', '--amber-bg'], ['--text', '--green-bg'],
            ['--text-muted', '--p50'],
        ];
        $bordesYFoco = [
            ['--border-control', '--white'], ['--border-control', '--p50'], ['--red', '--white'], ['--red', '--p50'],
            ['--focus-ring', '--white'], ['--focus-ring', '--p50'],
        ];
        foreach ([[$texto, 4.5], [$bordesYFoco, 3.0]] as [$pares, $minimo]) {
            foreach ($pares as [$frente, $fondo]) {
                $this->assertArrayHasKey($frente, $tokens, "Falta el token {$frente}.");
                $this->assertArrayHasKey($fondo, $tokens, "Falta el token {$fondo}.");
                $contraste = $this->contraste($tokens[$frente], $tokens[$fondo]);
                $this->assertGreaterThanOrEqual($minimo, $contraste, sprintf('%s sobre %s: %.2f:1', $frente, $fondo, $contraste));
            }
        }

        // Cada color de las reglas nuevas, sobre el fondo de su propia regla o sobre --white y --p50.
        $comprobados = 0;
        foreach ($this->reglasCss() as [$selector, $cuerpo]) {
            if (! preg_match(self::AMBITO_NUEVO, $selector)) {
                continue;
            }
            $fondos = preg_match('/background(?:-color)?\s*:\s*var\((--[\w-]+)\)/', $cuerpo, $f) && isset($tokens[$f[1]])
                ? [$f[1]] : ['--white', '--p50'];
            $usos = [];
            if (preg_match_all('/(?<![-\w])color\s*:\s*([^;]+)/', $cuerpo, $m)) {
                $usos = array_merge($usos, array_map(fn ($v) => [$v, 4.5, $fondos], $m[1]));
            }
            if (preg_match_all('/(?:border(?:-color)?|outline(?:-color)?)\s*:\s*([^;]+)/', $cuerpo, $m)) {
                $usos = array_merge($usos, array_map(fn ($v) => [$v, 3.0, ['--white', '--p50']], $m[1]));
            }
            foreach ($usos as [$valor, $minimo, $sobre]) {
                preg_match_all('/var\((--[\w-]+)\)/', $valor, $vars);
                foreach (array_intersect($vars[1], array_keys($tokens)) as $color) {
                    foreach ($sobre as $fondo) {
                        $contraste = $this->contraste($tokens[$color], $tokens[$fondo]);
                        $this->assertGreaterThanOrEqual($minimo, $contraste, sprintf('%s usa %s sobre %s: %.2f:1', $selector, $color, $fondo, $contraste));
                        $comprobados++;
                    }
                }
            }
        }
        $this->assertGreaterThanOrEqual(12, $comprobados, 'Deben comprobarse los colores de las reglas nuevas.');
    }

    public function test_las_reglas_css_nuevas_solo_usan_tokens(): void
    {
        // Control positivo: el comprobador detecta los literales.
        $this->assertCount(4, $this->literales('padding: 6px; color: #fff; background: rgb(0,0,0); font-weight: 600;'));
        $this->assertSame([], $this->literales('font-family: inherit; gap: var(--space-3);'), 'A53: inherit no es un literal.');

        $nuevas = 0;
        foreach ($this->reglasCss() as [$selector, $cuerpo]) {
            if (! preg_match(self::AMBITO_NUEVO, $selector)) {
                continue;
            }
            $nuevas++;
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
        }
        $this->assertGreaterThanOrEqual(10, $nuevas, 'app.css debe tener las reglas nuevas de la 028.');

        foreach (['.acciones-columna', '.acciones-fila', '.form-en-linea', '.aviso-plazo', '.historial-lista', '.historial-vacio',
            '.historial-estado-anterior', '.historial-estado-nuevo', '.historial-autor', '.timeline-time'] as $clase) {
            $this->assertNotSame('', $this->declaraciones(".formulario-accesible {$clase}"), "Falta .formulario-accesible {$clase} (A48, A49).");
        }
        $this->assertMatchesRegularExpression('/border-color\s*:\s*var\(--border-control\)/', $this->declaraciones('.formulario-accesible .field:focus'));
        $this->assertMatchesRegularExpression('/font-family\s*:\s*inherit/', $this->declaraciones('.formulario-accesible .field'), 'A53.');
        $this->assertStringContainsString('width: 48px', $this->declaraciones('.timeline-time'), 'A49: la regla global no cambia.');
    }

    public function test_los_controles_tienen_foco_visible(): void
    {
        foreach (['.formulario-accesible .field:focus-visible', '.formulario-accesible .btn:focus-visible',
            '.formulario-accesible a:focus-visible', '.btn-danger:focus-visible'] as $selector) {
            $this->assertMatchesRegularExpression('/outline\s*:[^;]*var\(--focus-ring\)/', $this->declaraciones($selector), "{$selector} con --focus-ring.");
        }
    }

    public function test_el_estado_completada_se_ve_con_contraste_aa(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'completada', self::MANANA);

        $html = $this->actingAs($this->admin)->get("/admin/citas/{$cita}/reprogramar")->assertOk()->getContent();
        $badge = (new DOMXPath($this->dom($html)))->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' formulario-accesible ')]//*[contains(@class, 'badge-green')]");

        $this->assertSame(1, $badge->length, 'El badge «Completada» va dentro de .formulario-accesible.');
        $this->assertMatchesRegularExpression('/(?<![-\w])color\s*:\s*var\(--text\)/', $this->declaraciones('.formulario-accesible .badge-green'));
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_personal(): void
    {
        $this->assertFuentesSinLiterales(['citas/partials/reprogramar.blade.php', 'citas/reprogramar.blade.php']);

        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);
        $this->assertSinEstilosEnLinea($this->bloque($this->admin, "/admin/citas/{$cita}/reprogramar", 'reprogramar-cita'));
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_paciente(): void
    {
        $this->assertFuentesSinLiterales(['citas/partials/reprogramar.blade.php', 'citas/cliente-reprogramar.blade.php']);

        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
        $this->assertSinEstilosEnLinea($this->bloque($this->paciente1, "/cliente/citas/{$cita}/reprogramar", 'reprogramar-cita'));
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_el_detalle_del_personal(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);
        $vacia = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-08 10:00');
        $this->entradaDeReprogramacion($cita, 'Motivo');

        foreach ([$cita, $vacia] as $id) {
            $html = $this->actingAs($this->admin)->get("/admin/citas/{$id}")->assertOk()->getContent();
            foreach (['acciones', 'historial'] as $bloque) {
                $this->assertDentroDelComponente($html, $bloque);
                $this->assertSinEstilosEnLinea((string) $this->seccion($html, $bloque));
            }

            // A52: #estado sigue y conserva su formulario hacia …/estado.
            $this->assertStringContainsString("/admin/citas/{$id}/estado", (string) $this->seccion($html, 'estado'));
        }
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_el_detalle_del_paciente(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        $html = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}")->assertOk()->getContent();

        $this->assertDentroDelComponente($html, 'acciones-cita');
        $acciones = (string) $this->seccion($html, 'acciones-cita');
        $this->assertSinEstilosEnLinea($acciones);
        $this->assertMatchesRegularExpression('/<button[^>]*class="[^"]*btn-danger/', $acciones, '«Cancelar» con .btn-danger.');
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_el_panel_del_paciente(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        $html = $this->actingAs($this->paciente1)->get('/cliente')->assertOk()->getContent();

        $this->assertDentroDelComponente($html, 'acciones-proxima');
        $acciones = (string) $this->seccion($html, 'acciones-proxima');
        $this->assertSinEstilosEnLinea($acciones);
        $this->assertMatchesRegularExpression('/<button[^>]*class="[^"]*btn-danger/', $acciones, '«Cancelar» con .btn-danger.');
    }

    public function test_lo_nuevo_solo_usa_el_sistema_en_la_lista_del_paciente(): void
    {
        $citas = [
            $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA),
            $this->cita($this->d1, $this->p1, 'confirmada', '2026-10-08 10:00'),
        ];

        $html = $this->actingAs($this->paciente1)->get('/cliente/citas')->assertOk()->getContent();

        foreach ($citas as $cita) {
            $this->assertDentroDelComponente($html, "acciones-{$cita}");
            $acciones = (string) $this->seccion($html, "acciones-{$cita}");
            $this->assertSinEstilosEnLinea($acciones);
            $this->assertMatchesRegularExpression('/<button[^>]*class="[^"]*btn-danger/', $acciones);
        }
    }

    // ── Errores asociados a su campo (RNF WCAG, A47) ──────────────────

    public function test_los_errores_del_personal_van_asociados_a_su_campo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::MANANA);

        $this->assertErroresAsociados($this->admin, "/admin/citas/{$cita}/reprogramar");
    }

    public function test_los_errores_del_paciente_van_asociados_a_su_campo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', self::FUTURA);

        $this->assertErroresAsociados($this->paciente1, "/cliente/citas/{$cita}/reprogramar");
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function assertErroresAsociados($usuario, string $formulario): void
    {
        $this->actingAs($usuario)->from($formulario)
            ->patch($formulario, ['fecha' => '06/10/2026', 'hora' => '25:00', 'formulario' => 'reprogramar']);
        $errores = ['fecha' => $this->errorDe('fecha'), 'hora' => $this->errorDe('hora')];
        $html = $this->actingAs($usuario)->get($formulario)->assertOk()->getContent();
        $xpath = new DOMXPath($this->dom($html));

        foreach ($errores as $campo => $mensaje) {
            $this->assertNotSame('', $mensaje, "Debe haber un error en {$campo}.");
            $input = $xpath->query("//*[@id='nueva-fecha']//input[@name='{$campo}']")->item(0);
            $this->assertInstanceOf(DOMElement::class, $input, "Falta el campo {$campo}.");
            $this->assertSame('true', $input->getAttribute('aria-invalid'), "{$campo}: aria-invalid.");
            $descrito = $input->getAttribute('aria-describedby');
            $this->assertNotSame('', $descrito, "{$campo}: aria-describedby.");
            $error = $xpath->query("//*[@id='{$descrito}']")->item(0);
            $this->assertInstanceOf(DOMElement::class, $error, "{$campo}: el error descrito existe.");
            $this->assertSame($mensaje, trim($error->textContent));
            $this->assertSame('alert', $error->getAttribute('role'), "{$campo}: el error se anuncia (A47).");
        }
    }

    private function entradaDeReprogramacion(string $cita, string $motivo): void
    {
        DB::table('historial_citas')->insert([
            'id' => (string) Str::uuid(),
            'cita_id' => $cita,
            'cambiado_por' => $this->admin->id,
            'estado_anterior' => 'confirmada',
            'estado_nuevo' => 'agendada',
            'fecha_hora_anterior' => '2026-10-06 10:00:00',
            'fecha_hora_nueva' => '2026-10-07 10:00:00',
            'motivo' => $motivo,
            'fecha' => now(),
        ]);
    }

    /** HTML del bloque con ese `id` en la página; falla si no existe. */
    private function bloque($usuario, string $url, string $id): string
    {
        $html = $this->actingAs($usuario)->get($url)->assertOk()->getContent();
        $bloque = $this->seccion($html, $id);
        $this->assertNotNull($bloque, "{$url} no tiene #{$id}.");

        return $bloque;
    }

    private function assertDentroDelComponente(string $html, string $id): void
    {
        $nodos = (new DOMXPath($this->dom($html)))->query(
            "//*[@id='{$id}']/ancestor-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' formulario-accesible ')]",
        );
        $this->assertGreaterThan(0, $nodos->length, "#{$id} va dentro de .formulario-accesible.");
    }

    private function assertSinEstilosEnLinea(string $html): void
    {
        // Control positivo del comprobador.
        $this->assertMatchesRegularExpression('/\sstyle=/', '<div style="color:red">');
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/', $html, 'Sin estilos en línea.');
    }

    /** Las plantillas no llevan `style=` ni colores o medidas literales. */
    private function assertFuentesSinLiterales(array $vistas): void
    {
        foreach ($vistas as $vista) {
            $ruta = resource_path("views/{$vista}");
            $this->assertFileExists($ruta);
            $fuente = file_get_contents($ruta);
            $this->assertStringNotContainsString('style=', $fuente, "{$vista}: sin style=.");
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b|rgba?\(|\d(\.\d+)?px/i', $fuente, "{$vista}: sin literales.");
        }
    }

    private function assertIdsUnicos(string $html): void
    {
        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        $repetidos = array_keys(array_filter(array_count_values($ids[1]), fn ($n) => $n > 1));
        $this->assertSame([], $repetidos, 'Sin id duplicados.');
    }

    /** @return list<string> literales que no son tokens en unas declaraciones CSS. */
    private function literales(string $cuerpo): array
    {
        $hallados = [];
        if (preg_match_all('/\d(\.\d+)?px/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, $m[0]);
        }
        if (preg_match_all('/#[0-9a-f]{3,8}\b|rgba?\(/i', $cuerpo, $m)) {
            $hallados = array_merge($hallados, $m[0]);
        }
        if (preg_match_all('/font-weight\s*:\s*([^;]+)/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, array_filter($m[1], fn ($v) => ! str_starts_with(trim($v), 'var(--weight-')));
        }
        if (preg_match_all('/font-family\s*:\s*([^;]+)/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, array_filter($m[1], fn ($v) => trim($v) !== 'inherit'));
        }

        return $hallados;
    }

    /** La casilla `confirmar` de «No reagendar», con su etiqueta asociada. */
    private function casillaDeConfirmar(string $seccion): DOMElement
    {
        $xpath = new DOMXPath($this->dom($seccion));
        $casilla = $xpath->query("//input[@type='checkbox'][@name='confirmar'][@value='1']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $casilla, 'Falta la casilla de confirmar.');
        $id = $casilla->getAttribute('id');
        $this->assertNotSame('', $id, 'La casilla necesita un id para su etiqueta.');
        $etiqueta = $xpath->query("//label[@for='{$id}']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $etiqueta, 'La casilla no tiene etiqueta asociada.');
        $this->assertSame('Entiendo que la cancelación no se puede deshacer', trim(preg_replace('/\s+/u', ' ', $etiqueta->textContent)));

        return $casilla;
    }

    /** El error de la casilla se pinta en su sección y va asociado a ella. */
    private function assertErrorDeLaCasilla(string $seccion): void
    {
        $casilla = $this->casillaDeConfirmar($seccion);
        $this->assertSame('true', $casilla->getAttribute('aria-invalid'), 'La casilla se marca como inválida.');
        $descrito = $casilla->getAttribute('aria-describedby');
        $this->assertNotSame('', $descrito, 'La casilla apunta a su error.');
        $error = (new DOMXPath($this->dom($seccion)))->query("//*[@id='{$descrito}']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $error, 'El error de la casilla está en su sección.');
        $this->assertSame(self::CONFIRMAR, trim($error->textContent));
    }

    private function dom(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return $dom;
    }

    /** @return list<array{0: string, 1: string}> reglas de primer nivel de app.css: [selector, declaraciones] */
    private function reglasCss(): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path('css/app.css')));
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $coincidencias, PREG_SET_ORDER);

        return array_map(fn ($m) => [trim($m[1]), $m[2]], $coincidencias);
    }

    /** Declaraciones de las reglas cuyo selector (o uno de su lista) es exactamente `$selector`. */
    private function declaraciones(string $selector): string
    {
        $cuerpos = [];
        foreach ($this->reglasCss() as [$selectores, $cuerpo]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if (in_array($selector, $lista, true)) {
                $cuerpos[] = $cuerpo;
            }
        }

        return implode("\n", $cuerpos);
    }

    /** Tokens de color de `:root` (hex), resolviendo `var()`. */
    private function tokensDeColor(): array
    {
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $root[1] ?? '', $pares, PREG_SET_ORDER);
        $crudos = [];
        foreach ($pares as [, $nombre, $valor]) {
            $crudos[$nombre] = trim($valor);
        }

        $resolver = function (string $valor, int $profundidad = 0) use (&$resolver, $crudos): ?string {
            if (preg_match('/^var\((--[\w-]+)\)$/', $valor, $m) && $profundidad < 5) {
                return isset($crudos[$m[1]]) ? $resolver($crudos[$m[1]], $profundidad + 1) : null;
            }

            return preg_match('/^#[0-9a-f]{6}$/i', $valor) ? $valor : null;
        };

        return array_filter(array_map($resolver, $crudos));
    }

    /** Contraste WCAG 2.x entre dos colores hex. */
    private function contraste(string $a, string $b): float
    {
        $luminancia = function (string $hex): float {
            $canales = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
            $lineal = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $canales);

            return 0.2126 * $lineal[0] + 0.7152 * $lineal[1] + 0.0722 * $lineal[2];
        };
        [$claro, $oscuro] = [max($luminancia($a), $luminancia($b)), min($luminancia($a), $luminancia($b))];

        return ($claro + 0.05) / ($oscuro + 0.05);
    }
}
