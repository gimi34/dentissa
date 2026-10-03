<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

/**
 * Quién puede gestionar datos del personal (spec 027).
 *
 * Respalda el Gate `definir-horario`: solo la administración define horarios, y
 * solo de dentistas. Para cualquier otro tipo de personal el horario no existe
 * (404, CA6); otro rol se deniega (403, CA9), aunque hoy ya lo corta `role:admin`.
 */
class GestionPersonal
{
    public function definirHorario(Usuario $usuario, object $personal): Response
    {
        if ($usuario->rol !== 'admin') {
            return Response::deny('No tienes permiso para definir horarios.');
        }

        return $personal->tipo === 'dentista'
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
