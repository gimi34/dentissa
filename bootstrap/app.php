<?php

use App\Http\Middleware\ContextoActor;
use App\Http\Middleware\IdentificadorPeticion;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Detrás de cloudflared y nginx (spec 030, D3): solo se confía en el
        // protocolo. La IP real la pone nginx en REMOTE_ADDR desde
        // CF-Connecting-IP; X-Forwarded-For, -Host y -Port los podría enviar
        // cualquier cliente (TM4, TM10).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO);

        // Alias de middlewares personalizados
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'no-cache' => PreventBackHistory::class,
        ]);

        // Aplica no-cache a todas las rutas protegidas con auth
        $middleware->appendToGroup('web', [
            PreventBackHistory::class,
        ]);

        // Correlación (spec 020, D5): el X-Request-Id de cada respuesta, también las de error, y el
        // actor de los logs al empezar cada petición con sesión.
        $middleware->prepend(IdentificadorPeticion::class);
        $middleware->appendToGroup('web', [
            ContextoActor::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
