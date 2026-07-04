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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->constrained('users')->cascadeOnDelete();

            // تفاصيل العرض
            $table->string('title');
            $table->text('description')->nullable();

            // نسبة الخصم (من 0.00 إلى 100.00)
            $table->decimal('discount_percentage', 5, 2);

            // الجدولة الزمنية للعرض
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // التفعيل والإيقاف اليدوي
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
