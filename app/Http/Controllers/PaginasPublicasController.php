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
        // Spec 044 (CA5, reemplaza 038 CA2): con sesión se ve la página, con «Ir a mi panel» en lugar de los
        // enlaces de cuenta; el inicio de sesión sigue llevando al panel de cada rol.
        return view('publico.inicio', [
            'categorias' => Servicio::porCategoria(),
            'contacto' => config('clinica.contacto'),
            'panel' => auth()->check() ? self::panelDelRol(auth()->user()) : null,
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
