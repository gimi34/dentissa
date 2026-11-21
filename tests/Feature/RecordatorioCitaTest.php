<?php

namespace Tests\Feature;

use App\Mail\RecordatorioCita;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
}
