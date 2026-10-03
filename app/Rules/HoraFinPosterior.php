<?php

namespace App\Rules;

use Closure;
use DateTime;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * La hora de fin de un día del horario es posterior a su hora de inicio, el mismo
 * día (spec 027, CA2).
 *
 * Solo compara cuando las dos horas son textos H:i válidos. Con cualquier otro valor
 * (una lista, un texto, "25:99" o vacío) no dice nada y deja el error a `date_format`
 * o `required_with`. La regla `after:` de Laravel no sirve aquí: con una lista en la
 * hora de inicio lanza un TypeError (A5).
 */
class HoraFinPosterior implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function setData(array $data): static
    {
        $this->datos = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // `dias.N.hora_fin` → `dias.N.hora_inicio`.
        $inicio = data_get($this->datos, preg_replace('/\.hora_fin$/', '.hora_inicio', $attribute));

        if (! $this->esHora($inicio) || ! $this->esHora($value)) {
            return;
        }

        // Con el formato H:i, la comparación de textos es la de las horas.
        if ($value <= $inicio) {
            $fail('La hora de fin debe ser posterior a la de inicio, el mismo día.');
        }
    }

    private function esHora(mixed $valor): bool
    {
        if (! is_string($valor)) {
            return false;
        }

        $hora = DateTime::createFromFormat('!H:i', $valor);

        return $hora !== false && $hora->format('H:i') === $valor;
    }
}
