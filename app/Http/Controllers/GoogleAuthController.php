<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public const MENSAJE_FALLO_GOOGLE = 'No se pudo iniciar sesión con Google. Inténtalo de nuevo.';

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        // Spec 031 (CA5): una vuelta cancelada en Google, sin inicio previo, con el
        // estado caducado o manipulado, o con un error del proveedor vuelve al login
        // sin sesión ni cuentas. Solo el error del proveedor se reporta, para que un
        // fallo de configuración (p. ej. invalid_client) deje rastro (/analyze A12).
        if (request()->has('error')) {
            return $this->volverAlLoginPorFallo();
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->volverAlLoginPorFallo();
        } catch (GuzzleException $e) {
            report($e);

            return $this->volverAlLoginPorFallo();
        }

        $usuario = Usuario::where('google_id', $googleUser->getId())->first();

        if (! $usuario) {
            $usuario = Usuario::where('email', $googleUser->getEmail())->first();

            if ($usuario) {
                $usuario->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        }

        if (! $usuario) {
            // Cuenta nueva: continuar con el registro normal (falta el paso de contacto)
            $partes = preg_split('/\s+/', trim($googleUser->getName() ?: $googleUser->getEmail()));
            $nombre = $googleUser->user['given_name'] ?? array_shift($partes);
            $apellido = $googleUser->user['family_name'] ?? (implode(' ', $partes) ?: '—');

            request()->session()->put('google_registro', [
                'google_id' => $googleUser->getId(),
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $googleUser->getEmail(),
                'foto_url' => $googleUser->getAvatar(),
            ]);

            return redirect()->route('register.contacto');
        }

        if (! $usuario->activo) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta a la clínica.',
            ]);
        }

        Auth::login($usuario);
        request()->session()->regenerate();

        return match ($usuario->rol) {
            'admin' => redirect()->route('admin.dashboard'),
            'dentista' => redirect()->route('dentista.dashboard'),
            'asistente' => redirect()->route('asistente.dashboard'),
            default => redirect()->route('cliente.dashboard'),
        };
    }

    /**
     * Fallo de la vuelta de Google: login fallido para la auditoría de la spec 021
     * (excepción P7) y vuelta al inicio de sesión con un mensaje genérico.
     */
    private function volverAlLoginPorFallo()
    {
        event(new Failed('web', null, ['proveedor' => 'google']));

        return redirect()->route('login')->withErrors([
            'email' => self::MENSAJE_FALLO_GOOGLE,
        ]);
    }
}
