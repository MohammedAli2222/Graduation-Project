<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('total_amount', 10, 2)->default(0.00);

            $table->enum('status', array_column(OrderStatus::cases(), 'value'))
                  ->default(OrderStatus::PENDING->value);

            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index('student_id');
            $table->index('store_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
