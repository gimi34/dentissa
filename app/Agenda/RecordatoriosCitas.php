<?php

namespace App\Agenda;

use App\Mail\RecordatorioCita;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Spec 052: la revisión periódica que envía el recordatorio de cita por correo (CA1–CA3, CA5, CA6, CA8,
 * CA11; plan D2, D3, D5, D6). La lanza `dentissa:recordatorios` cada minuto.
 *
 * - Candidatas (D3): citas agendadas o confirmadas sin recordatorio, que empiezan en las próximas 24 h
 *   (hora de la clínica) y aún no han empezado, de pacientes con cuenta activa y con correo (las cuentas
 *   sin correo de la spec 054 se saltan sin más). Solo se leen los datos de CA1: nunca notas ni expediente
 *   (CA10, TM3).
 * - Reclamo (D2, TM6): antes de enviar, un `UPDATE` condicional pasa la marca de 0 a 1; si no afecta a una
 *   fila, otra revisión ya la tomó o la cita cambió, y no se envía. Repite `actualizado_en` para que la
 *   marca no cambie la fecha de modificación de la cita (la columna tiene ON UPDATE).
 * - Fallos (CA8, TM8, /analyze A2): cualquier error al tratar una cita (reclamo, envío) libera el reclamo,
 *   deja `recordatorio.correo_fallido` con la clase del error y la revisión sigue con las demás; si el fallo
 *   es del servicio de correo, la revisión se corta y las demás quedan aplazadas (/review R2).
 * - Tope (CA11, TM4, D5): como mucho `recordatorios.tope_diario` por día de la clínica, contados con el
 *   RateLimiter; el exceso espera al día siguiente y `recordatorio.tope_diario` se escribe una vez al día
 *   (/analyze A4).
 * - Registros (P8, TM5): solo contadores o la clase del error; ni correos, ni nombres, ni fechas, ni ids.
 */
final class RecordatoriosCitas
{
    private const ESTADOS = ['agendada', 'confirmada'];

    /** @return array{enviados: int, fallidos: int, aplazados: int} */
    public function enviarPendientes(): array
    {
        $cuenta = ['enviados' => 0, 'fallidos' => 0, 'aplazados' => 0];
        if (! config('recordatorios.activo')) {
            return $cuenta;
        }

        $ahora = RelojClinica::ahora();
        $dia = $ahora->format('Y-m-d');
        $claveTope = "recordatorios:dia:{$dia}";
        $tope = (int) config('recordatorios.tope_diario');
        $hastaManana = (int) $ahora->diffInSeconds($ahora->endOfDay()) + 3600;

        $candidatas = $this->candidatas($ahora->format('Y-m-d H:i:s'), $ahora->addHours((int) config('recordatorios.antelacion_horas'))->format('Y-m-d H:i:s'));

        foreach ($candidatas as $posicion => $cita) {
            if (RateLimiter::attempts($claveTope) >= $tope) {
                $cuenta['aplazados'] = $candidatas->count() - $posicion;
                if (Cache::add("recordatorios:tope-avisado:{$dia}", true, $hastaManana)) {
                    Log::warning('recordatorio.tope_diario', ['tope' => $tope]);
                }
                break;
            }

            try {
                if (! $this->reclamar($cita)) {
                    continue;
                }
                Mail::to($cita->email)->send(new RecordatorioCita(
                    nombre: $cita->paciente_nombre,
                    fechaHora: (string) $cita->fecha_hora,
                    dentista: trim("{$cita->dentista_nombre} {$cita->dentista_apellido}"),
                    servicio: $cita->servicio,
                ));
                RateLimiter::hit($claveTope, $hastaManana);
                $cuenta['enviados']++;
            } catch (Throwable $e) {
                $this->liberar($cita);
                $cuenta['fallidos']++;
                Log::error('recordatorio.correo_fallido', ['error' => class_basename($e)]);

                // /review R2: si falla el servicio de correo, no se insiste con las demás citas en esta
                // revisión (un solo intento por minuto contra la cuenta de Gmail); quedan para la siguiente.
                if ($e instanceof TransportExceptionInterface) {
                    $cuenta['aplazados'] = $candidatas->count() - $posicion - 1;
                    break;
                }
            }
        }

        if (array_sum($cuenta) > 0) {
            Log::info('recordatorio.revision', $cuenta);
        }

        return $cuenta;
    }

    /** Las candidatas de D3, de la más cercana a la más lejana. */
    private function candidatas(string $ahora, string $limite)
    {
        return DB::table('citas')
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
            ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
            ->join('personal', 'citas.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->whereIn('citas.estado', self::ESTADOS)
            ->where('citas.recordatorio_enviado', 0)
            ->where('citas.fecha_hora', '>', $ahora)
            ->where('citas.fecha_hora', '<=', $limite)
            ->where('up.activo', 1)
            ->whereNotNull('up.email')
            ->where('up.email', '<>', '')
            ->orderBy('citas.fecha_hora')
            ->select(
                'citas.id',
                'citas.fecha_hora',
                'citas.actualizado_en',
                'up.email',
                'up.nombre as paciente_nombre',
                'ud.nombre as dentista_nombre',
                'ud.apellido as dentista_apellido',
                'servicios.nombre as servicio',
            )
            ->get()
            ->values();
    }

    /** D2: pasa la marca de 0 a 1 solo si la cita sigue igual que al leerla. */
    private function reclamar(object $cita): bool
    {
        return DB::table('citas')
            ->where('id', $cita->id)
            ->where('recordatorio_enviado', 0)
            ->where('fecha_hora', $cita->fecha_hora)
            ->whereIn('estado', self::ESTADOS)
            ->update(['recordatorio_enviado' => 1, 'actualizado_en' => $cita->actualizado_en]) === 1;
    }

    /** CA8: tras un fallo, la cita vuelve a quedar sin recordatorio para la revisión siguiente. */
    private function liberar(object $cita): void
    {
        try {
            DB::table('citas')
                ->where('id', $cita->id)
                ->where('recordatorio_enviado', 1)
                ->where('fecha_hora', $cita->fecha_hora)
                ->update(['recordatorio_enviado' => 0, 'actualizado_en' => $cita->actualizado_en]);
        } catch (Throwable) {
            // Si tampoco se puede liberar, la cita queda marcada; el fallo ya se registra sin datos.
        }
    }
}
