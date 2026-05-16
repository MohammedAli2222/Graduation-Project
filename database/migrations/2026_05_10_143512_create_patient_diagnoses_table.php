<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DiagnosisStatus;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users');

            $table->foreignId('case_type_id')->constrained();
            $table->foreignId('department_id')->constrained();

            $table->foreignId('suggested_by_student_id')->nullable()->constrained('users');

            $table->text('final_diagnosis');


            $table->enum('status', array_column(DiagnosisStatus::cases(), 'value'))
                ->default(DiagnosisStatus::AVAILABLE->value);

            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_diagnoses');
    }
};
