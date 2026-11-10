<?php

namespace App\Providers;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\AlmacenAuditoriaBaseDatos;
use App\Auditoria\Auditoria;
use App\Autorizacion\CambiarEstadoCita;
use App\Autorizacion\GestionGaleria;
use App\Autorizacion\GestionPersonal;
use App\Autorizacion\ReprogramarCita;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecuperarContrasenaController;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registro de auditoría (spec 020): un solo almacén y un solo servicio por ejecución, para que
        // el request_id generado fuera de HTTP se reutilice. Los tests sustituyen el almacén.
        $this->app->bind(AlmacenAuditoria::class, AlmacenAuditoriaBaseDatos::class);
        $this->app->singleton(Auditoria::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Estados de la cita (spec 026): alcance antes de validar, y alcance + rol al cambiar.
        Gate::define('gestionar-cita', [CambiarEstadoCita::class, 'alcance']);
        Gate::define('cambiar-estado-cita', [CambiarEstadoCita::class, 'autorizar']);
        Gate::define('reprogramar-cita', [ReprogramarCita::class, 'autorizar']);

        // Horario del dentista (spec 027): solo la administración, y solo sobre dentistas.
        Gate::define('definir-horario', [GestionPersonal::class, 'definirHorario']);

        // Galería de trabajos (spec 039, CA13): solo la administración la gestiona.
        Gate::define('gestionar-galeria', [GestionGaleria::class, 'autorizar']);

        // Límites de intentos (spec 030, CA8): 5 por minuto por IP (IPv6 por su /64), cuente o no
        // el resultado. El mensaje es el del login y no revela si la cuenta existe.
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(5)
            ->by(AuthController::origenLimite($request))
            ->response(fn (Request $request, array $headers) => back()
                ->withErrors(['email' => AuthController::mensajeDemasiadosIntentos((int) $headers['Retry-After'])])
                ->withInput($request->except('password', 'password_confirmation'))));

        // La vuelta de Google es un intento de login: el rechazo emite Lockout
        // para la auditoría de la spec 020 (excepción P7).
        RateLimiter::for('google', fn (Request $request) => Limit::perMinute(5)
            ->by(AuthController::origenLimite($request))
            ->response(function (Request $request, array $headers) {
                event(new Lockout($request));

                return redirect()->route('login')->withErrors([
                    'email' => AuthController::mensajeDemasiadosIntentos((int) $headers['Retry-After']),
                ]);
            }));

        // Spec 041 (CA7, TM8): cada sesión guarda la versión con la que entró, también al entrar con
        // Google. Convive con el listener de la 020 que pone el actor en el Context (/analyze A19).
        Event::listen(Login::class, fn (Login $evento) => session()->put('version_sesion', (int) $evento->user->version_sesion));

        // Spec 041 (CA9, D15): 5 envíos por minuto por origen, válidos o no. Al pasar el límite, la pantalla
        // del formulario en espera, nunca la URL limitada ni datos sin comprobar: esto corre antes de validar.
        RateLimiter::for('solicitar-enlace', fn (Request $request) => Limit::perMinute(5)
            ->by(AuthController::origenLimite($request))
            ->response(fn (Request $request) => self::pedirEnEspera($request)));

        // D23 (/review R26): abrir un enlace vigente comprueba su hash, que es costoso. Límite propio y amplio,
        // distinto del de los envíos, para que la espera de fijar pueda seguir mostrando su pantalla.
        RateLimiter::for('abrir-enlace', fn (Request $request) => Limit::perMinute(30)
            ->by(AuthController::origenLimite($request))
            // Esta respuesta sale antes de `PreventBackHistory`, y la URL lleva el token (TM11).
            ->response(fn () => response()->view('auth.recuperar.caducado', ['espera' => true], 429, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ])));

        RateLimiter::for('fijar-contrasena', fn (Request $request) => Limit::perMinute(5)
            ->by(AuthController::origenLimite($request))
            ->response(function (Request $request) {
                $token = $request->input('token');
                $cuenta = $request->input('cuenta');

                if (is_string($token) && preg_match('/^[A-Za-z0-9]{1,255}$/', $token) === 1 && is_string($cuenta) && Str::isUuid($cuenta)) {
                    return redirect()->route('contrasena.nueva', ['token' => $token, 'cuenta' => $cuenta])->with('espera', true);
                }

                return self::pedirEnEspera($request);
            }));
    }

    /** «Pedir el enlace» en espera, conservando el correo solo si es texto de hasta 255 caracteres (/analyze A34). */
    private static function pedirEnEspera(Request $request): RedirectResponse
    {
        return redirect()->route('contrasena.solicitud')
            ->with('espera', true)
            ->withInput(RecuperarContrasenaController::correoConservable($request));
    }
}
