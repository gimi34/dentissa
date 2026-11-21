<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spec 054 (D1, D3): un paciente registrado por el personal puede no tener correo. Su cuenta queda
 * con `email` y `password_hash` nulos y no puede entrar hasta que se le añade un correo.
 *
 * Solo expand: el índice único `uq_usuarios_email` admite varios nulos y `MODIFY` conserva la
 * intercalación de la tabla. La versión anterior convive con la columna nulable (plan → Rollout).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE usuarios MODIFY email VARCHAR(255) NULL');
    }

    /**
     * Solo se deshace si no hay cuentas sin correo: volver a NOT NULL las rompería. En ese caso hay
     * que darles un correo o restaurar la copia previa al despliegue (plan → Rollout).
     */
    public function down(): void
    {
        $sinCorreo = DB::table('usuarios')->whereNull('email')->count();

        if ($sinCorreo > 0) {
            throw new RuntimeException(
                "Hay {$sinCorreo} cuentas sin correo: no se puede volver a exigir el correo. "
                .'Dales un correo o restaura la copia de la base previa al despliegue.'
            );
        }

        DB::statement('ALTER TABLE usuarios MODIFY email VARCHAR(255) NOT NULL');
    }
};
