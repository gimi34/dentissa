<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 039 (plan → Contratos y datos, ADR 0009): fotos de la galería de trabajos. Solo *expand*:
 * la versión anterior ignora la tabla. Los archivos viven en el disco `galeria`
 * (`<id>-1600.webp` y `<id>-800.webp`); aquí solo sus medidas y pesos. `id` y la cuenta que
 * declaró el consentimiento son `char(36)`, como `usuarios.id` (plan D18, /analyze A8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeria_fotos', function (Blueprint $tabla) {
            $tabla->char('id', 36)->primary();
            $tabla->string('descripcion', 160);
            $tabla->unsignedSmallInteger('posicion')->unique();
            $tabla->boolean('con_paciente');
            $tabla->char('consentimiento_usuario_id', 36)->nullable();
            $tabla->string('consentimiento_nombre', 201)->nullable();
            $tabla->timestamp('consentimiento_en')->nullable();
            $tabla->unsignedSmallInteger('ancho');
            $tabla->unsignedSmallInteger('alto');
            $tabla->unsignedInteger('bytes');
            $tabla->unsignedInteger('bytes_pequena');
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();

            $tabla->foreign('consentimiento_usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeria_fotos');
    }
};
