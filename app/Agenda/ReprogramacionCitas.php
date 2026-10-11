<?php

namespace App\Agenda;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reprogramación de una cita (spec 028): mueve una cita agendada o confirmada que aún no
 * empezó a otra fecha y hora libres, con el mismo paciente, dentista, servicio y duración.
 *
 * Reutiliza las dos reglas que ya viven en un solo sitio: el hueco de `ReservaCitas`
 * (spec 025) y los estados y el plazo del paciente de `EstadosCita` (spec 026). Quién puede
 * reprogramar lo decide el Gate `reprogramar-cita` antes de llamar aquí.
 */
class ReprogramacionCitas
{
    /** Bloqueo agotado (1205) e interbloqueo (1213) de MariaDB. */
    private const ERRORES_DE_BLOQUEO = [1205, 1213];

    private const BLOQUEO_AGOTADO = 1205;

    public function __construct(
        private ConnectionInterface $db,
        private ReservaCitas $reservas,
        private EstadosCita $estados,
    ) {}

    /**
     * Reglas de entrada: fecha y hora como al agendar (spec 025) y motivo opcional.
     */
    public static function reglasDeEntrada(): array
    {
        $reserva = ReservaCitas::reglasDeEntrada();

        return [
            'fecha' => $reserva['fecha'],
            'hora' => $reserva['hora'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes en español de las reglas de entrada (spec 028, A8).
     */
    public static function mensajesDeEntrada(): array
    {
        $reserva = ReservaCitas::mensajesDeEntrada();

        return [
            'fecha.required' => 'Elige una fecha.',
            'fecha.date_format' => $reserva['fecha.date_format'],
            'hora.required' => 'Elige una hora.',
            'hora.date_format' => $reserva['hora.date_format'],
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.',
        ];
    }

    /**
     * Mueve la cita a la nueva fecha y hora (hora de la clínica) y deja una sola entrada de
     * historial con las fechas anterior y nueva.
     *
     * Bloquea primero al dentista, como la reserva, y después la cita: una reprogramación y un
     * agendado del mismo dentista se procesan en fila (D3). No debe llamarse dentro de otra
     * transacción: ahí un interbloqueo llega como DeadlockException (025/R9).
     *
     * @throws CitaNoAgendable
     * @throws CambioDeEstadoNoPermitido
     */
    public function reprogramar(string $citaId, string $fecha, string $hora, Usuario $actor, ?string $motivo): void
    {
        try {
            $this->db->transaction(fn () => $this->reprogramarConBloqueo($citaId, $fecha, $hora, $actor, $motivo));
        } catch (DeadlockException) {
            throw CitaNoAgendable::noDisponible();
        } catch (QueryException $e) {
            if (in_array($e->errorInfo[1] ?? null, self::ERRORES_DE_BLOQUEO, true)) {
                throw CitaNoAgendable::noDisponible();
            }

            throw $e;
        }
    }

    private function reprogramarConBloqueo(string $citaId, string $fecha, string $hora, Usuario $actor, ?string $motivo): void
    {
        $dentistaId = $this->db->table('citas')->where('id', $citaId)->value('dentista_id');

        if ($dentistaId === null) {
            throw new NotFoundHttpException;
        }

        $this->reservas->bloquearDentista($dentistaId);
        $cita = $this->bloquearCita($citaId);

        // Cerrada, empezada, dentista inactivo, sin duración o (paciente) fuera de plazo; con las
        // filas ya bloqueadas (/review 028: R7, R10, R19).
        $this->estados->comprobarReprogramable($cita, $actor);

        // «Ya pasó» se decide con la hora de la clínica (D13); el hueco, con la hora local sin
        // zona, como se guarda `fecha_hora` y como lo comprueba la reserva (A42). La hora elegida
        // va al minuto, así que «igual» compara al minuto aunque `fecha_hora` tenga segundos (R9).
        $instante = Carbon::createFromFormat('Y-m-d H:i', $fecha.' '.$hora, config('clinica.zona_horaria'))->startOfMinute();
        if ($instante->equalTo($this->estados->inicio($cita)->startOfMinute())) {
            throw CitaNoAgendable::mismaFechaYHora();
        }
        if ($instante->lessThanOrEqualTo(Carbon::now())) {
            throw CitaNoAgendable::enElPasado();
        }

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $fecha.' '.$hora)->startOfMinute();
        $fin = $inicio->copy()->addMinutes((int) $cita->duracion_min);
        $this->reservas->comprobarHueco($cita->dentista_id, $inicio, $fin, $cita->id);

        $ahora = Carbon::now();
        $this->db->table('citas')->where('id', $cita->id)->update([
            'fecha_hora' => $inicio,
            'estado' => 'agendada',
            'actualizado_en' => $ahora,
        ]);
        $this->db->table('historial_citas')->insert([
            'id' => (string) Str::uuid(),
            'cita_id' => $cita->id,
            'cambiado_por' => $actor->id,
            'estado_anterior' => $cita->estado,
            'estado_nuevo' => 'agendada',
            'fecha_hora_anterior' => $cita->fecha_hora,
            'fecha_hora_nueva' => $inicio,
            'motivo' => ($motivo === null || $motivo === '') ? null : $motivo,
            'fecha' => $ahora,
        ]);
    }

    /**
     * Bloquea la cita. Si la espera se agota aquí, la tiene un cambio de estado en curso:
     * el mensaje es «ocupada» y no «no disponible», que culparía al horario (D15).
     */
    private function bloquearCita(string $citaId): object
    {
        try {
            $cita = $this->db->table('citas')
                ->where('id', $citaId)
                ->lockForUpdate()
                ->first(['id', 'dentista_id', 'estado', 'fecha_hora', 'duracion_min']);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === self::BLOQUEO_AGOTADO) {
                throw CambioDeEstadoNoPermitido::ocupada();
            }

            throw $e;
        }

        if ($cita === null) {
            throw new NotFoundHttpException;
        }

        return $cita;
    }
}
