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

        // 5) فئات المتجر فقط (بيانات مرجعية يحتاجها صاحب المتجر لإضافة منتجاته)
        $this->call(StoreSeeder::class);
        $this->command->info('✔ تم إنشاء فئات المتجر.');

        /*
         * السيدرات التالية معطّلة لأن الاختبار الحالي يجري على بيانات حقيقية
         * تُدخل من التطبيق نفسه (مرضى من الاستقبال، تشخيصات من المعيد، ...).
         * لإعادة توليد البيانات الوهمية الضخمة، أزل التعليق عنها:
         *
         * $this->call(PatientSeeder::class);      // 500 مريض بتاريخهم الطبي وصورهم
         * $this->call(ClinicalSeeder::class);     // تشخيصات + مواعيد + معالجات
         * $this->call(NotificationSeeder::class); // توكنات أجهزة FCM (الإشعارات معطّلة حالياً أصلاً)
         */

        $this->command->info('🎉 اكتملت تهيئة البيانات الأساسية بنجاح!');
    }
}
