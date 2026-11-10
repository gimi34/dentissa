<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La comprobación de salud (`/up`) pide a los buscadores que no la indexen (spec 044, CA12; SE2.b; D6).
 *
 * Global, porque `/up` se registra fuera del grupo `web`.
 */
class NoIndexarSalud
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if ($request->is('up')) {
            $respuesta->headers->set('X-Robots-Tag', 'noindex');
        }

        return $respuesta;
    }
}
