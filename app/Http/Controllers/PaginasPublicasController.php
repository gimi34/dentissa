<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Usuario;

/**
 * Páginas públicas (spec 038): la página de inicio y la lista de servicios.
 *
 * Ninguna recibe parámetros; las dos muestran solo el catálogo activo (nombre,
 * descripción y categoría) con una consulta (TM2, TM6).
 */
class PaginasPublicasController extends Controller
{
    // ── Inicio ──────────────────────────────────────────────────────────────
    public function inicio()
    {
        // Con sesión, cada rol sigue yendo a su panel (spec 038 CA2).
        if (auth()->check()) {
            return redirect(self::panelDelRol(auth()->user()));
        }

        return view('publico.inicio', [
            'categorias' => Servicio::porCategoria(),
            'contacto' => config('clinica.contacto'),
        ]);
    }

    // ── Servicios ───────────────────────────────────────────────────────────
    public function servicios()
    {
        return view('publico.servicios', ['categorias' => Servicio::porCategoria()]);
    }

    /** Panel de cada rol: adonde va `/` con sesión y «Ir a mi panel» del layout público. */
    public static function panelDelRol(Usuario $usuario): string
    {
        return route(match ($usuario->rol) {
            'admin' => 'admin.dashboard',
            'dentista' => 'dentista.dashboard',
            'asistente' => 'asistente.dashboard',
            default => 'cliente.dashboard',
        });
    }
}
