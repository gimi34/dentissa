<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
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

    /** Clave del límite del login: la cuenta y el origen. */
    private function claveLimiteLogin(string $email, Request $request): string
    {
        return 'login:'.self::sujetoLimite($email).'|'.self::origenLimite($request);
    }

    /**
     * Cuenta que cuenta el límite del login, y que agrupa el registro de fallos
     * de la spec 032 (plan D7). Se busca con la misma comparación que la
     * autenticación (colación de `usuarios.email`), así que las variantes de un
     * correo que entran en la misma cuenta comparten contador (/review R1), esté
     * activa o no. Si no hay cuenta, cuenta el correo en minúsculas; la respuesta
     * es la misma en los dos casos.
     */
    public static function sujetoLimite(string $email): string
    {
        $cuenta = Usuario::where('email', $email)->value('id');

        return $cuenta !== null ? 'cuenta:'.$cuenta : 'correo:'.Str::lower($email);
    }

    /**
     * Origen que cuentan los límites de intentos: la IPv4 tal cual y la IPv6
     * reducida a su prefijo /64, que un cliente suele controlar entero
     * (/review R6). Las IPv4 escritas como IPv6 (`::ffff:a.b.c.d`) cuentan
     * como su IPv4.
     */
    public static function origenLimite(Request $request): string
    {
        $ip = (string) $request->ip();

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        $binaria = inet_pton($ip);

        if (str_starts_with($binaria, str_repeat("\0", 10)."\xff\xff")) {
            return inet_ntop(substr($binaria, 12));
        }

        return inet_ntop(substr($binaria, 0, 8).str_repeat("\0", 8)).'/64';
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
        $clave = $this->claveLimiteLogin($credentials['email'], $request);

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
