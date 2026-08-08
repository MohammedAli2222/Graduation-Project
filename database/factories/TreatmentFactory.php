<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TreatmentStatus;
use App\Models\PatientDiagnose;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(array_column(TreatmentStatus::cases(), 'value'));
        $startDate = $this->faker->dateTimeBetween('-2 months', 'now');

        $endDate = $status === TreatmentStatus::COMPLETED->value
            ? Carbon::instance($startDate)->addDays($this->faker->numberBetween(1, 14))
            : null;

        return [
            'diagnosis_id' => PatientDiagnose::factory(),
            'instructor_id' => null,
            'status' => $status,
            'rejection_reason' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'instructor_notes' => $this->faker->boolean(30) ? 'يرجى الانتباه للعزل الجيد أثناء تطبيق التخريش الحمضي.' : null,
        ];
    }
}
