<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Context;

/**
 * Spec 020 (CA10; D5, D20): al iniciar o cerrar sesión dentro de una petición, los logs y los
 * eventos que vienen después llevan el mismo actor. `ContextoActor` lo fija al empezar la petición.
 *
 * Se registra por el descubrimiento automático de `app/Listeners`, como `RegistrarFalloAcceso`.
 */
class ActualizarActorDelContexto
{
    public function handleLogin(Login $evento): void
    {
        Context::add('actor_id', $evento->user->getAuthIdentifier());
    }

    public function handleLogout(Logout $evento): void
    {
        Context::forget('actor_id');
    }
}
