<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientMedicalHistory>
 */
class PatientMedicalHistoryFactory extends Factory
{
    protected $model = PatientMedicalHistory::class;

    public function definition(): array
    {
        $hasGeneralDiseases = $this->faker->boolean(20);
        $takesMedications = $hasGeneralDiseases ? $this->faker->boolean(80) : $this->faker->boolean(5);
        $hasAllergies = $this->faker->boolean(10);

        return [
            'patient_id' => Patient::factory(),
            'has_general_diseases' => $hasGeneralDiseases,
            'general_diseases_details' => $hasGeneralDiseases ? $this->faker->randomElement(['السكري النمط الثاني', 'ارتفاع ضغط الدم', 'ربو تحسسي', 'اضطرابات في الغدة الدرقية']) : null,
            'is_special_needs' => $this->faker->boolean(2),
            'special_needs_details' => null,
            'takes_medications' => $takesMedications,
            'medications_details' => $takesMedications ? $this->faker->randomElement(['ميتفورمين', 'أسبرين (مميع دم)', 'أدوية ضغط', 'بخاخ فينتولين']) : null,
            'has_allergies' => $hasAllergies,
            'allergies_details' => $hasAllergies ? $this->faker->randomElement(['حساسية من البنسلين', 'حساسية من اللاتكس', 'حساسية تجاه مسكنات NSAIDs']) : null,
        ];
    }
}
