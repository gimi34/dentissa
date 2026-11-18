<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 041 (CA7, TM8, plan D4): restablecer la contraseña cierra todas las sesiones de la cuenta.
 *
 * Cada sesión guarda la versión con la que entró; el restablecimiento sube `usuarios.version_sesion`
 * y aquí se cierra, en su siguiente petición, toda sesión con una versión menor. Una sesión sin
 * versión, abierta antes del despliegue, cuenta como 0. Funciona también con las cuentas de Google
 * sin contraseña. Compara con el usuario ya cargado, sin consulta adicional.
 *
 * También cierra la sesión de una cuenta desactivada (RS3.a; 001 CA11, 006 CA8, 008 CA6, 010 CA8;
 * ficha docs/changes/2026-11-17-fix-sesion-cuenta-desactivada.md): `activo` se comprobaba solo al
 * entrar, y desactivar desde usuarios, personal o pacientes no cerraba la sesión ya abierta.
 */
class SesionVigente
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario !== null) {
            $deLaCuenta = (int) $usuario->version_sesion;

            if (! $usuario->activo || (int) $request->session()->get('version_sesion', 0) < $deLaCuenta) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }

            $request->session()->put('version_sesion', $deLaCuenta);
        }

        return $next($request);
    }
}
