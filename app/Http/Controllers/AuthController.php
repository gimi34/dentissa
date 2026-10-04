<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Fallos de login permitidos por correo e IP en la ventana (spec 030, CA8). */
    private const MAX_FALLOS_LOGIN = 5;

    private const VENTANA_SEGUNDOS = 60;

    /**
     * Mensaje común a los límites del login, el alta y la vuelta de Google:
     * no revela si la cuenta existe.
     */
    public static function mensajeDemasiadosIntentos(int $segundos): string
    {
        return "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.";
    }

    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Con 5 fallos en la ventana, el siguiente intento se rechaza sin
        // intentar autenticar. Lockout deja el rechazo a la vista de la
        // auditoría de la spec 020 (excepción P7).
        $clave = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_FALLOS_LOGIN)) {
            event(new Lockout($request));

            return back()->withErrors([
                'email' => self::mensajeDemasiadosIntentos(RateLimiter::availableIn($clave)),
            ]);
        }

        if (Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'activo' => 1,
        ])) {
            RateLimiter::clear($clave);
            $request->session()->regenerate();

            $user = auth()->user();

            return match ($user->rol) {
                'admin' => redirect('/admin'),
                'dentista' => redirect('/dentista'),
                'asistente' => redirect('/asistente'),
                default => redirect('/cliente'),
            };
        }

        RateLimiter::hit($clave, self::VENTANA_SEGUNDOS);

        return back()->withErrors([
            'email' => 'Credenciales incorrectas',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
