<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Spec 030 (CA5, RS6.c): cambia las contraseñas de las cuentas del volcado
 * antes de abrir producción.
 *
 * Cada cuenta con contraseña recibe una aleatoria de 20 caracteres
 * alfanuméricos, que se muestra una sola vez por la salida estándar y nunca se
 * escribe en el log. Las cuentas solo de Google (sin contraseña) no cambian.
 */
class RotarCredencialesVolcado extends Command
{
    protected $signature = 'dentissa:rotar-credenciales
        {--confirmar : Cambia de verdad las contraseñas (sin esta opción no hace nada)}';

    protected $description = 'Pone una contraseña aleatoria a cada cuenta con contraseña y la muestra una sola vez';

    public function handle(): int
    {
        if (! $this->option('confirmar')) {
            $this->error('No se cambió nada. Repite con --confirmar para cambiar las contraseñas.');

            return self::FAILURE;
        }

        $filas = DB::transaction(function () {
            $filas = [];

            Usuario::whereNotNull('password_hash')
                ->where('password_hash', '!=', '')
                ->orderBy('email')
                ->get()
                ->each(function (Usuario $usuario) use (&$filas) {
                    $nueva = Str::random(20);
                    $usuario->forceFill(['password_hash' => Hash::make($nueva)])->save();
                    $filas[] = [$usuario->email, $nueva];
                });

            return $filas;
        });

        $this->table(['Correo', 'Contraseña nueva'], $filas);
        $this->warn('Guarda estas contraseñas fuera del chat y del repositorio: no se vuelven a mostrar.');

        return self::SUCCESS;
    }
}
