<?php

use App\Enums\PatientStatus;
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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_code')->unique();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('phone')->index();
            $table->string('address')->nullable();
            $table->text('preliminary_diagnosis')->nullable();
            $table->enum('availability_status', array_column(PatientStatus::cases(), 'value'))
                ->default(PatientStatus::WAITING_DIAGNOSIS->value);
            $table->foreignId('added_by')->constrained('users');
            $table->timestamps();

            $table->index(['availability_status', 'created_at'], 'patients_availability_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
