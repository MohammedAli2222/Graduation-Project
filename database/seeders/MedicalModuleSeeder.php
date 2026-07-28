<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CaseType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\PatientMedicalHistory;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Seeder;

class MedicalModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. التأكد من وجود بيانات أساسية (نعتمد على وجود أقسام وحالات وطلاب مسبقاً)
        $departments = Department::all();
        $caseTypes = CaseType::all();
        $students = User::role('student')->get();
        $instructors = User::role('instructor')->get(); // بافتراض وجود دور instructor

        if ($departments->isEmpty() || $caseTypes->isEmpty() || $students->isEmpty()) {
            $this->command->warn('يرجى عمل Seed للأقسام (Departments)، والحالات (CaseTypes)، والطلاب أولاً.');
            return;
        }

        $this->command->info('جاري بناء النظام الطبي السني وإنشاء السجلات المترابطة...');

        // 2. إنشاء 50 مريضاً كعينة ممتازة
        Patient::factory(50)->make()->each(function (Patient $patient) use ($students, $departments, $caseTypes, $instructors) {

            // ربط المريض بطالب عشوائي
            $student = $students->random();
            $patient->added_by = $student->id;
            $patient->save();

            // 3. إنشاء التاريخ الطبي للمريض
            PatientMedicalHistory::factory()->create([
                'patient_id' => $patient->id,
            ]);

            // 4. إنشاء 1 إلى 2 تشخيص لكل مريض
            $diagnosesCount = rand(1, 2);
            for ($i = 0; $i < $diagnosesCount; $i++) {
                $diagnosis = PatientDiagnose::factory()->create([
                    'patient_id' => $patient->id,
                    'department_id' => $departments->random()->id,
                    'case_type_id' => $caseTypes->random()->id,
                    'suggested_by_student_id' => $student->id,
                    'instructor_id' => $instructors->isNotEmpty() ? $instructors->random()->id : null,
                ]);

                // 5. إنشاء خطة علاج (Treatment) لـ 70% من التشخيصات
                $treatment = null;
                if (rand(1, 100) <= 70) {
                    $treatment = Treatment::factory()->create([
                        'diagnosis_id' => $diagnosis->id,
                        'instructor_id' => $diagnosis->instructor_id,
                    ]);
                }

                // 6. إنشاء 1 إلى 3 مواعيد (Appointments) بناءً على هذا التشخيص
                $appointmentsCount = rand(1, 3);
                Appointment::factory($appointmentsCount)->create([
                    'patient_id' => $patient->id,
                    'student_id' => $student->id,
                    'diagnosis_id' => $diagnosis->id,
                    'treatment_id' => $treatment ? $treatment->id : null,
                ]);
            }
        });

        $this->command->info('تم الانتهاء بنجاح! تم إنشاء مرضى، تواريخ طبية، تشخيصات، علاجات، ومواعيد مترابطة بشكل كامل.');
    }
}
