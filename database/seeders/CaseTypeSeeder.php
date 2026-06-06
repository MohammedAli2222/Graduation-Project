<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CaseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1️⃣ مادة Mداواة الترميمية 1 (سنة رابعة - فصل أول)
        $operative1 = Course::where('name', 'like', '%مداواة ترميمية 1%')->first();
        if ($operative1) {
            $operative1->caseTypes()->createMany([
                ['name' => 'حشوة كومبوزيت تجميلية (سطح واحد)', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'حشوة كومبوزيت تجميلية (سطحين أو أكثر)', 'required_count' => 3, 'slots_needed' => 1],
                ['name' => 'حشوة عنقية (Class V)', 'required_count' => 2, 'slots_needed' => 1],
            ]);
        }

        // 2️⃣ مادة المداواة اللبية 1 - سحب عصب (سنة رابعة - فصل أول)
        $endo1 = Course::where('name', 'like', '%مداواة لبية 1%')->first();
        if ($endo1) {
            $endo1->caseTypes()->createMany([
                ['name' => 'علاج عصب لسن أمامي (وحيد الجذر)', 'required_count' => 4, 'slots_needed' => 1],
                ['name' => 'علاج عصب لضاحك (Premolar)', 'required_count' => 2, 'slots_needed' => 1],
            ]);
        }

        // 3️⃣ مادة جراحة الفم 1 - التخدير والقلع (سنة رابعة - فصل أول)
        $surgery1 = Course::where('name', 'like', '%جراحة الفم 1%')->first();
        if ($surgery1) {
            $surgery1->caseTypes()->createMany([
                ['name' => 'قلع سن أمامي وحيد الجذر', 'required_count' => 10, 'slots_needed' => 1],
                // 🟢 حالات قلع الأضراس متعددة الجذور قد تتعرض للاختلاط وتأخذ وقت أطول (ساعتين أو أكثر)
                ['name' => 'قلع ضرس سفلي أو علوي متعدد الجذور', 'required_count' => 8, 'slots_needed' => 2],
                ['name' => 'إعطاء تخدير موضعي (حصار عصب وتسللي)', 'required_count' => 15, 'slots_needed' => 1],
            ]);
        }

        // 4️⃣ مادة التعويضات الثابتة 1 - التيجان والجسور (سنة رابعة - فصل أول)
        $fixed1 = Course::where('name', 'like', '%تعويضات ثابتة 1%')->first();
        if ($fixed1) {
            $fixed1->caseTypes()->createMany([
                // 🟢 تحضير السن وأخذ الطبعات الدقيقة للتيجان يحتاج حجز فترتين متتاليتين (Block Slots)
                ['name' => 'تحضير وطبع تاج معدني خزفي (PFM)', 'required_count' => 2, 'slots_needed' => 2],
            ]);
        }

        // 5️⃣ مادة أمراض اللثة 1 (سنة رابعة - فصل أول)
        $perio1 = Course::where('name', 'like', '%أمراض اللثة 1%')->first();
        if ($perio1) {
            $perio1->caseTypes()->createMany([
                ['name' => 'تقليح وتنظيف لثة كامل الفكين (Scaling)', 'required_count' => 6, 'slots_needed' => 1],
                ['name' => 'تخطيط وتجريف جيوب لثوية (Root Planing)', 'required_count' => 3, 'slots_needed' => 1],
            ]);
        }

        // 6️⃣ مادة طب أسنان الأطفال 1 (سنة رابعة - فصل ثاني)
        $pedodontics1 = Course::where('name', 'like', '%طب أسنان الأطفال 1%')->first();
        if ($pedodontics1) {
            $pedodontics1->caseTypes()->createMany([
                ['name' => 'حشوة أسنان لبنية للأطفال', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'قلع سن لبني ممتص الجذور', 'required_count' => 4, 'slots_needed' => 1],
                ['name' => 'تطبيق فلورايد وقائي وسد الشقوق (Fissure Sealant)', 'required_count' => 3, 'slots_needed' => 1],
            ]);
        }

        // 7️⃣ مادة المداواة اللبية السريرية - سحب عصب (سنة خامسة - فصل أول)
        $endoClinical = Course::where('name', 'like', '%مداواة لبية سريرية%')->first();
        if ($endoClinical) {
            $endoClinical->caseTypes()->createMany([
                // 🟢 سحب عصب ضرس خلفي متعدد الأقنية معقد جداً ويحتاج 4 ساعات (فترتين)
                ['name' => 'علاج عصب لضرس خلفي (Molar - متعدد الأقنية)', 'required_count' => 3, 'slots_needed' => 2],
                // 🟢 إعادة علاج العصب الفاشل يتطلب إزالة الحشوة القديمة وتطهير أطول فيحتاج فترتين
                ['name' => 'إعادة علاج عصب فاشل (Retreatment)', 'required_count' => 1, 'slots_needed' => 2],
            ]);
        }

        // 8️⃣ مادة العيادة الشاملة (سنة خامسة - فصل ثاني)
        $comprehensive = Course::where('name', 'like', '%العيادة الشاملة%')->first();
        if ($comprehensive) {
            $comprehensive->caseTypes()->createMany([
                // 🟢 خطة علاج متكاملة وتأهيل فموي كامل تفحص كامل الفم وتحتاج فترتين
                ['name' => 'خطة علاج متكاملة لمريض (تأهيل فموي كامل)', 'required_count' => 2, 'slots_needed' => 2],
            ]);
        }
    }
}
