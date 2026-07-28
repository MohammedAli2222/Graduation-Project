<?php

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل التهجير وإنشاء الجداول مع الفهارس المحسنة.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // المفاتيح الأجنبية (تقوم Laravel بإنشاء فهارس لها تلقائياً بسبب استخدام constrained)
            $table->foreignId('store_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

            $table->string('name');
            $table->text('description');

            $table->decimal('price', 10, 2);

            $table->string('brand')->nullable();

            // حالات المنتج باستخدام الـ Enums
            $table->enum('availability_status', array_column(ProductAvailability::cases(), 'value'))
                ->default(ProductAvailability::AVAILABLE->value);

            $table->enum('condition', array_column(ProductCondition::cases(), 'value'))
                ->default(ProductCondition::NEW->value);

            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            $table->index('availability_status');
            $table->index('condition');
            $table->index('created_at');

            $table->fullText(['name', 'description']);
        });
    }

    /**
     * التراجع عن التهجير.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
