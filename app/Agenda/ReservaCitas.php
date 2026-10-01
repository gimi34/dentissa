<?php

namespace App\Agenda;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Regla de agenda única (spec 025): decide si un dentista puede atender una cita
 * (horario del día, encaje en la franja y solape) y la guarda, para el agendado
 * del personal y el del paciente.
 *
 * La comprobación y el guardado van en una transacción que bloquea primero la
 * fila del dentista, así que dos reservas del mismo dentista se procesan una
 * detrás de otra (CA8). Un bloqueo agotado o un interbloqueo se devuelven como
 * "no disponible", nunca como error del servidor.
 */
class ReservaCitas
{
    /** Bloqueo agotado (1205) e interbloqueo (1213) de MariaDB. */
    private const ERRORES_DE_BLOQUEO = [1205, 1213];

    /** Estados que liberan el hueco. */
    private const ESTADOS_LIBRES = ['cancelada', 'no_asistio'];

    public function __construct(
        private ConnectionInterface $db,
        private int $esperaBloqueoSegundos = 5,
    ) {}

    /**
     * Reglas de entrada comunes a los dos flujos: fecha, hora y dentista.
     */
    public static function reglasDeEntrada(): array
    {
        return [
            'fecha' => ['bail', 'required', 'date_format:Y-m-d'],
            'hora' => ['bail', 'required', 'date_format:H:i'],
            'dentista_id' => ['bail', 'required', Rule::exists('personal', 'id')->where(
                fn ($consulta) => $consulta->where('tipo', 'dentista')->whereIn(
                    'usuario_id',
                    fn ($activos) => $activos->select('id')->from('usuarios')->where('activo', 1),
                ),
            )],
        ];
    }

    /**
     * Mensajes en español de las reglas de entrada.
     */
    public static function mensajesDeEntrada(): array
    {
        return [
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM.',
            'dentista_id.exists' => 'Elige un dentista válido.',
        ];
    }

    /**
     * Valida la cita contra la agenda del dentista y la guarda en estado `agendada`.
     *
     * @param  array{paciente_id: string, dentista_id: string, servicio_id: ?string, fecha: string, hora: string, duracion_min: int, notas_paciente: ?string, notas_internas: ?string, creado_por: ?string}  $cita
     * @return string id de la cita
     *
     * @throws CitaNoAgendable
     */
    public function reservar(array $cita): string
    {
        try {
            return $this->db->transaction(fn () => $this->reservarConBloqueo($cita));
        } catch (DeadlockException) {
            // Dentro de una transacción anidada Laravel convierte 1205 y 1213 en DeadlockException.
            throw CitaNoAgendable::noDisponible();
        } catch (QueryException $e) {
            if (in_array($e->errorInfo[1] ?? null, self::ERRORES_DE_BLOQUEO, true)) {
                throw CitaNoAgendable::noDisponible();
            }

            throw $e;
        }
    }

    private function reservarConBloqueo(array $cita): string
    {
        // Entero propio, no entrada del usuario.
        $this->db->statement('SET SESSION innodb_lock_wait_timeout = '.(int) $this->esperaBloqueoSegundos);

        // Primero el bloqueo del dentista: así las lecturas siguientes ven las
        // citas que confirmó la reserva anterior.
        $this->db->table('personal')->where('id', $cita['dentista_id'])->lockForUpdate()->first(['id']);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $cita['fecha'].' '.$cita['hora'])->startOfMinute();
        $fin = $inicio->copy()->addMinutes((int) $cita['duracion_min']);

        $horario = $this->db->table('horarios_dentista')
            ->where('personal_id', $cita['dentista_id'])
            ->where('dia_semana', $inicio->isoWeekday())
            ->first();

        if (! $horario || ! $horario->disponible) {
            throw CitaNoAgendable::sinHorario();
        }

        $franjaInicio = Carbon::parse($cita['fecha'].' '.$horario->hora_inicio);
        $franjaFin = Carbon::parse($cita['fecha'].' '.$horario->hora_fin);

        if ($inicio->lt($franjaInicio) || $fin->gt($franjaFin)) {
            throw CitaNoAgendable::fueraDeFranja($franjaInicio, $franjaFin);
        }

        $this->comprobarSolape($cita['dentista_id'], $inicio, $fin);

        $id = (string) Str::uuid();
        $this->db->table('citas')->insert([
            'id' => $id,
            'paciente_id' => $cita['paciente_id'],
            'dentista_id' => $cita['dentista_id'],
            'servicio_id' => $cita['servicio_id'],
            'fecha_hora' => $inicio,
            'duracion_min' => (int) $cita['duracion_min'],
            'estado' => 'agendada',
            'notas_paciente' => $cita['notas_paciente'],
            'notas_internas' => $cita['notas_internas'],
            'creado_por' => $cita['creado_por'],
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        return $id;
    }

    /**
     * Solape estricto con las citas del mismo día y dentista que siguen
     * ocupando el hueco: las citas seguidas no chocan (CA5).
     */
    private function comprobarSolape(string $dentistaId, Carbon $inicio, Carbon $fin): void
    {
        $citas = $this->db->table('citas')
            ->where('dentista_id', $dentistaId)
            ->whereNotIn('estado', self::ESTADOS_LIBRES)
            ->whereBetween('fecha_hora', [$inicio->copy()->startOfDay(), $fin])
            ->get(['fecha_hora', 'duracion_min']);

        foreach ($citas as $otra) {
            $otraInicio = Carbon::parse($otra->fecha_hora);
            $otraFin = $otraInicio->copy()->addMinutes((int) $otra->duracion_min);

            if ($inicio->lt($otraFin) && $otraInicio->lt($fin)) {
                throw CitaNoAgendable::noDisponible();
            }
        }
    }
}
