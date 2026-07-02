<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use App\Models\CaseType;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = User::role('receptionist')->first();
        $students = User::role('student')->get();

        // 1. إنشاء 10 مرضى من الاستقبال
        for ($i = 0; $i < 10; $i++) {
            $patient = Patient::create([
                'patient_code' => date('Y') . '-' . Str::upper(Str::random(4)),
                'full_name' => 'مريض استقبال ' . ($i + 1),
                'birth_date' => fake()->date(),
                'gender' => fake()->randomElement(['male', 'female']),
                'address' => fake()->address(),
                'phone' => fake()->phoneNumber(),
                'preliminary_diagnosis' => null,
                'added_by' => $receptionist ? $receptionist->id : null,
                'availability_status' => PatientStatus::WAITING_DIAGNOSIS,
            ]);

            // إنشاء السجل الطبي
            // داخل الـ Loop في PatientSeeder

            $hasGeneral = fake()->boolean();
            $isSpecial = fake()->boolean();
            $takesMeds = fake()->boolean();
            $hasAllergies = fake()->boolean();

            $patient->medicalHistory()->create([
                'has_general_diseases' => $hasGeneral,
                'general_diseases_details' => $hasGeneral ? 'تفاصيل عن الأمراض العامة: ' . fake()->sentence(3) : null,

                'is_special_needs' => $isSpecial,
                'special_needs_details' => $isSpecial ? 'نوع الاحتياجات الخاصة: ' . fake()->word() : null,

                'takes_medications' => $takesMeds,
                'medications_details' => $takesMeds ? 'الأدوية: ' . fake()->words(2, true) : null,

                'has_allergies' => $hasAllergies,
                'allergies_details' => $hasAllergies ? 'نوع الحساسية: ' . fake()->word() : null,
            ]);

            $patient->addMedia(public_path('seeders/images/patient_avatar.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('id_cards');

            $patient->addMedia(public_path('seeders/images/clinical_demo.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('clinical_images');

            $patient->addMedia(public_path('seeders/images/xray_demo.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('x_ray_images');
        }

        // 2. إنشاء 20 مريض من الطلاب
        for ($i = 0; $i < 20; $i++) {
            $student = $students->random();
            $studentProfile = $student->studentProfile;

            $caseType = CaseType::whereHas('course', function ($query) use ($studentProfile) {
                $query->where('year', $studentProfile->academic_year)
                    ->where('semester', $studentProfile->semester);
            })->inRandomOrder()->first();

            if (!$caseType) continue;

            $patient = Patient::create([
                'patient_code' => date('Y') . '-' . Str::upper(Str::random(4)),
                'full_name' => 'مريض طالب ' . ($i + 1),
                'birth_date' => fake()->date(),
                'gender' => fake()->randomElement(['male', 'female']),
                'address' => fake()->address(),
                'phone' => fake()->phoneNumber(),
                'preliminary_diagnosis' => 'تشخيص أولي للشكوى رقم ' . ($i + 1),
                'added_by' => $student->id,
                'availability_status' => PatientStatus::WAITING_DIAGNOSIS,
            ]);

            // إنشاء السجل الطبي
            $hasDiseases = fake()->boolean();
            $takesMeds = fake()->boolean();

            $patient->medicalHistory()->create([
                'has_general_diseases' => $hasDiseases,
                'general_diseases_details' => $hasDiseases ? 'تفاصيل الأمراض التجريبية رقم ' . ($i + 1) : null,

                'takes_medications' => $takesMeds,
                'medications_details' => $takesMeds ? 'أسماء الأدوية التجريبية: باراسيتامول، إيبوبروفين' : null,

                'has_allergies' => false,
                'is_special_needs' => false,
            ]);

            // إنشاء التشخيص والربط الأكاديمي
            $diagnosis = $patient->diagnoses()->create([
                'suggested_by_student_id' => $student->id,
                'case_type_id' => $caseType->id,
                'department_id' => $caseType->course->department_id,
                'status' => DiagnosisStatus::WAITING_APPROVAL,
                'estimated_cost' => rand(100, 1000),
            ]);

            $patient->addMedia(public_path('seeders/images/patient_avatar.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('id_cards');

            $diagnosis->addMedia(public_path('seeders/images/clinical_demo.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('clinical_images');

            $diagnosis->addMedia(public_path('seeders/images/xray_demo.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('x_ray_images');
        }
    }
}
