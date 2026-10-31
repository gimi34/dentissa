<?php

namespace App\Listeners;

use App\Http\Controllers\AuthController;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Spec 032 (CA2; TM5, TM8; plan D7): cada inicio de sesión fallido o bloqueado
 * deja una línea en el canal `seguridad`, que el vigilante cuenta para avisar de
 * las ráfagas. El limitador de la spec 030 no cambia.
 *
 * La línea no lleva la IP, el correo ni el id: solo huellas HMAC recortadas del
 * origen y de la cuenta, con las mismas reglas que agrupa el limitador. Es
 * seudonimización (con la APP_KEY se recupera una IPv4 por fuerza bruta), un
 * riesgo aceptado por el usuario (TM8, /analyze A46).
 */
class RegistrarFalloAcceso
{
    public function handleFallo(Failed $evento): void
    {
        $google = ($evento->credentials['proveedor'] ?? null) === 'google';

        $this->registrar('fallido', $google, request(), $google ? null : ($evento->credentials['email'] ?? null));
    }

    /** El Lockout del limitador `google` solo trae la petición (/analyze A33). */
    public function handleBloqueo(Lockout $evento): void
    {
        $google = $evento->request->routeIs('auth.google.callback');

        $this->registrar('bloqueado', $google, $evento->request, $google ? null : $evento->request->input('email'));
    }

    private function registrar(string $tipo, bool $google, Request $request, mixed $email): void
    {
        // Registrar nunca cambia la respuesta del login ni la del limitador.
        try {
            Log::channel('seguridad')->info('acceso_fallido', [
                'tipo' => $tipo,
                'metodo' => $google ? 'google' : 'contrasena',
                'origen' => $this->huella(AuthController::origenLimite($request)),
                'cuenta' => is_string($email) && $email !== '' ? $this->huella(AuthController::sujetoLimite($email)) : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** HMAC-SHA256 recortado, con una clave derivada de APP_KEY y no la APP_KEY misma (/analyze A27). */
    private function huella(string $valor): string
    {
        $clave = hash_hmac('sha256', 'dentissa-seguridad', (string) config('app.key'));

        return substr(hash_hmac('sha256', $valor, $clave), 0, 16);
    }
}
