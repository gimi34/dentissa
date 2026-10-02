<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Alcance clínico del personal (ADR 0003).
 *
 * La spec 026 crea solo los dos métodos que necesita, con la firma que diseña la
 * spec 014, que añadirá el resto (alcance sobre pacientes y expedientes).
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
}
