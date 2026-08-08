<?php

use App\Enums\DiagnosisStatus;
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
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->nullable()->constrained('users');

            $table->foreignId('case_type_id')->constrained();
            $table->foreignId('department_id')->constrained();

            $table->foreignId('suggested_by_student_id')->nullable()->constrained('users');

            $table->text('final_diagnosis')->nullable();
            
            $table->decimal('estimated_cost', 10, 2)->default(0);


            $table->index(['case_type_id', 'status']);

            $table->enum('status', array_column(DiagnosisStatus::cases(), 'value'))
                ->default(DiagnosisStatus::AVAILABLE->value);


            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['suggested_by_student_id', 'status'], 'diagnoses_student_status_idx');

            $table->index(['patient_id', 'status'], 'diagnoses_patient_status_idx');
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
