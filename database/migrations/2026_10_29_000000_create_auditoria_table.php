<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 020 (ADR 0004): registro de auditoría común, solo de anexado. Guarda
 * identificadores y nombres de campo, nunca valores personales ni de salud
 * (CA3). `id` autoincremental y no UUID porque es un registro ordenado de solo
 * inserción (plan D6); `actor_id` sin clave foránea para que el evento
 * sobreviva al usuario. `down()` solo se usa en desarrollo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->dateTime('ocurrido_en', 6)->index();
            $tabla->char('actor_id', 36)->nullable();
            $tabla->string('actor_rol', 20)->nullable();
            $tabla->string('identificador_intentado', 255)->nullable();
            $tabla->string('accion', 60)->index();
            $tabla->string('recurso_tipo', 60)->nullable();
            $tabla->string('recurso_id', 64)->nullable();
            $tabla->string('resultado', 30);
            $tabla->json('campos')->nullable();
            $tabla->json('detalle')->nullable();
            $tabla->string('ip', 45)->nullable();
            $tabla->char('request_id', 36)->index();

            $tabla->index(['recurso_tipo', 'recurso_id']);
        });

        // Solo de anexado (CA2; TM1, TM2): ningún evento se modifica, y solo se borra en la purga
        // (indicador @auditoria_purga) y si tiene más de 5 años, medidos en UTC como ocurrido_en.
        // SQL estático, sin entrada del usuario.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER auditoria_sin_update BEFORE UPDATE ON auditoria FOR EACH ROW
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: solo anexado'
            SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER auditoria_sin_delete BEFORE DELETE ON auditoria FOR EACH ROW
              IF COALESCE(@auditoria_purga, 0) <> 1 OR OLD.ocurrido_en >= UTC_TIMESTAMP() - INTERVAL 5 YEAR THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: borrado no permitido';
              END IF
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS auditoria_sin_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS auditoria_sin_update');
        Schema::dropIfExists('auditoria');
    }
};
