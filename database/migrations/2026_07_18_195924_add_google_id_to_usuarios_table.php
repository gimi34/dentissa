<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
        });

        // password_hash debe aceptar NULL para cuentas creadas solo con Google
        DB::statement('ALTER TABLE usuarios MODIFY password_hash TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE usuarios SET password_hash = '' WHERE password_hash IS NULL");
        DB::statement('ALTER TABLE usuarios MODIFY password_hash TEXT NOT NULL');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
