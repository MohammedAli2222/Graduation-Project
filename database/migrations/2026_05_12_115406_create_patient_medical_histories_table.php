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
        Schema::create('patient_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');

            $table->boolean('has_general_diseases')->default(false);
            $table->text('general_diseases_details')->nullable();

            $table->boolean('is_special_needs')->default(false);
            $table->text('special_needs_details')->nullable();

            $table->boolean('takes_medications')->default(false);
            $table->text('medications_details')->nullable();

            $table->boolean('has_allergies')->default(false);
            $table->text('allergies_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_histories');
    }
};
