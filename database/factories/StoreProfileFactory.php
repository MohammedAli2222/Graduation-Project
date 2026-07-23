<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StoreProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreProfile>
 */
class StoreProfileFactory extends Factory
{
    protected $model = StoreProfile::class;

    private const SUFFIXES = [
        'Dental Supplies', 'Dental Depot', 'Dental Trading Co.', 'Dental Equipment House',
        'Dental Warehouse', 'Dental Mart', 'Oral Care Distributors', 'Dental Instruments Co.',
        'Dental Import & Export', 'Dental Solutions Group',
    ];

    private const CITIES = [
        'Damascus', 'Aleppo', 'Homs', 'Latakia', 'Tartus', 'Hama', 'Daraa', 'Deir ez-Zor',
    ];

    public function definition(): array
    {
        $baseName = $this->faker->unique()->lastName();

        return [
            'user_id' => User::factory(),
            'store_name' => "{$baseName} " . $this->faker->randomElement(self::SUFFIXES),
            'store_phone' => $this->faker->numerify('09########'),
            'store_address' => $this->faker->buildingNumber() . ' ' . $this->faker->streetName()
                . ', ' . $this->faker->randomElement(self::CITIES),
        ];
    }
}
