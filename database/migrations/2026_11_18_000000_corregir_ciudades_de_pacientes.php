<?php

use App\Pacientes\CorreccionCiudades;
use Illuminate\Database\Migrations\Migration;

/**
 * Spec 051 (CA8, CA9, CA12): corrige las ciudades ya guardadas que coinciden con la lista y deja su
 * evento de auditoría. Usa la lista vigente en el código: una base nueva se corrige con la de ese
 * momento, que es justo lo que se quiere.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(CorreccionCiudades::class)->ejecutar();
    }

    /**
     * No se deshace: solo reescribe la forma de ciudades que ya eran ese municipio, y el texto
     * original queda en la copia de la base previa al despliegue.
     */
    public function down(): void {}
};
