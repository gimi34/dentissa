<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 041 (D4, CA7): versión de las sesiones de cada cuenta. Restablecer la contraseña la sube, y
 * `SesionVigente` cierra toda sesión que entró con una versión anterior (las que no guardaron versión
 * cuentan como 0). Solo expand: la columna tiene valor por defecto y el código anterior la ignora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $tabla) {
            $tabla->unsignedInteger('version_sesion')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $tabla) {
            $tabla->dropColumn('version_sesion');
        });
    }
};
