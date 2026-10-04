<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

/**
 * Spec 030 (CA5, RS6.c): cambia las contraseñas de las cuentas del volcado
 * antes de abrir producción.
 */
class RotarCredencialesVolcado extends Command
{
    protected $signature = 'dentissa:rotar-credenciales
        {--confirmar : Cambia de verdad las contraseñas (sin esta opción no hace nada)}';

    protected $description = 'Pone una contraseña aleatoria a cada cuenta con contraseña y la muestra una sola vez';

    public function handle(): int
    {
        throw new RuntimeException('sin implementar');
    }
}
