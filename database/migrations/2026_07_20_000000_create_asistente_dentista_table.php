<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asistente_dentista', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('asistente_id', 36);
            $table->char('dentista_id', 36);
            $table->timestamp('creado_en')->useCurrent();

            $table->unique(['asistente_id', 'dentista_id'], 'uq_asistente_dentista');
            $table->foreign('asistente_id')->references('id')->on('personal')->onDelete('cascade');
            $table->foreign('dentista_id')->references('id')->on('personal')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistente_dentista');
    }
};
