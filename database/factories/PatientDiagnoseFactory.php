<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiagnosisStatus;
use App\Models\CaseType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<PatientDiagnose>
 */
class PatientDiagnoseFactory extends Factory
{
    protected $model = PatientDiagnose::class;

    private const FINAL_DIAGNOSES = [
        'التهاب لب غير ردود (Irreversible Pulpitis) يحتاج لمعالجة لبية.',
        'نخر عميق (Deep Caries) مع سلامة اللب، يحتاج ترميم كمبوزيت.',
        'التهاب دواعم السن المزمن (Chronic Periodontitis) يحتاج تقليح وتسوية جذور.',
        'انطمار ضرس العقل السفلي (Impaction) يحتاج قلع جراحي.',
        'فقد سني مفرد يحتاج تعويض بالزراعة أو جسر ثلاثي.',
    ];

    public function definition(): array
    {
        $status = $this->faker->randomElement(array_column(DiagnosisStatus::cases(), 'value'));

        return [
            'patient_id' => Patient::factory(),
            'instructor_id' => null,

            // سحب حالة وقسم حقيقيين تم إنشاؤهما مسبقاً في الـ Seeders بدلاً من محاولة تصنيعهما
            'case_type_id' => CaseType::query()->inRandomOrder()->value('id')
                ?? throw new RuntimeException('يرجى عمل Seed للحالات (CaseTypes) أولاً.'),

            'department_id' => Department::query()->inRandomOrder()->value('id')
                ?? throw new RuntimeException('يرجى عمل Seed للأقسام (Departments) أولاً.'),

            'suggested_by_student_id' => User::factory()->student(),
            'final_diagnosis' => $this->faker->randomElement(self::FINAL_DIAGNOSES),
            'estimated_cost' => $this->faker->randomFloat(2, 50, 1500),
            'status' => $status,
            'rejection_reason' => $status === DiagnosisStatus::REJECTED->value ? 'الحالة معقدة جداً ولا تتناسب مع المستوى الأكاديمي للطالب.' : null,
        ];
    }
}
