<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

/**
 * Quién ve los reportes de la clínica (spec 047, CA9, TM1; ADR 0003).
 *
 * Respalda el Gate `ver-reportes`: solo la administración. Los reportes son de la clínica, no de
 * un usuario, así que el alcance es el rol. `ReportesController` lo comprueba antes de validar,
 * además de `role:admin` en el grupo de rutas.
 */
class VerReportes
{
    public function autorizar(Usuario $usuario): Response
    {
        return $usuario->rol === 'admin'
            ? Response::allow()
            : Response::deny('No tienes permiso para ver los reportes.');
    }
}
