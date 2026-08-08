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
        Schema::table('patient_medical_histories', function (Blueprint $table) {
            $table->boolean('is_pregnant')->nullable()->after('allergies_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_medical_histories', function (Blueprint $table) {
            $table->dropColumn('is_pregnant');
        });
    }
};
