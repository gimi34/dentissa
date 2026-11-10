<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

/**
 * Quién gestiona la galería de trabajos (spec 039, CA13, TM1; ADR 0003).
 *
 * Respalda el Gate `gestionar-galeria`: solo la administración publica, ordena y quita fotos.
 * Las fotos son de la clínica, no de un usuario, así que el alcance es el rol. Cada acción de
 * `FotosGaleriaController` lo comprueba, además de `role:admin` en el grupo de rutas.
 */
class GestionGaleria
{
    public function autorizar(Usuario $usuario): Response
    {
        return $usuario->rol === 'admin'
            ? Response::allow()
            : Response::deny('No tienes permiso para gestionar la galería.');
    }
}
