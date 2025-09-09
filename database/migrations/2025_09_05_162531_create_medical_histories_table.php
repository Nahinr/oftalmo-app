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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->bigIncrements('id');                 // medical_history_id (PK)
            $table->unsignedBigInteger('patient_id');    // FK -> patients.id

            // Hallazgos
            $table->text('findings')->nullable();

            // Refracción
            $table->string('refraction_od', 60)->nullable();
            $table->string('refraction_os', 60)->nullable();
            $table->string('refraction_add', 15)->nullable();


            // Tratamiento
            $table->text('tx')->nullable();

            // Fecha de consulta (auto)
            $table->dateTime('visit_date')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->timestamps();

            $table->index(['patient_id', 'visit_date']);
            $table->foreign('patient_id')->references('id')->on('patients')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
