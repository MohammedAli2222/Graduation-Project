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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('department_id')->constrained()->onDelete('cascade');

            $table->unsignedInteger('year');
            $table->unsignedInteger('semester');
            $table->timestamps();

            // فهرس مركب لأن التسجيل التلقائي بالمقررات (autoEnrollStudentCourses) وتصنيف
            // أنواع الحالات حسب الفصل الحالي/السابق (getCategorizedCaseTypes) يُصفّيان باستمرار
            // حسب السنة والفصل الدراسي معاً، وهما استعلامان يُنفَّذان مع كل طالب تقريباً
            $table->index(['year', 'semester'], 'courses_year_semester_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
