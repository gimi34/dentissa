<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spec 026: el historial de estados de la cita lo escribe la aplicación, una sola fila
 * con autor por cambio (App\Agenda\EstadosCita). Se retira el trigger del volcado, que
 * escribía otra fila sin autor. DDL fijo, sin entrada del usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_historial_cita');
    }

    /**
     * Recrea el trigger con el mismo cuerpo que database/dentissa.sql.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_historial_cita');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_historial_cita` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
              IF OLD.estado <> NEW.estado THEN
                INSERT INTO historial_citas (id, cita_id, estado_anterior, estado_nuevo)
                VALUES (UUID(), NEW.id, OLD.estado, NEW.estado);
              END IF;
            END
            SQL);
    }
};
