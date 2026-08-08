<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();

            // كل مستخدم يمكن أن يملك أكثر من توكن (تسجيل دخول من عدة أجهزة)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('device_type')->nullable();

            // التوكن فريد لأنه يمثل جهازاً محدداً، ويُستخدم لتحديث/حذف السجل عند إعادة استخدامه
            $table->string('fcm_token')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
