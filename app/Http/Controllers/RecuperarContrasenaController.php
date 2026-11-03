<?php

namespace App\Http\Controllers;

use App\Auditoria\Auditoria;
use App\Mail\ContrasenaCambiada;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Spec 041: recuperar la contraseña por correo. Rutas públicas: la autorización es tener el token
 * de esa cuenta, que comprueba el broker (TM3, TM4). Solo las cuentas activas reciben el enlace y
 * pueden usarlo (TM5).
 *
 * Pedir el enlace responde siempre igual: el trabajo que depende de la cuenta va después de responder
 * (`afterResponse()`, plan D6), así que ni el texto, ni el código, ni el tiempo la delatan (TM1).
 */
class RecuperarContrasenaController extends Controller
{
    public function solicitudForm(Request $request): View
    {
        return view('auth.recuperar.solicitud', ['espera' => $request->session()->has('espera')]);
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $validador = Validator::make($request->only('email'), [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Escribe el correo de tu cuenta.',
            'email.string' => 'Escribe un correo válido.',
            'email.email' => 'Escribe un correo válido.',
            'email.max' => 'Escribe un correo válido.',
        ]);

        // Nunca back(): la página de origen puede no enviar Referer.
        if ($validador->fails()) {
            return redirect()->route('contrasena.solicitud')
                ->withErrors($validador)
                ->withInput(self::correoConservable($request));
        }

        $correo = $validador->validated()['email'];

        // El estado del broker se ignora: con cuenta, sin ella o con la espera de 60 s, la respuesta
        // es la misma (CA8, CA9). TM6: del fallo solo se registra la clase del error.
        dispatch(function () use ($correo) {
            try {
                Password::sendResetLink(['email' => $correo, 'activo' => 1]);
            } catch (Throwable $e) {
                Log::error('restablecimiento.correo_fallido', ['error' => class_basename($e)]);
            }
        })->afterResponse();

        // TM14: el flash dura una petición; «Revisa tu correo» lo muestra y desaparece.
        return redirect()->route('contrasena.enviada')->with('contrasena_enviada', [
            'correo' => $correo,
            'hora' => self::horaLocal(CarbonImmutable::now()->addMinutes(self::minutosDeVigencia())),
        ]);
    }

    public function enviada(Request $request): View|RedirectResponse
    {
        $datos = $request->session()->get('contrasena_enviada');

        if (! is_array($datos)) {
            return redirect()->route('contrasena.solicitud');
        }

        return view('auth.recuperar.enviada', $datos);
    }

    public function nuevaForm(Request $request, string $token): View
    {
        $cuenta = $request->query('cuenta');
        $usuario = is_string($cuenta) ? $this->cuentaDelEnlace($cuenta, $token) : null;

        if ($usuario === null) {
            return view('auth.recuperar.caducado', ['espera' => $request->session()->has('espera')]);
        }

        return view('auth.recuperar.nueva', [
            'token' => $token,
            'cuenta' => $usuario->id,
            'correo' => $usuario->email,
            'hora' => $this->horaDeCaducidad($usuario),
            'espera' => $request->session()->has('espera'),
        ]);
    }

    public function restablecer(Request $request): Response
    {
        $formato = Validator::make($request->only('token', 'cuenta'), [
            'token' => ['required', 'string', 'max:255'],
            'cuenta' => ['required', 'uuid'],
        ]);

        // Sin URL del enlace a la que volver: «Este enlace ya no sirve» (/analyze A33).
        if ($formato->fails()) {
            return response()->view('auth.recuperar.caducado');
        }

        ['token' => $token, 'cuenta' => $cuenta] = $formato->validated();
        $usuario = $this->cuentaDelEnlace($cuenta, $token);

        if ($usuario === null) {
            return response()->view('auth.recuperar.caducado');
        }

        $validador = Validator::make($request->only('password', 'password_confirmation'), [
            // A22: la misma regla que el registro y el perfil.
            'password' => ['required', 'string', ReglaContrasena::min(8), 'max:255'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password.required' => 'Escribe tu contraseña nueva.',
            'password.string' => 'Escribe tu contraseña nueva.',
            'password.min' => 'Tiene que tener al menos 8 caracteres.',
            'password.max' => 'Tiene que tener como mucho 255 caracteres.',
            'password_confirmation.required' => 'Repite la contraseña.',
            'password_confirmation.same' => 'Las dos contraseñas no coinciden.',
        ]);

        // Nunca back(): con no-referrer el POST no lleva Referer (/analyze A26).
        if ($validador->fails()) {
            return redirect()->route('contrasena.nueva', ['token' => $token, 'cuenta' => $cuenta])->withErrors($validador);
        }

        // D5: contraseña, versión de sesión, auditoría y token, todo o nada. El broker borra el token
        // después del callback, dentro de la misma transacción; si el registro falla, 503 de la 020.
        $estado = DB::transaction(fn () => Password::reset([
            'email' => $usuario->email,
            'activo' => 1,
            'token' => $token,
            'password' => $validador->validated()['password'],
        ], function (Usuario $cuentaValidada, string $contrasena) {
            $cuentaValidada->forceFill([
                'password_hash' => Hash::make($contrasena),
                'version_sesion' => (int) $cuentaValidada->version_sesion + 1,
            ])->save();

            // D2: el actor es la cuenta que el broker validó con el token, nunca el `cuenta` de la petición.
            app(Auditoria::class)->registrar(
                accion: 'contrasena_restablecida',
                resultado: 'exito',
                recursoTipo: 'usuario',
                recursoId: $cuentaValidada->id,
                campos: ['password_hash'],
                critico: true,
                actor: $cuentaValidada,
            );
        }));

        if ($estado !== Password::PASSWORD_RESET) {
            return response()->view('auth.recuperar.caducado');
        }

        // Tras confirmar la transacción: si falló, no se llega aquí y no hay aviso (CA7).
        dispatch(function () use ($usuario) {
            try {
                Mail::to($usuario->email)->send(new ContrasenaCambiada($usuario));
            } catch (Throwable $e) {
                Log::error('restablecimiento.correo_fallido', ['error' => class_basename($e)]);
            }
        })->afterResponse();

        // /review R1: si este navegador tenía abierta la cuenta, la sesión se cierra aquí. Si no, `SesionVigente`
        // la vaciaría al llegar a /login, y el mensaje de éxito con ella (CA3).
        if ($request->user()?->is($usuario)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/login')->with('contrasena_cambiada', true);
    }

    /**
     * Correo que el límite de intentos puede conservar: solo texto de hasta 255 caracteres, porque
     * esa respuesta corre antes de validar (/analyze A34).
     *
     * @return array<string, string>
     */
    public static function correoConservable(Request $request): array
    {
        $correo = $request->input('email');

        return is_string($correo) && mb_strlen($correo) <= 255 ? ['email' => $correo] : [];
    }

    /** La cuenta activa del enlace, si el token es el suyo y sigue vigente (TM3, TM4, TM5). */
    private function cuentaDelEnlace(string $cuenta, string $token): ?Usuario
    {
        if (! Validator::make(['cuenta' => $cuenta], ['cuenta' => ['uuid']])->passes()) {
            return null;
        }

        $usuario = Usuario::where('id', $cuenta)->where('activo', 1)->first();

        return $usuario !== null && Password::tokenExists($usuario, $token) ? $usuario : null;
    }

    private function horaDeCaducidad(Usuario $usuario): string
    {
        $creado = DB::table(config('auth.passwords.users.table'))->where('email', $usuario->email)->value('created_at');

        return self::horaLocal(CarbonImmutable::parse($creado)->addMinutes(self::minutosDeVigencia()));
    }

    private static function minutosDeVigencia(): int
    {
        return (int) config('auth.passwords.users.expire');
    }

    private static function horaLocal(CarbonImmutable $momento): string
    {
        return $momento->setTimezone(config('clinica.zona_horaria'))->format('H:i');
    }
}
