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
 */
class SesionVigente
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario !== null) {
            $deLaCuenta = (int) $usuario->version_sesion;

            if ((int) $request->session()->get('version_sesion', 0) < $deLaCuenta) {
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
