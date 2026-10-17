<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

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
}
