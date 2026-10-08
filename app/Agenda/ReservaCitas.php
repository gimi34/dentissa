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
            'dentista_id' => ['bail', 'required', 'string', Rule::exists('personal', 'id')->where(
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
            'dentista_id.string' => 'Elige un dentista válido.',
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
        // Una duración de 0 o negativa haría que el solape nunca se cumpliera.
        if ((int) $cita['duracion_min'] < 1) {
            throw CitaNoAgendable::duracionInvalida();
        }

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

    /**
     * Bloquea la fila del dentista hasta el final de la transacción. Se llama dentro
     * de una transacción ya abierta y antes de leer horario y citas: así las lecturas
     * siguientes ven las citas que confirmó la reserva anterior.
     *
     * @throws CitaNoAgendable si el dentista ya no existe
     */
    public function bloquearDentista(string $id): void
    {
        // Entero propio, no entrada del usuario.
        $this->db->statement('SET SESSION innodb_lock_wait_timeout = '.(int) $this->esperaBloqueoSegundos);

        $dentista = $this->db->table('personal')->where('id', $id)->lockForUpdate()->first(['id']);

        if ($dentista === null) {
            // El dentista dejó de existir entre la validación y la reserva.
            throw CitaNoAgendable::noDisponible();
        }
    }

    /**
     * Comprueba que el dentista puede atender de `$inicio` a `$fin`: día con horario,
     * encaje en la franja y sin solape (CA2–CA7). Se llama dentro de una transacción
     * ya abierta, después de `bloquearDentista`. Al reprogramar, la propia cita no cuenta
     * como conflicto (`$excluirCitaId`, spec 028 D2).
     *
     * @throws CitaNoAgendable
     */
    public function comprobarHueco(string $dentistaId, Carbon $inicio, Carbon $fin, ?string $excluirCitaId = null): void
    {
        $horario = $this->db->table('horarios_dentista')
            ->where('personal_id', $dentistaId)
            ->where('dia_semana', $inicio->isoWeekday())
            ->first();

        if (! $horario || ! $horario->disponible) {
            throw CitaNoAgendable::sinHorario();
        }

        $dia = $inicio->format('Y-m-d');
        $franjaInicio = Carbon::parse($dia.' '.$horario->hora_inicio);
        $franjaFin = Carbon::parse($dia.' '.$horario->hora_fin);

        if ($inicio->lt($franjaInicio) || $fin->gt($franjaFin)) {
            throw CitaNoAgendable::fueraDeFranja($franjaInicio, $franjaFin);
        }

        $this->comprobarSolape($dentistaId, $inicio, $fin, $excluirCitaId);
    }

    private function reservarConBloqueo(array $cita): string
    {
        $this->bloquearDentista($cita['dentista_id']);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $cita['fecha'].' '.$cita['hora'])->startOfMinute();
        $fin = $inicio->copy()->addMinutes((int) $cita['duracion_min']);

        $this->comprobarHueco($cita['dentista_id'], $inicio, $fin);

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
    private function comprobarSolape(string $dentistaId, Carbon $inicio, Carbon $fin, ?string $excluirCitaId = null): void
    {
        $citas = $this->db->table('citas')
            // Por el índice del dentista: el FOR UPDATE solo bloquea citas de este
            // dentista, no las de otros en el mismo tramo del día (R15).
            ->forceIndex('idx_citas_dentista')
            ->where('dentista_id', $dentistaId)
            ->whereNotIn('estado', self::ESTADOS_LIBRES)
            ->when($excluirCitaId !== null, fn ($consulta) => $consulta->where('id', '!=', $excluirCitaId))
            ->whereBetween('fecha_hora', [$inicio->copy()->startOfDay(), $fin])
            // Lectura con bloqueo: ve la última versión confirmada aunque la reserva
            // corra dentro de una transacción exterior con una vista de lectura previa.
            ->lockForUpdate()
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
