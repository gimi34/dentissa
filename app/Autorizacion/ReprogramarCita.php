<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

/**
 * Quién puede reprogramar una cita o cancelarla definitivamente desde la pantalla de
 * reprogramar (spec 028, CA8).
 *
 * Primero el alcance de la 026 (personal fuera de alcance → 403; paciente con una cita
 * ajena → 404) y después el rol: administración, asistente y paciente. El dentista no
 * reprograma. Si la cita está cerrada, empezada o fuera de plazo lo decide
 * App\Agenda\EstadosCita.
 */
class ReprogramarCita
{
    private const ROLES = ['admin', 'asistente', 'cliente'];

    public function __construct(private CambiarEstadoCita $cambiarEstado) {}

    public function autorizar(Usuario $usuario, object $cita): Response
    {
        $alcance = $this->cambiarEstado->alcance($usuario, $cita);

        if ($alcance->denied()) {
            return $alcance;
        }

        return in_array($usuario->rol, self::ROLES, true)
            ? Response::allow()
            : Response::deny('Como dentista no puedes reprogramar citas. Pide a tu asistente o a la administración que la reprogramen.');
    }
}
