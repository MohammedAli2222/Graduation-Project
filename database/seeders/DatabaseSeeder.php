<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * الترتيب هنا إلزامي وليس تعسفياً: كل Seeder لاحق يعتمد على بيانات
     * أنشأها Seeder سابق عبر مفاتيح خارجية (Foreign Keys)، لذلك يجب تشغيلها
     * بهذا التسلسل بالضبط لتفادي أخطاء انتهاك القيود المرجعية.
     *
     * ملاحظة على الترتيب: نُشغّل AcademicSeeder قبل UserSeeder رغم أن طلب
     * العميل يذكر "المستخدمين" كخطوة 2 و"الهيكل الأكاديمي" كخطوة 3، وذلك لأن
     * عمود student_profiles.group_id مفتاح خارجي إلزامي (NOT NULL) نحو جدول
     * groups — أي لا يمكن إنشاء أي طالب قبل وجود المجموعات فعلياً في القسم.
     */
    public function run(): void
    {
        Cache::flush();

        // 1) الأدوار والصلاحيات
        $this->call(RoleAndPermissionSeeder::class);
        $this->command->info('✔ تم إنشاء الأدوار والصلاحيات.');

        // 2) الهيكل الأكاديمي (أقسام، مقررات، أنواع حالات، مجموعات) — قبل المستخدمين لأسباب الـ FK أعلاه
        $this->call(AcademicSeeder::class);
        $this->command->info('✔ تم بناء الهيكل الأكاديمي (5 أقسام، 20 مقرراً، 50 نوع حالة، 8 مجموعات).');

        // 3) المستخدمون وملفاتهم الشخصية
        $this->call(UserSeeder::class);
        $this->command->info('✔ تم إنشاء المستخدمين (مدير، رؤساء أقسام، معيدون، أصحاب متاجر، طلاب، موظف استقبال).');

        // 4) التسجيلات الأكاديمية للطلاب في المقررات
        $this->call(EnrollmentSeeder::class);
        $this->command->info('✔ تم تسجيل الطلاب في المقررات الدراسية.');

        // 5) المرضى وتاريخهم الطبي وصور توضيحية
        $this->call(PatientSeeder::class);
        $this->command->info('✔ تم إنشاء 500 مريض مع تاريخهم الطبي وصور توضيحية.');

        // 6) السلسلة السريرية الكاملة: تشخيصات -> مواعيد -> معالجات
        $this->call(ClinicalSeeder::class);
        $this->command->info('✔ تم بناء السلسلة السريرية الكاملة (تشخيصات، مواعيد، معالجات).');

        // 7) التجارة الإلكترونية: فئات، منتجات، عروض، طلبات
        $this->call(StoreSeeder::class);
        $this->command->info('✔ تم بناء بيانات التجارة الإلكترونية (فئات، منتجات، عروض، طلبات).');

        // 8) توكنات الإشعارات (الأجهزة)
        $this->call(NotificationSeeder::class);
        $this->command->info('✔ تم إنشاء توكنات إشعارات الأجهزة للمستخدمين.');

        $this->command->info('🎉 اكتملت عملية توليد قاعدة البيانات التجريبية بنجاح!');
    }
}
