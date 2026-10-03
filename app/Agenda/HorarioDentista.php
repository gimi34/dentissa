<?php

namespace App\Agenda;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Horario semanal del dentista (spec 027): lo guarda y calcula qué citas futuras
 * quedan fuera de él.
 *
 * El guardado bloquea primero la fila del dentista en `personal`, el mismo bloqueo
 * que toma ReservaCitas, así que una reserva simultánea ve el horario anterior
 * completo o el nuevo, nunca uno a medias (TM5). La regla de franja es la misma que
 * la de la reserva: una cita queda fuera si su día no tiene horario disponible, si
 * empieza antes de la franja o si termina después.
 */
class HorarioDentista
{
    /** Bloqueo agotado (1205) e interbloqueo (1213) de MariaDB. */
    private const ERRORES_DE_BLOQUEO = [1205, 1213];

    /** Estados de las citas que siguen pendientes de atender. */
    private const ESTADOS_PENDIENTES = ['agendada', 'confirmada'];

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Sustituye el horario del dentista por las franjas dadas (una por día marcado).
     *
     * @param  array<int, array{inicio: string, fin: string}>  $franjas  día (1 = lunes … 7 = domingo) => horas H:i ya validadas
     * @return int citas futuras que quedan fuera del horario nuevo
     *
     * @throws HorarioNoGuardado
     */
    public function guardar(string $dentistaId, array $franjas): int
    {
        try {
            $this->db->transaction(fn () => $this->guardarConBloqueo($dentistaId, $franjas));
        } catch (DeadlockException) {
            // Dentro de una transacción anidada Laravel convierte 1205 y 1213 en DeadlockException.
            throw HorarioNoGuardado::ocupado();
        } catch (QueryException $e) {
            if (in_array($e->errorInfo[1] ?? null, self::ERRORES_DE_BLOQUEO, true)) {
                throw HorarioNoGuardado::ocupado();
            }

            throw $e;
        }

        return $this->citasFuera($dentistaId)->count();
    }

    /**
     * Citas agendadas o confirmadas del dentista que aún no empiezan (hora de la
     * clínica) y que quedan fuera de su horario, ordenadas por fecha.
     *
     * @return Collection<int, object{id: string, paciente: string, fecha_hora: string, duracion_min: int, estado: string}>
     */
    public function citasFuera(string $dentistaId): Collection
    {
        $ahora = Carbon::now(config('clinica.zona_horaria'))->format('Y-m-d H:i:s');

        $horarios = $this->db->table('horarios_dentista')
            ->where('personal_id', $dentistaId)
            ->get(['dia_semana', 'hora_inicio', 'hora_fin', 'disponible'])
            ->keyBy(fn ($horario) => (int) $horario->dia_semana);

        return $this->db->table('citas')
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('citas.dentista_id', $dentistaId)
            ->whereIn('citas.estado', self::ESTADOS_PENDIENTES)
            ->where('citas.fecha_hora', '>', $ahora)
            ->orderBy('citas.fecha_hora')
            ->get(['citas.id', 'citas.fecha_hora', 'citas.duracion_min', 'citas.estado', 'usuarios.nombre', 'usuarios.apellido'])
            ->filter(fn ($cita) => $this->quedaFuera($cita, $horarios))
            ->map(fn ($cita) => (object) [
                'id' => $cita->id,
                'paciente' => trim($cita->nombre.' '.$cita->apellido),
                'fecha_hora' => $cita->fecha_hora,
                'duracion_min' => (int) $cita->duracion_min,
                'estado' => $cita->estado,
            ])
            ->values();
    }

    private function guardarConBloqueo(string $dentistaId, array $franjas): void
    {
        // Primero el bloqueo del dentista, en el mismo orden que la reserva
        // (personal → horarios → citas), para no crear interbloqueos nuevos.
        $this->db->table('personal')->where('id', $dentistaId)->lockForUpdate()->first(['id']);

        $this->db->table('horarios_dentista')->where('personal_id', $dentistaId)->delete();

        foreach ($franjas as $dia => $franja) {
            $this->db->table('horarios_dentista')->insert([
                'id' => (string) Str::uuid(),
                'personal_id' => $dentistaId,
                'dia_semana' => (int) $dia,
                'hora_inicio' => $franja['inicio'],
                'hora_fin' => $franja['fin'],
                'disponible' => 1,
            ]);
        }
    }

    /** Misma regla y mismos bordes que ReservaCitas: la franja admite empezar y terminar justo en sus límites. */
    private function quedaFuera(object $cita, Collection $horarios): bool
    {
        $inicio = Carbon::parse($cita->fecha_hora);
        $horario = $horarios->get($inicio->isoWeekday());

        if (! $horario || ! $horario->disponible) {
            return true;
        }

        $fecha = $inicio->format('Y-m-d');
        $fin = $inicio->copy()->addMinutes((int) $cita->duracion_min);

        return $inicio->lt(Carbon::parse($fecha.' '.$horario->hora_inicio))
            || $fin->gt(Carbon::parse($fecha.' '.$horario->hora_fin));
    }
}
