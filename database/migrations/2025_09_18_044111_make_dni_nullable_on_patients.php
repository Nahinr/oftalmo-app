<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Permitir NULL en dni (requiere doctrine/dbal por ->change())
        Schema::table('patients', function (Blueprint $table) {
            $table->string('dni', 15)->nullable()->change();
        });

        // 2) Convertir cadenas vacías existentes a NULL (para no chocar con UNIQUE)
        DB::table('patients')->where('dni', '')->update(['dni' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ⚠️ Si haces rollback y hay múltiples NULL, NOT NULL podría fallar según tus datos.
        Schema::table('patients', function (Blueprint $table) {
            $table->string('dni', 15)->nullable(false)->change();
        });
    }
};
