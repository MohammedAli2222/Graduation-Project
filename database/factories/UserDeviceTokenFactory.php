<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserDeviceToken>
 */
class UserDeviceTokenFactory extends Factory
{
    protected $model = UserDeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_type' => $this->faker->randomElement($this->weightedDeviceTypes()),
            // شكل تقريبي لتوكن Firebase Cloud Messaging الحقيقي (بادئة + جزء عشوائي طويل)
            'fcm_token' => 'f' . $this->faker->numerify('#####') . ':APA91b' . Str::random(134),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function weightedDeviceTypes(): array
    {
        return [
            ...array_fill(0, 60, 'android'),
            ...array_fill(0, 30, 'ios'),
            ...array_fill(0, 10, 'web'),
        ];
    }
}
