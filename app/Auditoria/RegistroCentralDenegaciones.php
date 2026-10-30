<?php

namespace App\Auditoria;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra las denegaciones que nadie registró con `Auditoria::denegar()` (spec 020, CA4; D4).
 *
 * Lo llama el callback `respond` de `withExceptions`, que recibe la excepción ya preparada. Con un
 * usuario autenticado es una denegación:
 * - una respuesta 403 (`abort(403)`, `RoleMiddleware`, un Gate denegado sin estado) → `denegado`;
 * - una 404 cuya excepción previa es una `AuthorizationException` (un Gate con `denyAsNotFound`),
 *   salvo que su código sea `recurso_inexistente` → `denegado_404`.
 *
 * Un 404 real (sin esa previa) no es una denegación. La excepción de `denegar()` ya dejó su evento.
 */
class RegistroCentralDenegaciones
{
    /** Código con el que un Gate declara que el recurso no existe para ese usuario, sin ocultarlo. */
    public const RECURSO_INEXISTENTE = 'recurso_inexistente';

    private const ACCION = '/^[A-Za-z0-9_.:-]{1,64}$/';

    private const TIPO = '/^[a-z_]{1,60}$/';

    private const IDENTIFICADOR = '/^[A-Za-z0-9_-]{1,64}$/';

    public function __construct(private readonly Auditoria $auditoria) {}

    public function registrar(Response $respuesta, Throwable $excepcion, Request $peticion): void
    {
        if ($excepcion instanceof AccesoDenegadoAuditado || Auth::user() === null) {
            return;
        }

        $resultado = $this->resultado($respuesta->getStatusCode(), $excepcion->getPrevious());
        if ($resultado === null) {
            return;
        }

        $ruta = $peticion->route();
        $nombre = $ruta?->getName();
        $accion = is_string($nombre) && preg_match(self::ACCION, $nombre) === 1 ? $nombre : 'ruta_sin_nombre';
        $tipo = is_string($nombre) ? (explode('.', $nombre)[1] ?? null) : null;
        $parametros = $ruta?->parameters() ?? [];
        $id = $parametros['id'] ?? (reset($parametros) ?: null);

        $this->auditoria->registrar(
            'acceso_denegado',
            $resultado,
            is_string($tipo) && preg_match(self::TIPO, $tipo) === 1 ? $tipo : null,
            // Un segmento de URL que no es un identificador no llega al registro (TM12).
            is_string($id) && preg_match(self::IDENTIFICADOR, $id) === 1 ? $id : null,
            detalle: ['accion_solicitada' => $accion],
        );
    }

    private function resultado(int $estado, ?Throwable $previa): ?string
    {
        return match (true) {
            $estado === 403 => 'denegado',
            $estado === 404 && $previa instanceof AuthorizationException
                && $previa->response()?->code() !== self::RECURSO_INEXISTENTE => 'denegado_404',
            default => null,
        };
    }
}
