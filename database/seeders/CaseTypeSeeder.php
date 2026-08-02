<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CaseType;
use Illuminate\Database\Seeder;

class CaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $casesData = [
            // Year 4 Sem 1
            'المداواة الترميمية (3)' => [
                ['name' => 'حشوة كومبوزيت (سطح واحد)', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'حشوة كومبوزيت (سطحين)', 'required_count' => 3, 'slots_needed' => 1],
                ['name' => 'حشوة عنقية Class V', 'required_count' => 2, 'slots_needed' => 1],
                ['name' => 'حشوة أملغم خلفية', 'required_count' => 4, 'slots_needed' => 1],
            ],
            'المداواة اللبية (1)' => [
                ['name' => 'علاج عصب أمامي وحيد الجذر', 'required_count' => 4, 'slots_needed' => 1],
                ['name' => 'علاج عصب ضاحك أحادي', 'required_count' => 3, 'slots_needed' => 1],
            ],
            'جراحة الفم (التخدير والقلع 1)' => [
                ['name' => 'قلع سن أمامي وحيد الجذر', 'required_count' => 10, 'slots_needed' => 1],
                ['name' => 'قلع ضرس متعدد الجذور', 'required_count' => 6, 'slots_needed' => 2],
                ['name' => 'حصار عصبي وتخدير موضعي', 'required_count' => 15, 'slots_needed' => 1],
            ],
            'التعويضات الثابتة (3)' => [
                ['name' => 'تحضير تاج معدني خزفي PFM', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'تحضير جسر ثلاثي الوحدات', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'أمراض النسج حول السنية (2)' => [
                ['name' => 'تقليح وتنظيف كامل الفكين', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'تجريف جيوب لثوية', 'required_count' => 3, 'slots_needed' => 1],
                ['name' => 'إزالة جير فوقي وتحت لثوي', 'required_count' => 4, 'slots_needed' => 1],
            ],
            'طب الفم وأمراضه (1)' => [
                ['name' => 'فحص سريري شامل وتشخيص أمراض الفم', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'أخذ طبعات وصور أشعة تشخيصية', 'required_count' => 4, 'slots_needed' => 1],
            ],

            // Year 4 Sem 2
            'المداواة اللبية (2)' => [
                ['name' => 'علاج عصب ضاحك ثنائي', 'required_count' => 3, 'slots_needed' => 2],
                ['name' => 'علاج عصب ضرس خلفي', 'required_count' => 3, 'slots_needed' => 2],
            ],
            'التعويضات المتحركة (2)' => [
                ['name' => 'صنع طقم أسنان جزئي متحرك', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'تعديل وإصلاح طقم أسنان', 'required_count' => 3, 'slots_needed' => 1],
            ],
            'جراحة الفم (التخدير والقلع 2)' => [
                ['name' => 'قلع جراحي لسن مطمور', 'required_count' => 3, 'slots_needed' => 2],
                ['name' => 'استئصال جراحي لكيس فكي', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'طب أسنان الأطفال (2)' => [
                ['name' => 'حشوة لبنية للأطفال', 'required_count' => 5, 'slots_needed' => 1],
                ['name' => 'قلع سن لبني', 'required_count' => 4, 'slots_needed' => 1],
                ['name' => 'تطبيق فلورايد وسد شقوق', 'required_count' => 3, 'slots_needed' => 1],
                ['name' => 'علاج عصب لسن لبني (تقطيع لبي)', 'required_count' => 2, 'slots_needed' => 1],
            ],
            'تقويم الأسنان (1)' => [
                ['name' => 'تركيب جهاز تقويم ثابت علوي', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'تركيب جهاز تقويم متحرك', 'required_count' => 2, 'slots_needed' => 1],
                ['name' => 'متابعة وتعديل جهاز التقويم', 'required_count' => 4, 'slots_needed' => 1],
            ],

            // Year 5 Sem 1
            'المداواة الترميمية السريرية (4)' => [
                ['name' => 'ترميم جمالي متعدد الأسنان بالكومبوزيت', 'required_count' => 3, 'slots_needed' => 2],
                ['name' => 'حشوة خزفية (Inlay/Onlay)', 'required_count' => 2, 'slots_needed' => 2],
            ],
            'المداواة اللبية السريرية (3)' => [
                ['name' => 'علاج عصب ضرس خلفي متعدد الأقنية', 'required_count' => 3, 'slots_needed' => 2],
                ['name' => 'إعادة علاج عصب فاشل', 'required_count' => 1, 'slots_needed' => 2],
                ['name' => 'جراحة طرف جذر (Apicoectomy)', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'التعويضات الثابتة السريرية (4)' => [
                ['name' => 'تاج خزفي كامل (All-Ceramic)', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'جسر خزفي متعدد الوحدات', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'التعويضات المتحركة السريرية (3)' => [
                ['name' => 'طقم أسنان كامل (علوي وسفلي)', 'required_count' => 1, 'slots_needed' => 2],
                ['name' => 'طقم أسنان فوري', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'أمراض النسج حول السنية السريرية (3)' => [
                ['name' => 'جراحة لثوية لتصحيح تضخم اللثة', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'رفع لثوي وإعادة تشكيل العظم', 'required_count' => 1, 'slots_needed' => 2],
                ['name' => 'تطعيم لثوي لمعالجة الانحسار', 'required_count' => 1, 'slots_needed' => 2],
            ],

            // Year 5 Sem 2
            'جراحة الفم والفكين السريرية (2)' => [
                ['name' => 'قلع جراحي معقد وتعديل عظمي للأسنان المطمورة', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'استئصال وإزالة آفة فموية', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'طب أسنان الأطفال السريري (3)' => [
                ['name' => 'تركيب تاج فولاذي جاهز للأسنان اللبنية', 'required_count' => 3, 'slots_needed' => 1],
                ['name' => 'علاج لب جذري في سن لبني', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'جهاز حافظ للمكان', 'required_count' => 2, 'slots_needed' => 1],
            ],
            'تقويم الأسنان والفكين (2)' => [
                ['name' => 'معالجة تقويمية متقدمة لحالة هيكلية', 'required_count' => 1, 'slots_needed' => 2],
                ['name' => 'تركيب وتفعيل جهاز توسيع الحنك', 'required_count' => 1, 'slots_needed' => 2],
                ['name' => 'جهاز ادراكي وظيفي لمشاكل الفكين', 'required_count' => 1, 'slots_needed' => 2],
            ],
            'العيادة الشاملة' => [
                ['name' => 'خطة علاج متكاملة وتأهيل فموي كامل', 'required_count' => 2, 'slots_needed' => 2],
                ['name' => 'فحص وبيان صحة الفم الشامل', 'required_count' => 3, 'slots_needed' => 1],
            ],
        ];

        foreach ($casesData as $courseName => $cases) {
            $course = Course::where('name', $courseName)->first();
            if ($course) {
                foreach ($cases as $case) {
                    CaseType::create([
                        'course_id' => $course->id,
                        'name' => $case['name'],
                        'required_count' => $case['required_count'],
                        'slots_needed' => $case['slots_needed']
                    ]);
                }
            }
        }
    }
}
