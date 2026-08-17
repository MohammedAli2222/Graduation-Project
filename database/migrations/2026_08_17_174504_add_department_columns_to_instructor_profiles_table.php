<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_profiles', function (Blueprint $table) {
            // القسم الذي يتبع له المعيد فعلياً بعد اعتماد طلب التفويض. لم يكن
            // موجوداً أصلاً رغم أن DepartmentTreatmentController@completedTreatments
            // يعتمد عليه بالفعل عبر $user->instructorProfile?->department_id —
            // أي أن نقطة "استعراض معالجات القسم" لأي معيد مفوَّض كانت معطوبة
            // بصمت (403 دائماً) لغياب هذا العمود، بغضّ النظر عن منح الصلاحية.
            $table->foreignId('department_id')->nullable()
                ->after('specialty_year')
                ->constrained('departments')
                ->nullOnDelete();

            // طلب تفويض معلَّق بانتظار موافقة رئيس القسم؛ يُصفَّر عند القبول
            // (بعد نقل قيمته إلى department_id) أو عند الرفض.
            $table->foreignId('requested_department_id')->nullable()
                ->after('department_id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instructor_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_department_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
