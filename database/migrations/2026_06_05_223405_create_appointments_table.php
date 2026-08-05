<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('diagnosis_id')->constrained('patient_diagnoses')->onDelete('cascade');

            $table->tinyInteger('slot_number');

            $table->date('appointment_date');

            $table->foreignId('treatment_id')
                  ->nullable()
                  ->constrained('treatments')
                  ->onDelete('set null');

            $table->enum('status', array_column(AppointmentStatus::cases(), 'value'))
                ->default(AppointmentStatus::SCHEDULED->value);
            $table->timestamps();

            $table->unique(['student_id', 'appointment_date', 'slot_number'], 'student_daily_slot_unique');

            // فهرس مركب لتسريع فحص سعة عيادة القسم (عدد الكراسي المحجوزة) في تاريخ وسلوت معيّنين
            // عبر جميع الطلاب دفعة واحدة؛ فهرس الـ unique أعلاه لا يخدم هذا الاستعلام لأنه يبدأ
            // بعمود student_id، بينما فحص السعة (hasConflict -> reservedChairsCount) لا يُصفّي
            // حسب طالب معيّن إطلاقاً
            $table->index(['appointment_date', 'slot_number', 'status'], 'appointments_date_slot_status_idx');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
