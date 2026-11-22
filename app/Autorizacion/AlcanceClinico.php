<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Alcance clínico del personal (ADR 0003).
 *
 * La spec 026 crea los dentistas del alcance (citas); la spec 014 añade el alcance sobre
 * pacientes, que usan los Gates de `AccesoClinico` y los listados del personal.
 */
class AlcanceClinico
{
    /** Id de `personal` del usuario, o null si no es personal de la clínica. */
    public function personalId(Usuario $usuario): ?string
    {
        return DB::table('personal')->where('usuario_id', $usuario->id)->value('id');
    }

    /**
     * Dentistas cuyas citas puede gestionar el usuario: null = sin restricción (admin);
     * dentista → él mismo; asistente → sus dentistas asignados; cualquier otro rol → ninguno.
     */
    public function dentistasDelAlcance(Usuario $usuario): ?array
    {
        if ($usuario->rol === 'admin') {
            return null;
        }

        $personalId = $this->personalId($usuario);
        if ($personalId === null) {
            return [];
        }

        return match ($usuario->rol) {
            'dentista' => [$personalId],
            'asistente' => DB::table('asistente_dentista')
                ->where('asistente_id', $personalId)
                ->pluck('dentista_id')
                ->all(),
            default => [],
        };
    }

    /**
     * Spec 014 (D11, CA1, CA2): limita una consulta sobre `pacientes` a los del alcance del usuario. Un paciente es
     * del alcance si está asignado a uno de sus dentistas o tiene alguna cita no cancelada con uno de ellos, pasada
     * o futura. El admin no tiene restricción. Se evalúa en cada consulta, sin caché (CA10).
     */
    public function aplicarAPacientes(Builder $consulta, Usuario $usuario): Builder
    {
        $dentistas = $this->dentistasDelAlcance($usuario);
        if ($dentistas === null) {
            return $consulta;
        }

        return $consulta->where(function (Builder $alcance) use ($dentistas) {
            $alcance->whereIn('pacientes.dentista_id', $dentistas)
                ->orWhereExists(fn (Builder $citas) => $citas->select(DB::raw(1))
                    ->from('citas')
                    ->whereColumn('citas.paciente_id', 'pacientes.id')
                    ->whereIn('citas.dentista_id', $dentistas)
                    ->where('citas.estado', '<>', 'cancelada'));
        });
    }

    /** Spec 014: si el paciente está en el alcance del usuario (la misma regla que `aplicarAPacientes`). */
    public function pacienteEnAlcance(Usuario $usuario, string $pacienteId): bool
    {
        return $this->aplicarAPacientes(DB::table('pacientes')->where('pacientes.id', $pacienteId), $usuario)->exists();
    }
}
