<?php

namespace App\Providers;

use App\Autorizacion\CambiarEstadoCita;
use Illuminate\Support\Facades\Gate;
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
    }
}
