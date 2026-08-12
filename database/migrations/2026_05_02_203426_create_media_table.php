<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });
    }

    // بدون down() كان migrate:refresh يتخطى هذه الهجرة عند التراجع (لارافيل يستدعي
    // down فقط إذا كانت معرّفة)، فيبقى جدول media موجوداً ثم يفشل up عند إعادة
    // التنفيذ بخطأ "Table 'media' already exists".
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
