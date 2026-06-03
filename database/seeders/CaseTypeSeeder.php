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
        // 1️⃣ مادة المداواة الترميمية 1 (سنة رابعة - فصل أول)
        $operative1 = Course::where('name', 'like', '%مداواة ترميمية 1%')->first();
        if ($operative1) {
            $operative1->caseTypes()->createMany([
                ['name' => 'حشوة كومبوزيت تجميلية (سطح واحد)', 'required_count' => 5],
                ['name' => 'حشوة كومبوزيت تجميلية (سطحين أو أكثر)', 'required_count' => 3],
                ['name' => 'حشوة عنقية (Class V)', 'required_count' => 2],
            ]);
        }

        // 2️⃣ مادة المداواة اللبية 1 - سحب عصب (سنة رابعة - فصل أول)
        $endo1 = Course::where('name', 'like', '%مداواة لبية 1%')->first();
        if ($endo1) {
            $endo1->caseTypes()->createMany([
                ['name' => 'علاج عصب لسن أمامي (وحيد الجذر)', 'required_count' => 4],
                ['name' => 'علاج عصب لضاحك (Premolar)', 'required_count' => 2],
            ]);
        }

        // 3️⃣ مادة جراحة الفم 1 - التخدير والقلع (سنة رابعة - فصل أول)
        $surgery1 = Course::where('name', 'like', '%جراحة الفم 1%')->first();
        if ($surgery1) {
            $surgery1->caseTypes()->createMany([
                ['name' => 'قلع سن أمامي وحيد الجذر', 'required_count' => 10],
                ['name' => 'قلع ضرس سفلي أو علوي متعدد الجذور', 'required_count' => 8],
                ['name' => 'إعطاء تخدير موضعي (حصار عصب وتسللي)', 'required_count' => 15],
            ]);
        }

        // 4️⃣ مادة التعويضات الثابتة 1 - التيجان والجسور (سنة رابعة - فصل أول)
        $fixed1 = Course::where('name', 'like', '%تعويضات ثابتة 1%')->first();
        if ($fixed1) {
            $fixed1->caseTypes()->createMany([
                ['name' => 'تحضير وطبع تاج معدني خزفي (PFM)', 'required_count' => 2],
            ]);
        }

        // 5️⃣ مادة أمراض اللثة 1 (سنة رابعة - فصل أول)
        $perio1 = Course::where('name', 'like', '%أمراض اللثة 1%')->first();
        if ($perio1) {
            $perio1->caseTypes()->createMany([
                ['name' => 'تقليح وتنظيف لثة كامل الفكين (Scaling)', 'required_count' => 6],
                ['name' => 'تخطيط وتجريف جيوب لثوية (Root Planing)', 'required_count' => 3],
            ]);
        }

        // 6️⃣ مادة طب أسنان الأطفال 1 (سنة رابعة - فصل ثاني)
        $pedodontics1 = Course::where('name', 'like', '%طب أسنان الأطفال 1%')->first();
        if ($pedodontics1) {
            $pedodontics1->caseTypes()->createMany([
                ['name' => 'حشوة أسنان لبنية للأطفال', 'required_count' => 5],
                ['name' => 'قلع سن لبني ممتص الجذور', 'required_count' => 4],
                ['name' => 'تطبيق فلورايد وقائي وسد الشقوق (Fissure Sealant)', 'required_count' => 3],
            ]);
        }

        // 7️⃣ مادة المداواة اللبية السريرية - سحب عصب (سنة خامسة - فصل أول)
        $endoClinical = Course::where('name', 'like', '%مداواة لبية سريرية%')->first();
        if ($endoClinical) {
            $endoClinical->caseTypes()->createMany([
                ['name' => 'علاج عصب لضرس خلفي (Molar - متعدد الأقنية)', 'required_count' => 3],
                ['name' => 'إعادة علاج عصب فاشل (Retreatment)', 'required_count' => 1],
            ]);
        }

        // 8️⃣ مادة العيادة الشاملة (سنة خامسة - فصل ثاني)
        $comprehensive = Course::where('name', 'like', '%العيادة الشاملة%')->first();
        if ($comprehensive) {
            $comprehensive->caseTypes()->createMany([
                ['name' => 'خطة علاج متكاملة لمريض (تأهيل فموي كامل)', 'required_count' => 2],
            ]);
        }
    }
}
