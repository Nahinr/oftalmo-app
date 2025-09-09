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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->bigIncrements('id');                      // prescription_id (PK)
            $table->unsignedBigInteger('medical_history_id'); // FK -> medical_histories.id

            $table->text('medications_description')->nullable(); // texto libre de medicamentos
            $table->text('diagnosis')->nullable();               // diagnóstico impreso
            $table->dateTime('issued_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->timestamps();

            $table->index(['medical_history_id', 'issued_at']);
            $table->foreign('medical_history_id')->references('id')->on('medical_histories')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
