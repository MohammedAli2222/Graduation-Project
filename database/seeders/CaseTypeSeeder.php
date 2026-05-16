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
        // جلب مادة المداواة الترميمية لإضافة حالات لها
        $course = Course::where('name', 'مداواة ترميمية 1')->first();

        if ($course) {
            $course->caseTypes()->createMany([
                [
                    'name' => 'حشوة كومبوزيت (سطح واحد)',
                    'required_count' => 5 // مطلوب من الطالب يخلص 5 حالات
                ],
                [
                    'name' => 'حشوة أملغم (سطحين)',
                    'required_count' => 3
                ],
                [
                    'name' => 'حشوة عنقية (Class V)',
                    'required_count' => 2
                ]
            ]);
        }

        // جلب مادة القلع
        $surgeryCourse = Course::where('name', 'قلع 1')->first();
        if ($surgeryCourse) {
            $surgeryCourse->caseTypes()->createMany([
                [
                    'name' => 'قلع سن وحيد الجذر',
                    'required_count' => 10
                ],
                [
                    'name' => 'قلع ضرس متعدد الجذور',
                    'required_count' => 5
                ]
            ]);
        }
    }
}
