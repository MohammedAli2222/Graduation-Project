<?php

use App\Enums\TreatmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diagnosis_id')->constrained('patient_diagnoses')->onDelete('cascade');

            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');


            $table->enum('status', array_column(TreatmentStatus
                ::cases(), 'value'))
                ->default(TreatmentStatus::IN_PROGRESS->value);

            $table->text('rejection_reason')->nullable();

            $table->timestamp('start_date')->useCurrent();
            $table->timestamp('end_date')->nullable();

            $table->text('instructor_notes')->nullable();
            $table->timestamps();

            // فهرس على عمود status لأنه شرط التصفية الأساسي في عدة استعلامات متكررة لا تملك
            // أي فهرس عليه حالياً: قائمة المعالجات بانتظار موافقة المعيد
            // (getPendingApprovalsListForInstructor)، قائمة المعالجات المكتملة لرئيس القسم
            // (getOptimizedCompletedTreatments)، وإحصائيات القسم (getDepartmentTreatmentStatistics)
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
