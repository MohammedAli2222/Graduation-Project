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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');

            $table->string('phone');

            $table->string('exam_number')->unique();
            $table->string('university');

            $table->unsignedInteger('academic_year');
            $table->unsignedInteger('semester');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
