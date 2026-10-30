<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actor de los logs (spec 020, CA10; D5): al empezar cada petición del grupo `web`, el usuario de
 * la sesión pasa al `Context`, o se quita si no hay sesión, para que una petición anónima nunca
 * herede el actor de otra. Los cambios dentro de la petición los hace `ActualizarActorDelContexto`.
 */
class ContextoActor
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = Auth::id();
        if ($actor !== null) {
            Context::add('actor_id', $actor);
        } else {
            Context::forget('actor_id');
        }

        return $next($request);
    }
}
