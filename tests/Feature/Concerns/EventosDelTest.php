<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Spec 051 (T030): la auditoría no admite borrados y una migración de datos puede dejar en ella su
 * evento antes de la suite (`pacientes_ciudades_corregidas`). Los tests que cuentan eventos miran solo
 * los que se escriben durante cada test.
 */
trait EventosDelTest
{
    private int $ultimoEventoAnterior = 0;

    /** Laravel lo llama al preparar el test, con la transacción ya abierta. */
    protected function setUpEventosDelTest(): void
    {
        $this->ultimoEventoAnterior = (int) DB::table('auditoria')->max('id');
    }

    private function auditoriaDelTest(): Builder
    {
        return DB::table('auditoria')->where('id', '>', $this->ultimoEventoAnterior);
    }
}
