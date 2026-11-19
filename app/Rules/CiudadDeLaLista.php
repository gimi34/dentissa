<?php

namespace App\Rules;

use App\Pacientes\Ciudades;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Spec 051 (CA5, CA6, CA10): la ciudad tiene que ser una opción de la lista. La única excepción es
 * la ciudad antigua de ese mismo paciente, para no perderla al guardar otro dato; `$guardada` sale
 * siempre del paciente de la ruta o de la sesión, nunca de la petición.
 */
class CiudadDeLaLista implements ValidationRule
{
    public function __construct(private readonly ?string $guardada = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || (! Ciudades::contiene($value) && $value !== Ciudades::antigua($this->guardada))) {
            $fail('Elige una ciudad de la lista');
        }
    }
}
