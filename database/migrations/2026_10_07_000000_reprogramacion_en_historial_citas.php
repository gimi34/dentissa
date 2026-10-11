<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 028: una reprogramación deja una entrada de historial con la fecha y hora
 * anteriores y las nuevas (CA7). Solo añade columnas que admiten nulo: las filas
 * existentes (cambios de estado) quedan sin fechas y el código anterior las ignora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_citas', function (Blueprint $tabla) {
            $tabla->dateTime('fecha_hora_anterior')->nullable()->after('estado_nuevo');
            $tabla->dateTime('fecha_hora_nueva')->nullable()->after('fecha_hora_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('historial_citas', function (Blueprint $tabla) {
            $tabla->dropColumn(['fecha_hora_anterior', 'fecha_hora_nueva']);
        });
    }
};
