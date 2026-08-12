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
     * البيانات الأساسية اللازمة لتشغيل التطبيق واختباره على بيانات حقيقية
     * تُدخل من التطبيق نفسه (المرضى من الاستقبال، التشخيصات من المعيد، ...).
     *
     * الترتيب إلزامي وليس تعسفياً — كل Seeder يعتمد على مفاتيح خارجية أنشأها ما قبله:
     *   الأدوار → الهيكل الأكاديمي → المستخدمون → المتجر
     *
     * سبب تقديم AcademicSeeder على UserSeeder تحديداً: عمود student_profiles.group_id
     * مفتاح خارجي إلزامي (NOT NULL) نحو جدول groups، فلا يمكن إنشاء أي طالب قبل
     * وجود المجموعات فعلياً.
     */
    public function run(): void
    {
        // تفريغ الكاش أولاً حتى لا تبقى صلاحيات Spatie أو قوائم مخزّنة من تشغيل سابق
        Cache::flush();

        // 1) الأدوار والصلاحيات (يجب أن تسبق أي assignRole)
        $this->call(RoleAndPermissionSeeder::class);
        $this->command->info('✔ الأدوار والصلاحيات.');

        // 2) الهيكل الأكاديمي: قسمان، مجموعتان، 4 مقررات، 5 حالات سريرية
        $this->call(AcademicSeeder::class);
        $this->command->info('✔ الهيكل الأكاديمي (قسمان، مجموعتان، 4 مقررات، 5 حالات سريرية).');

        // 3) مستخدم واحد لكل دور مع بروفايله وربطه بالمجموعات/الأقسام وتسجيله في المقررات
        $this->call(UserSeeder::class);
        $this->command->info('✔ المستخدمون وبروفايلاتهم (كلمة المرور: password).');

        // 4) المتجر: 4 تصنيفات و8 منتجات مملوكة لحساب صاحب المتجر
        $this->call(StoreSeeder::class);
        $this->command->info('✔ بيانات المتجر (4 تصنيفات، 8 منتجات).');

        /*
         * السيدرات التالية معطّلة لأنها تولّد بيانات وهمية ضخمة لا لزوم لها
         * أثناء الاختبار على بيانات حقيقية. لإعادة تفعيلها أزل التعليق:
         *
         * $this->call(PatientSeeder::class);      // 500 مريض بتاريخهم الطبي وصورهم
         * $this->call(ClinicalSeeder::class);     // تشخيصات + مواعيد + معالجات
         * $this->call(NotificationSeeder::class); // توكنات أجهزة FCM (الإشعارات معطّلة حالياً أصلاً)
         */

        $this->command->info('🎉 اكتملت تهيئة البيانات الأساسية بنجاح!');
    }
}
