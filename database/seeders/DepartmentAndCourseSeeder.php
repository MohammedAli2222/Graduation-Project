<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentAndCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================================================
        // 1. قسم المداواة والترميم
        // ==========================================================================
        $restorative = Department::create([
            'name' => 'قسم المداواة والترميم',
            'description' => 'يشمل الحشوات التجميلية، ترميم الأسنان، وعلاج الجذور وسحب العصب.'
        ]);

        $restorative->courses()->createMany([
            ['name' => 'Operative Dentistry I (مداواة ترميمية 1)', 'year' => '4', 'semester' => '1'],
            ['name' => 'Endodontics I (مداواة لبية 1)', 'year' => '4', 'semester' => '1'],
            ['name' => 'Operative Dentistry II (مداواة ترميمية 2)', 'year' => '4', 'semester' => '2'],
            ['name' => 'Endodontics II (مداواة لبية 2)', 'year' => '4', 'semester' => '2'],
            ['name' => 'Clinical Operative Dentistry (مداواة ترميمية سريرية)', 'year' => '5', 'semester' => '1'],
            ['name' => 'Clinical Endodontics (مداواة لبية سريرية)', 'year' => '5', 'semester' => '1'],
        ]);

        // ==========================================================================
        // 2. قسم جراحة الفم والفكين
        // ==========================================================================
        $surgery = Department::create([
            'name' => 'قسم جراحة الفم والفكين',
            'description' => 'يشمل التخدير، القلع البسيط والجراحي، والعمليات الجراحية الفموية الصغرى والكبرى.'
        ]);

        $surgery->courses()->createMany([
            ['name' => 'Oral Surgery I (جراحة الفم 1 - تخدير وقلع)', 'year' => '4', 'semester' => '1'],
            ['name' => 'Oral Surgery II (جراحة الفم 2)', 'year' => '4', 'semester' => '2'],
            ['name' => 'Oral and Maxillofacial Surgery I (جراحة فم وفكين 1)', 'year' => '5', 'semester' => '1'],
            ['name' => 'Oral and Maxillofacial Surgery II (جراحة فم وفكين 2)', 'year' => '5', 'semester' => '2'],
        ]);

        // ==========================================================================
        // 3. قسم التعويضات السنية
        // ==========================================================================
        $prosthodontics = Department::create([
            'name' => 'قسم التعويضات السنية',
            'description' => 'يشمل التعويضات الثابتة (التيجان والجسور) والتعويضات المتحركة (الكاملة والجزئية).'
        ]);

        $prosthodontics->courses()->createMany([
            ['name' => 'Fixed Prosthodontics I (تعويضات ثابتة 1)', 'year' => '4', 'semester' => '1'],
            ['name' => 'Fixed Prosthodontics II (تعويضات ثابتة 2)', 'year' => '4', 'semester' => '2'],
            ['name' => 'Removable Partial Prosthodontics (تعويضات متحركة)', 'year' => '5', 'semester' => '1'],
        ]);

        // ==========================================================================
        // 4. قسم طب أسنان الأطفال والوقائي والتقويم
        // ==========================================================================
        $pedodonticsAndOrtho = Department::create([
            'name' => 'قسم طب أسنان الأطفال وتقويم الأسنان',
            'description' => 'يشمل معالجة أسنان الأطفال، الأجهزة الوقائية، وتقويم الأسنان وتعديل الإطباق.'
        ]);

        $pedodonticsAndOrtho->courses()->createMany([
            ['name' => 'Pedodontics I (طب أسنان الأطفال 1)', 'year' => '4', 'semester' => '2'],
            ['name' => 'Orthodontics I (تقويم الأسنان 1)', 'year' => '5', 'semester' => '1'],
            ['name' => 'Pedodontics II (طب أسنان الأطفال 2)', 'year' => '5', 'semester' => '2'],
            ['name' => 'Orthodontics II (تقويم الأسنان 2)', 'year' => '5', 'semester' => '2'],
        ]);

        // ==========================================================================
        // 5. قسم أمراض اللثة والأنسجة المحيطة بالأسنان
        // ==========================================================================
        $periodontics = Department::create([
            'name' => 'قسم أمراض اللثة',
            'description' => 'يشمل تجريف اللثة، تقليح الأسنان (تنظيف اللثة)، وعلاجات الأنسجة الداعمة.'
        ]);

        $periodontics->courses()->createMany([
            ['name' => 'Periodontology I (أمراض اللثة 1)', 'year' => '4', 'semester' => '1'],
            ['name' => 'Periodontology II (أمراض اللثة 2)', 'year' => '5', 'semester' => '2'],
        ]);

        // ==========================================================================
        // 6. العيادة الشاملة (قسم عام ومستقل)
        // ==========================================================================
        $comprehensive = Department::create([
            'name' => 'قسم العيادات الشاملة',
            'description' => 'العيادة النهائية لطلاب السنة الخامسة التي تشمل خطة علاج متكاملة للمريض من ألف إلى الياء.'
        ]);

        $comprehensive->courses()->create([
            'name' => 'Comprehensive Clinical Dentistry (العيادة الشاملة)',
            'year' => '5',
            'semester' => '2'
        ]);
    }
}
