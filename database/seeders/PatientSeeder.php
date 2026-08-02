<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\CaseType;
use App\Enums\PatientStatus;
use App\Enums\DiagnosisStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = User::role('receptionist')->first();
        $students = User::role('student')->get();
        
        if (!$receptionist || $students->isEmpty()) {
            return;
        }

        $names = [
            ['first' => 'سعيد', 'last' => 'المصري', 'gender' => 'male'],
            ['first' => 'لينا', 'last' => 'الأحمد', 'gender' => 'female'],
            ['first' => 'ياسر', 'last' => 'الشيخ', 'gender' => 'male'],
            ['first' => 'منى', 'last' => 'الخطيب', 'gender' => 'female'],
            ['first' => 'رامي', 'last' => 'الزين', 'gender' => 'male'],
            ['first' => 'هبة', 'last' => 'السيد', 'gender' => 'female'],
            ['first' => 'نادر', 'last' => 'العمر', 'gender' => 'male'],
            ['first' => 'سماح', 'last' => 'الأسعد', 'gender' => 'female'],
            ['first' => 'طارق', 'last' => 'النابلسي', 'gender' => 'male'],
            ['first' => 'رندة', 'last' => 'البني', 'gender' => 'female'],
            ['first' => 'بهاء', 'last' => 'الحسين', 'gender' => 'male'],
            ['first' => 'عبير', 'last' => 'الصالح', 'gender' => 'female'],
            ['first' => 'ماهر', 'last' => 'الكردي', 'gender' => 'male'],
            ['first' => 'سلوى', 'last' => 'القاضي', 'gender' => 'female'],
            ['first' => 'وسيم', 'last' => 'السلمان', 'gender' => 'male'],
            ['first' => 'ناديا', 'last' => 'الرفاعي', 'gender' => 'female'],
            ['first' => 'غسان', 'last' => 'الجابر', 'gender' => 'male'],
            ['first' => 'ريما', 'last' => 'العثمان', 'gender' => 'female'],
            ['first' => 'فؤاد', 'last' => 'الدرويش', 'gender' => 'male'],
            ['first' => 'مايا', 'last' => 'الحمصي', 'gender' => 'female'],
            ['first' => 'زياد', 'last' => 'نجار', 'gender' => 'male'],
            ['first' => 'دانيا', 'last' => 'حداد', 'gender' => 'female'],
            ['first' => 'حسن', 'last' => 'شامي', 'gender' => 'male'],
            ['first' => 'رشا', 'last' => 'حمصي', 'gender' => 'female'],
            ['first' => 'عادل', 'last' => 'ديب', 'gender' => 'male'],
            ['first' => 'لمى', 'last' => 'عباس', 'gender' => 'female'],
            ['first' => 'سامي', 'last' => 'سليمان', 'gender' => 'male'],
            ['first' => 'مرح', 'last' => 'إبراهيم', 'gender' => 'female'],
            ['first' => 'مجد', 'last' => 'محمود', 'gender' => 'male'],
            ['first' => 'غنى', 'last' => 'يوسف', 'gender' => 'female'],
            ['first' => 'وائل', 'last' => 'صالح', 'gender' => 'male'],
            ['first' => 'يارا', 'last' => 'طالب', 'gender' => 'female'],
            ['first' => 'قيس', 'last' => 'علي', 'gender' => 'male'],
            ['first' => 'جنى', 'last' => 'حسن', 'gender' => 'female'],
            ['first' => 'أيهم', 'last' => 'حسين', 'gender' => 'male'],
            ['first' => 'شام', 'last' => 'قاسم', 'gender' => 'female'],
            ['first' => 'كرم', 'last' => 'خليل', 'gender' => 'male'],
            ['first' => 'روان', 'last' => 'عثمان', 'gender' => 'female'],
            ['first' => 'نور', 'last' => 'سعد', 'gender' => 'male'],
            ['first' => 'لين', 'last' => 'منصور', 'gender' => 'female'],
        ];

        $addresses = [
            'دمشق - المزة - شارع الجلاء', 'دمشق - كفرسوسة - حي المطار', 'دمشق - المالكي - شارع أبو رمانة',
            'دمشق - باب توما - الحريقة', 'دمشق - ركن الدين - شارع المدارس', 'دمشق - المهاجرين - شارع الروضة',
            'ريف دمشق - جرمانا', 'ريف دمشق - داريا', 'دمشق - برزة - شارع فيصل', 'دمشق - القابون - شارع الثورة'
        ];

        $medicalConditions = [
            'داء السكري النوع الثاني', 'ارتفاع ضغط الدم', 'الربو القصبي', 'حساسية من البنسلين',
            'أمراض القلب التاجية', 'فقر الدم المنجلي', 'أمراض الغدة الدرقية', 'حساسية من مواد التخدير الموضعي'
        ];
        
        $preliminaryDiagnoses = [
            'ألم حاد في السن السفلي الأيمن مع تورم',
            'تسوس عميق في الضاحك العلوي',
            'نخور متعددة في الأسنان الأمامية',
            'التهاب لثة ونزف عند التفريش',
            'فقدان أسنان خلفية وحاجة للتعويض',
            'ألم عند شرب البارد والساخن',
            'انطمار في ضرس العقل',
            'تصبغات سنية وتراكم جير'
        ];

        $caseTypes = CaseType::with('course')->get();

        // 10 patients from receptionist (waiting diagnosis)
        for ($i = 0; $i < 10; $i++) {
            $name = $names[$i];
            $patient = Patient::create([
                'patient_code' => 'DU-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'full_name' => $name['first'] . ' ' . $name['last'],
                'birth_date' => Carbon::now()->subYears(rand(18, 70))->subDays(rand(1, 365)),
                'gender' => $name['gender'],
                'phone' => '09' . rand(3,9) . rand(1000000, 9999999),
                'address' => $addresses[array_rand($addresses)],
                'preliminary_diagnosis' => $preliminaryDiagnoses[array_rand($preliminaryDiagnoses)],
                'availability_status' => PatientStatus::WAITING_DIAGNOSIS->value,
                'added_by' => $receptionist->id,
            ]);

            $hasCondition = rand(0, 1);
            $patient->medicalHistory()->create([
                'has_general_diseases' => $hasCondition,
                'general_diseases_details' => $hasCondition ? $medicalConditions[array_rand($medicalConditions)] : null,
                'is_special_needs' => false,
                'takes_medications' => $hasCondition,
                'medications_details' => $hasCondition ? 'أدوية متعلقة بالحالة العامة' : null,
                'has_allergies' => false,
            ]);
        }

        // 30 patients from students (with diagnoses)
        for ($i = 10; $i < 40; $i++) {
            $name = $names[$i];
            $student = $students->random();
            
            $patient = Patient::create([
                'patient_code' => 'DU-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'full_name' => $name['first'] . ' ' . $name['last'],
                'birth_date' => Carbon::now()->subYears(rand(18, 70))->subDays(rand(1, 365)),
                'gender' => $name['gender'],
                'phone' => '09' . rand(3,9) . rand(1000000, 9999999),
                'address' => $addresses[array_rand($addresses)],
                'preliminary_diagnosis' => $preliminaryDiagnoses[array_rand($preliminaryDiagnoses)],
                'availability_status' => PatientStatus::AVAILABLE->value,
                'added_by' => $student->id,
            ]);

            $hasCondition = rand(0, 1);
            $patient->medicalHistory()->create([
                'has_general_diseases' => $hasCondition,
                'general_diseases_details' => $hasCondition ? $medicalConditions[array_rand($medicalConditions)] : null,
                'is_special_needs' => false,
                'takes_medications' => false,
                'has_allergies' => false,
            ]);

            if ($caseTypes->isNotEmpty()) {
                $caseType = $caseTypes->random();
                $patient->diagnoses()->create([
                    'case_type_id' => $caseType->id,
                    'department_id' => $caseType->course->department_id,
                    'suggested_by_student_id' => $student->id,
                    'final_diagnosis' => 'تم تشخيص الحالة بناء على الفحص السريري والشعاعي',
                    'estimated_cost' => rand(10000, 50000),
                    'status' => DiagnosisStatus::AVAILABLE->value,
                ]);
            }
        }
    }
}
