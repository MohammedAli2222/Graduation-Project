<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            // سيتم تمرير الـ student_id من الـ Seeder
            'student_id' => User::factory()->student(),
        ];
    }
}
