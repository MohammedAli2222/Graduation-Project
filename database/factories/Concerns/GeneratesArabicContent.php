<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * سمة مشتركة توفر مولّد Faker مضبوطاً على اللغة العربية (ar_SA) لاستخدامه
 * في الحقول التي تحتاج أسماء/عناوين/نصوصاً عربية واقعية داخل الـ Factories.
 *
 * نحتفظ بنسخة واحدة ثابتة (static) لكل كلاس بدلاً من استدعاء
 * \Faker\Factory::create('ar_SA') في كل سطر بيانات، لأن إنشاء مولّد Faker
 * جديد لكل صف عند توليد آلاف السجلات يبطئ عملية الـ Seeding بلا داعٍ.
 */
trait GeneratesArabicContent
{
    protected static ?Generator $arabicFaker = null;

    protected function arFaker(): Generator
    {
        return static::$arabicFaker ??= FakerFactory::create('ar_SA');
    }
}
