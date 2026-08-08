<?php

declare(strict_types=1);

namespace App\Support;


final class CacheGroup
{
    /** المتاجر الرسمية وقوائمها. */
    public const STORES = 'stores';

    /** المنتجات بكل قوائمها (متجر، سوق الطلاب، تفاصيل منتج). */
    public const PRODUCTS = 'products';

    /** منتجات الطلاب في السوق. */
    public const STUDENT_PRODUCTS = 'student_products';

    /** التصنيفات (dropdown). */
    public const CATEGORIES = 'categories';

    /** الأفواج/المجموعات الطلابية. */
    public const GROUPS = 'groups';

    /** أنواع الحالات السريرية وقوائمها المنسدلة. */
    public const CASE_TYPES = 'case_types';

    /** المقررات والتسجيل الأكاديمي. */
    public const COURSES = 'courses';

    /** العلاجات وإحصاءاتها. */
    public const TREATMENTS = 'treatments';

    /** إحصاءات عامة (لوحات التحكم). */
    public const STATISTICS = 'statistics';

    /** إعدادات الأقسام. */
    public const DEPARTMENTS = 'departments';

    /**
     * مجموعة خاصة بمتجر واحد — تتيح إبطال كاش متجر بعينه دون المساس بالباقي.
     */
    public static function store(int $storeId): string
    {
        return "store_{$storeId}";
    }

    /**
     * مجموعة خاصة بطالب واحد (مقرراته، أنواع حالاته، إحصاءات تقدّمه).
     */
    public static function student(int $studentUserId): string
    {
        return "student_{$studentUserId}";
    }

    /**
     * مجموعة خاصة بقسم واحد (يستعملها رئيس القسم).
     */
    public static function department(int $departmentId): string
    {
        return "department_{$departmentId}";
    }
}
