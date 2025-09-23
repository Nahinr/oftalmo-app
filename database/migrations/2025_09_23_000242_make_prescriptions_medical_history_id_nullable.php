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
        Schema::table('prescriptions', function (Blueprint $table) {
                       
            $table->dropForeign(['medical_history_id']);


            $table->unsignedBigInteger('medical_history_id')->nullable()->change();


            $table->foreign('medical_history_id')
                  ->references('id')->on('medical_histories')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
             $table->dropForeign(['medical_history_id']);
            $table->unsignedBigInteger('medical_history_id')->nullable(false)->change();
            $table->foreign('medical_history_id')
                  ->references('id')->on('medical_histories')
                  ->onDelete('cascade'); 
        });
    }
};
