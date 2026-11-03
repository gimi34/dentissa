<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identificador de petición (spec 020, CA8, CA9; TM3, TM11): reutiliza el `X-Request-Id` que
 * llega solo si es un UUID; si no, genera uno. Lo pone en el `Context`, de donde pasa a los logs y
 * a los eventos de auditoría, y lo devuelve en la respuesta, también en las de error.
 *
 * Solo correlaciona: el actor y el acceso nunca salen de él.
 */
class IdentificadorPeticion
{
    public function handle(Request $request, Closure $next): Response
    {
        $recibido = $request->headers->get('X-Request-Id');
        $id = is_string($recibido) && strlen($recibido) === 36 && Str::isUuid($recibido) ? $recibido : (string) Str::uuid();

        Context::add('request_id', $id);

        $respuesta = $next($request);
        $respuesta->headers->set('X-Request-Id', $id);

        return $respuesta;
    }
}
