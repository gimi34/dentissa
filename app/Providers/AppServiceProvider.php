<?php

namespace App\Providers;

use App\Autorizacion\CambiarEstadoCita;
use App\Autorizacion\GestionPersonal;
use App\Autorizacion\ReprogramarCita;
use App\Http\Controllers\AuthController;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
