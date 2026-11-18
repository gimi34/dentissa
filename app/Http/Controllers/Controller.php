<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // RS19.a de docs/security.md (ficha docs/changes/2026-11-17-fix-admin-no-se-desactiva.md): un admin
    // no puede desactivarse ni quitarse el rol a sí mismo, ni en usuarios, ni en personal, ni en pacientes.
    protected const NO_DESACTIVARSE = 'No puedes desactivar tu propia cuenta.';

    protected const NO_QUITARSE_ADMIN = 'No puedes quitarte el rol de administrador.';

    protected function esLaPropiaCuenta(string $usuarioId): bool
    {
        return $usuarioId === (string) auth()->id();
    }

    /** Rechaza, como error de validación del formulario, que la edición de la propia cuenta le quite el acceso. */
    protected function impedirQuitarsePropioAcceso(string $usuarioId, array $errores): void
    {
        $errores = array_filter($errores);

        if ($errores !== [] && $this->esLaPropiaCuenta($usuarioId)) {
            throw ValidationException::withMessages($errores);
        }
    }
}
