<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Course;
use Illuminate\Database\Seeder;

class DepartmentAndCourseSeeder extends Seeder
{
    public function run(): void
    {
        $departmentsData = [
            'Restorative' => Department::create(['name' => 'قسم المداواة والترميم', 'total_chairs' => 12, 'description' => 'يعنى هذا القسم بالمداواة الترميمية واللبية']),
            'Oral Surgery' => Department::create(['name' => 'قسم جراحة الفم والفكين', 'total_chairs' => 8, 'description' => 'يعنى بقلع الأسنان والعمليات الجراحية الصغرى والكبرى']),
            'Prosthodontics' => Department::create(['name' => 'قسم التعويضات السنية', 'total_chairs' => 10, 'description' => 'يختص بالتعويضات الثابتة والمتحركة']),
            'Pediatric' => Department::create(['name' => 'قسم طب أسنان الأطفال والوقائي', 'total_chairs' => 6, 'description' => 'مختص بمعالجة أسنان الأطفال والإجراءات الوقائية']),
            'Orthodontics' => Department::create(['name' => 'قسم تقويم الأسنان والفكين', 'total_chairs' => 8, 'description' => 'يعنى بتقويم الأسنان وتعديل التشوهات الفكية']),
            'Periodontics' => Department::create(['name' => 'قسم أمراض النسج حول السنية', 'total_chairs' => 10, 'description' => 'مختص بأمراض اللثة والنسج الداعمة']),
            'Oral Medicine' => Department::create(['name' => 'قسم طب الفم وأمراضه', 'total_chairs' => 6, 'description' => 'مختص بالتشخيص وأمراض الفم والأشعة']),
        ];

        $coursesData = [
            // Year 4 Semester 1
            ['name' => 'المداواة الترميمية (3)', 'department_id' => $departmentsData['Restorative']->id, 'year' => 4, 'semester' => 1],
            ['name' => 'المداواة اللبية (1)', 'department_id' => $departmentsData['Restorative']->id, 'year' => 4, 'semester' => 1],
            ['name' => 'جراحة الفم (التخدير والقلع 1)', 'department_id' => $departmentsData['Oral Surgery']->id, 'year' => 4, 'semester' => 1],
            ['name' => 'التعويضات الثابتة (3)', 'department_id' => $departmentsData['Prosthodontics']->id, 'year' => 4, 'semester' => 1],
            ['name' => 'أمراض النسج حول السنية (2)', 'department_id' => $departmentsData['Periodontics']->id, 'year' => 4, 'semester' => 1],
            ['name' => 'طب الفم وأمراضه (1)', 'department_id' => $departmentsData['Oral Medicine']->id, 'year' => 4, 'semester' => 1],
            
            // Year 4 Semester 2
            ['name' => 'المداواة اللبية (2)', 'department_id' => $departmentsData['Restorative']->id, 'year' => 4, 'semester' => 2],
            ['name' => 'التعويضات المتحركة (2)', 'department_id' => $departmentsData['Prosthodontics']->id, 'year' => 4, 'semester' => 2],
            ['name' => 'جراحة الفم (التخدير والقلع 2)', 'department_id' => $departmentsData['Oral Surgery']->id, 'year' => 4, 'semester' => 2],
            ['name' => 'طب أسنان الأطفال (2)', 'department_id' => $departmentsData['Pediatric']->id, 'year' => 4, 'semester' => 2],
            ['name' => 'تقويم الأسنان (1)', 'department_id' => $departmentsData['Orthodontics']->id, 'year' => 4, 'semester' => 2],

            // Year 5 Semester 1
            ['name' => 'المداواة الترميمية السريرية (4)', 'department_id' => $departmentsData['Restorative']->id, 'year' => 5, 'semester' => 1],
            ['name' => 'المداواة اللبية السريرية (3)', 'department_id' => $departmentsData['Restorative']->id, 'year' => 5, 'semester' => 1],
            ['name' => 'التعويضات الثابتة السريرية (4)', 'department_id' => $departmentsData['Prosthodontics']->id, 'year' => 5, 'semester' => 1],
            ['name' => 'التعويضات المتحركة السريرية (3)', 'department_id' => $departmentsData['Prosthodontics']->id, 'year' => 5, 'semester' => 1],
            ['name' => 'أمراض النسج حول السنية السريرية (3)', 'department_id' => $departmentsData['Periodontics']->id, 'year' => 5, 'semester' => 1],

            // Year 5 Semester 2
            ['name' => 'جراحة الفم والفكين السريرية (2)', 'department_id' => $departmentsData['Oral Surgery']->id, 'year' => 5, 'semester' => 2],
            ['name' => 'طب أسنان الأطفال السريري (3)', 'department_id' => $departmentsData['Pediatric']->id, 'year' => 5, 'semester' => 2],
            ['name' => 'تقويم الأسنان والفكين (2)', 'department_id' => $departmentsData['Orthodontics']->id, 'year' => 5, 'semester' => 2],
            ['name' => 'العيادة الشاملة', 'department_id' => $departmentsData['Oral Medicine']->id, 'year' => 5, 'semester' => 2],
        ];

        foreach ($coursesData as $course) {
            Course::create($course);
        }
    }
}
