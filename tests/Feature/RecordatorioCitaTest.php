<?php

namespace Tests\Feature;

use App\Mail\RecordatorioCita;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Support\CapturaLogs;
use Tests\TestCase;

/**
 * Spec 052: la revisión periódica que envía el recordatorio de cita por correo (CA1–CA6, CA8, CA11;
 * TM1, TM4–TM6, TM8).
 *
 * El reloj se fija en la hora de la clínica (miércoles 25 de noviembre de 2026, 10:30) y las citas se
 * crean con el escenario de la spec 026 (P1 de D1, P2 de D2, cada uno con su cuenta de cliente).
 */
class RecordatorioCitaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    /** «Ahora» en la clínica para todos los casos. */
    private const AHORA = '2026-11-25 10:30';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Mail::fake();
        config(['recordatorios.activo' => true]);
        $this->travelTo(CarbonImmutable::parse(self::AHORA.':00', config('clinica.zona_horaria')));
    }

    /** La hora local de la clínica, desplazada desde «ahora», como 'Y-m-d H:i'. */
    private function dentroDe(string $desplazamiento): string
    {
        return CarbonImmutable::parse(self::AHORA, config('clinica.zona_horaria'))->modify($desplazamiento)->format('Y-m-d H:i');
    }

    private function revisar(): void
    {
        Artisan::call('dentissa:recordatorios');
    }

    private function marcaDe(string $cita): int
    {
        return (int) DB::table('citas')->where('id', $cita)->value('recordatorio_enviado');
    }

    /** CA1: a 24 h 1 min todavía no sale; a 23 h 59 min sale un único recordatorio. */
    public function test_sale_cuando_faltan_24_horas(): void
    {
        $lejana = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+24 hours +1 minute'));
        $cercana = $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+23 hours +59 minutes'));

        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
        Mail::assertSent(RecordatorioCita::class, fn ($correo) => $correo->hasTo($this->paciente2->email));
        $this->assertSame(1, $this->marcaDe($cercana), 'La cita a 23 h 59 min queda avisada.');
        $this->assertSame(0, $this->marcaDe($lejana), 'La de 24 h 1 min aún no.');
    }

    /** CA1, TM1: el correo va a la cuenta del paciente de la cita, con sus datos y no los de otro. */
    public function test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos(): void
    {
        $this->cita($this->d1, $this->p1, 'confirmada', $this->dentroDe('+2 hours'));
        $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+30 hours'));

        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
        Mail::assertSent(RecordatorioCita::class, function ($correo) {
            $html = $correo->render();

            return $correo->hasTo($this->paciente1->email)
                && ! $correo->hasTo($this->paciente2->email)
                && str_contains($html, 'Pablo') && ! str_contains($html, 'Paula')
                && str_contains($html, 'Diego Primero') && ! str_contains($html, 'Dora Segunda');
        });
    }

    /** CA2: una cita creada cuando faltan menos de 24 h sale en la revisión siguiente. */
    public function test_una_cita_a_menos_de_24_horas_sale_en_la_siguiente_revision(): void
    {
        $this->revisar();
        Mail::assertNothingSent();

        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+3 hours'));
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
        $this->assertSame(1, $this->marcaDe($cita));
    }

    /** CA2, CA5: si la cita ya empezó, no se envía. */
    public function test_una_cita_que_ya_empezo_no_recibe_recordatorio(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('-5 minutes'));

        $this->revisar();

        Mail::assertNothingSent();
        $this->assertSame(0, $this->marcaDe($cita));
    }

    /** CA5: solo agendadas y confirmadas. */
    public function test_solo_agendadas_y_confirmadas_reciben_recordatorio(): void
    {
        $citas = [];
        foreach (['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'] as $i => $estado) {
            $citas[$estado] = $this->cita($this->d1, $this->p1, $estado, $this->dentroDe('+'.($i + 2).' hours'));
        }

        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 2);
        foreach ($citas as $estado => $cita) {
            $this->assertSame(in_array($estado, ['agendada', 'confirmada'], true) ? 1 : 0, $this->marcaDe($cita), "Cita {$estado}.");
        }
    }

    /** CA6: una cuenta desactivada no recibe recordatorio. */
    public function test_una_cuenta_desactivada_no_recibe_recordatorio(): void
    {
        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['activo' => 0]);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));

        $this->revisar();

        Mail::assertNothingSent();
        $this->assertSame(0, $this->marcaDe($cita));
    }

    /** Spec 054 (decisión del usuario): una cuenta sin correo se salta, sin fallo ni registro. */
    public function test_una_cuenta_sin_correo_se_salta_sin_registro(): void
    {
        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['email' => null]);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $logs = CapturaLogs::instalar();

        $this->revisar();

        Mail::assertNothingSent();
        $this->assertSame(0, $this->marcaDe($cita));
        $this->assertSame('', $logs->comoTexto(), 'Sin ninguna línea en el registro técnico.');
    }

    /** Interruptor: apagado, la revisión no hace nada. */
    public function test_apagado_no_envia_nada(): void
    {
        config(['recordatorios.activo' => false]);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));

        $this->revisar();

        Mail::assertNothingSent();
        $this->assertSame(0, $this->marcaDe($cita));
    }

    /** Interruptor (D6, decisión del usuario): sin la variable de entorno, sale apagado. */
    public function test_apagado_por_defecto(): void
    {
        $antes = getenv('RECORDATORIOS_CORREO');
        putenv('RECORDATORIOS_CORREO');
        unset($_ENV['RECORDATORIOS_CORREO'], $_SERVER['RECORDATORIOS_CORREO']);

        try {
            $configuracion = require config_path('recordatorios.php');
            $this->assertFalse($configuracion['activo']);
            $this->assertSame(250, $configuracion['tope_diario']);
            $this->assertSame(24, $configuracion['antelacion_horas']);
        } finally {
            if ($antes !== false) {
                putenv("RECORDATORIOS_CORREO={$antes}");
                $_ENV['RECORDATORIOS_CORREO'] = $_SERVER['RECORDATORIOS_CORREO'] = $antes;
            }
        }
    }

    // ── Envío: sin duplicados, reprogramación, fallos, tope y registros (T011) ───────────────────

    /** CA3: una segunda revisión no reenvía. */
    public function test_una_segunda_revision_no_reenvia(): void
    {
        $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));

        $this->revisar();
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
    }

    /**
     * CA3, TM6 (/analyze A3): si otra revisión reclama la cita entre la lectura y el reclamo, esta no
     * envía nada. Se simula marcando la cita justo después de que la revisión lee las candidatas.
     */
    public function test_dos_revisiones_no_envian_dos_veces(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $hecho = false;
        DB::listen(function (QueryExecuted $consulta) use ($cita, &$hecho) {
            if (! $hecho && str_starts_with(ltrim(strtolower($consulta->sql)), 'select') && str_contains($consulta->sql, 'recordatorio_enviado')) {
                $hecho = true;
                // Otra revisión la reclama y, después, el personal edita sus notas internas (cambia `actualizado_en`).
                DB::table('citas')->where('id', $cita)->update(['recordatorio_enviado' => 1, 'notas_internas' => 'Editada tras el reclamo', 'actualizado_en' => '2026-11-25 10:31:00']);
            }
        });

        $this->revisar();

        $this->assertTrue($hecho, 'Control: la revisión leyó las candidatas.');
        Mail::assertNothingSent();
    }

    /** CA4: reprogramar reinicia la marca y sale el recordatorio de la fecha nueva. */
    public function test_reprogramar_reinicia_el_recordatorio_y_sale_otro(): void
    {
        $this->conHorarios();
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $this->revisar();
        $this->assertSame(1, $this->marcaDe($cita));

        $this->reprogramar($this->admin, $cita, '2026-11-26', '11:00')->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertSame(0, $this->marcaDe($cita), 'La marca ya no vale para la fecha nueva.');

        $this->travel(1)->hours();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 2);
        $this->assertSame(1, $this->marcaDe($cita));
    }

    /** CA5: cancelar después del recordatorio no genera ningún otro correo. */
    public function test_cancelar_despues_del_recordatorio_no_envia_otro_correo(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $this->revisar();

        $this->cambiar($this->admin, $cita, 'cancelada', 'El paciente avisó')->assertStatus(302);
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
    }

    /** CA8, TM8: un fallo del correo no marca la cita y la revisión siguiente lo reintenta. */
    public function test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $logs = CapturaLogs::instalar();
        Mail::shouldReceive('to')->andThrow(new TransportException('Fallo SMTP con '.$this->paciente1->email));

        $this->revisar();

        $this->assertSame(0, $this->marcaDe($cita), 'La cita no queda como avisada.');
        $fallos = array_values(array_filter($logs->registros(), fn ($r) => $r['mensaje'] === 'recordatorio.correo_fallido'));
        $this->assertCount(1, $fallos);
        $this->assertSame('error', $fallos[0]['nivel']);
        $this->assertSame(['error' => 'TransportException'], $fallos[0]['contexto']);

        // El mock de Mail sustituyó al gestor de correo: se olvida antes de volver a la falsificación.
        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 1);
        $this->assertSame(1, $this->marcaDe($cita));
    }

    /**
     * CA8, P8 (/analyze A2): un error al reclamar una cita (p. ej. de la base) se captura sin datos de la
     * cita, la cita no queda avisada y la revisión sigue con las demás.
     */
    public function test_un_error_al_reclamar_no_deja_datos_en_el_registro(): void
    {
        $fallida = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $otra = $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+3 hours'));
        $fecha = (string) DB::table('citas')->where('id', $fallida)->value('fecha_hora');
        $logs = CapturaLogs::instalar();
        $lanzado = false;
        DB::listen(function (QueryExecuted $consulta) use ($fallida, $fecha, &$lanzado) {
            if (! $lanzado && str_starts_with(ltrim(strtolower($consulta->sql)), 'update') && in_array($fallida, $consulta->bindings, true)) {
                $lanzado = true;
                throw new \RuntimeException("Deadlock al actualizar la cita {$fallida} del {$fecha}");
            }
        });

        $this->revisar();

        $this->assertTrue($lanzado, 'Control: el reclamo de la cita falló.');
        Mail::assertSent(RecordatorioCita::class, 1);
        Mail::assertSent(RecordatorioCita::class, fn ($correo) => $correo->hasTo($this->paciente2->email));
        $this->assertSame(0, $this->marcaDe($fallida), 'La cita que falló no queda avisada.');
        $this->assertSame(1, $this->marcaDe($otra));
        $texto = $logs->comoTexto();
        $this->assertStringContainsString('recordatorio.correo_fallido', $texto);
        $this->assertStringNotContainsString($fallida, $texto);
        $this->assertStringNotContainsString(substr($fecha, 0, 10), $texto);
    }

    /** CA11, TM4 (/analyze A4): el tope del día aplaza el exceso al día siguiente y avisa una sola vez. */
    public function test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente(): void
    {
        config(['recordatorios.tope_diario' => 2]);
        $primera = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $segunda = $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+3 hours'));
        $tercera = $this->cita($this->d1, $this->p1, 'confirmada', $this->dentroDe('+20 hours'));
        $logs = CapturaLogs::instalar();

        $this->revisar();
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 2);
        $this->assertSame([1, 1, 0], [$this->marcaDe($primera), $this->marcaDe($segunda), $this->marcaDe($tercera)], 'Salen las dos más cercanas.');
        $topes = array_filter($logs->registros(), fn ($r) => $r['mensaje'] === 'recordatorio.tope_diario');
        $this->assertCount(1, $topes, 'Una sola línea al día aunque la revisión siga corriendo.');

        $this->travelTo(CarbonImmutable::parse('2026-11-26 00:05:00', config('clinica.zona_horaria')));
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 3);
        $this->assertSame(1, $this->marcaDe($tercera), 'Al día siguiente sale la aplazada.');
    }

    /** P8, CA8, CA11, TM5: los tres eventos técnicos, solo con contadores o la clase del error. */
    public function test_los_registros_no_llevan_datos_personales(): void
    {
        config(['recordatorios.tope_diario' => 1]);
        $enviada = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        // A 20 h: al día siguiente (00:05) sigue sin empezar y su envío falla.
        $aplazada = $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+20 hours'));
        $servicio = DB::table('servicios')->first();
        DB::table('citas')->whereIn('id', [$enviada, $aplazada])->update(['servicio_id' => $servicio->id]);
        $logs = CapturaLogs::instalar();

        $this->revisar();
        $this->travelTo(CarbonImmutable::parse('2026-11-26 00:05:00', config('clinica.zona_horaria')));
        Mail::shouldReceive('to')->andThrow(new TransportException('Fallo SMTP con '.$this->paciente2->email));
        $this->revisar();

        $porMensaje = [];
        foreach ($logs->registros() as $registro) {
            $porMensaje[$registro['mensaje']][] = $registro['contexto'];
        }
        $this->assertSame(['tope' => 1], $porMensaje['recordatorio.tope_diario'][0] ?? null);
        $this->assertSame(['error' => 'TransportException'], $porMensaje['recordatorio.correo_fallido'][0] ?? null);
        $this->assertNotEmpty($porMensaje['recordatorio.revision'] ?? []);
        foreach ($porMensaje['recordatorio.revision'] as $contexto) {
            $this->assertSame(['enviados', 'fallidos', 'aplazados'], array_keys($contexto));
        }

        $texto = $logs->comoTexto();
        foreach ([$this->paciente1->email, $this->paciente2->email, 'Pablo', 'Paula', 'Diego', 'Dora', $enviada, $aplazada, '2026-11-25', '12:30', $servicio->nombre] as $dato) {
            $this->assertStringNotContainsString($dato, $texto, "El registro no lleva «{$dato}».");
        }
    }

    /** D2: marcar la cita no cambia su fecha de modificación (la columna tiene ON UPDATE). */
    public function test_la_marca_no_cambia_actualizado_en(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        DB::table('citas')->where('id', $cita)->update(['actualizado_en' => '2026-11-20 08:00:00']);

        $this->revisar();

        $this->assertSame(1, $this->marcaDe($cita));
        $this->assertSame('2026-11-20 08:00:00', (string) DB::table('citas')->where('id', $cita)->value('actualizado_en'));
    }

    /** CA1, CA2, RNF de entrega: la revisión está programada cada minuto y sin solaparse. */
    public function test_la_revision_corre_cada_minuto(): void
    {
        $eventos = array_values(array_filter(
            app(Schedule::class)->events(),
            fn ($evento) => str_contains((string) $evento->command, 'dentissa:recordatorios'),
        ));

        $this->assertCount(1, $eventos);
        $this->assertSame('* * * * *', $eventos[0]->expression);
        $this->assertTrue($eventos[0]->withoutOverlapping);
    }

    // ── Correcciones de /review r1 (T027) ─────────────────────────────────────────────────────────

    /** R1: si el proceso muere a mitad de una revisión, el bloqueo no deja sin recordatorios todo un día. */
    public function test_el_bloqueo_de_la_revision_caduca_pronto(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($evento) => str_contains((string) $evento->command, 'dentissa:recordatorios'));

        $this->assertNotNull($evento);
        $this->assertSame(10, $evento->expiresAt, 'El bloqueo caduca a los 10 minutos.');
    }

    /** R2, CA8: con el correo caído, una revisión hace un solo intento y aplaza las demás citas. */
    public function test_con_el_correo_caido_se_intenta_una_vez_por_revision(): void
    {
        $primera = $this->cita($this->d1, $this->p1, 'agendada', $this->dentroDe('+2 hours'));
        $segunda = $this->cita($this->d2, $this->p2, 'agendada', $this->dentroDe('+3 hours'));
        $logs = CapturaLogs::instalar();
        Mail::shouldReceive('to')->once()->andThrow(new TransportException('Conexión rechazada'));

        $this->revisar();

        $this->assertSame([0, 0], [$this->marcaDe($primera), $this->marcaDe($segunda)], 'Ninguna queda avisada.');
        $registros = $logs->registros();
        $fallos = array_filter($registros, fn ($r) => $r['mensaje'] === 'recordatorio.correo_fallido');
        $this->assertCount(1, $fallos, 'Un solo intento y un solo fallo por revisión.');
        $revision = array_values(array_filter($registros, fn ($r) => $r['mensaje'] === 'recordatorio.revision'));
        $this->assertSame([['enviados' => 0, 'fallidos' => 1, 'aplazados' => 1]], array_column($revision, 'contexto'));

        // Con el correo de vuelta, la revisión siguiente envía las dos.
        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();
        $this->travel(1)->minutes();
        $this->revisar();

        Mail::assertSent(RecordatorioCita::class, 2);
        $this->assertSame([1, 1], [$this->marcaDe($primera), $this->marcaDe($segunda)]);
    }
}
