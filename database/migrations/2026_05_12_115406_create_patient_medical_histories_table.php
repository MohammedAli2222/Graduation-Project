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

            $table->boolean('has_general_diseases')->default(false); // هل يعاني من أمراض عامة
            $table->text('general_diseases_details')->nullable();    // تفاصيل الأمراض

            $table->boolean('is_special_needs')->default(false);    // هل المريض من ذوي الاحتياجات الخاصة
            $table->text('special_needs_details')->nullable();

            $table->boolean('takes_medications')->default(false);   // هل يتناول أدوية حالياً
            $table->text('medications_details')->nullable();

            $table->boolean('has_allergies')->default(false);       // هل يعاني من حساسية أدوية
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
