<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'student_id' => User::factory()->student(),
            'diagnosis_id' => PatientDiagnose::factory(),
            'treatment_id' => null,
            'appointment_date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'slot_number' => $this->faker->numberBetween(1, 4),
            'status' => $this->faker->randomElement(array_column(AppointmentStatus::cases(), 'value')),
        ];
    }
}
