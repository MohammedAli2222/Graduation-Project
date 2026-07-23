<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    private const REJECTION_REASONS = [
        'Item out of stock at the time of confirmation.',
        'Store closed and unable to fulfill the order.',
        'Requested product has been discontinued.',
        'Student cancelled the order before processing.',
        'Pricing discrepancy needs to be resolved with the buyer.',
    ];

    public function definition(): array
    {
        $status = $this->faker->randomElement($this->weightedStatuses());

        return [
            'student_id' => User::factory()->student(),
            'store_id' => User::factory()->asStoreOwner(),
            'total_amount' => $this->faker->randomFloat(2, 20, 2500),
            'status' => $status,
            'rejection_reason' => $status === OrderStatus::REJECTED
                ? $this->faker->randomElement(self::REJECTION_REASONS)
                : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(): array => ['status' => OrderStatus::PENDING, 'rejection_reason' => null]);
    }

    public function completed(): static
    {
        return $this->state(fn(): array => ['status' => OrderStatus::COMPLETED, 'rejection_reason' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn(): array => [
            'status' => OrderStatus::REJECTED,
            'rejection_reason' => $this->faker->randomElement(self::REJECTION_REASONS),
        ]);
    }

    /**
     * @return array<int, OrderStatus>
     */
    private function weightedStatuses(): array
    {
        return [
            ...array_fill(0, 20, OrderStatus::PENDING),
            ...array_fill(0, 20, OrderStatus::PROCESSING),
            ...array_fill(0, 15, OrderStatus::READY),
            ...array_fill(0, 35, OrderStatus::COMPLETED),
            ...array_fill(0, 10, OrderStatus::REJECTED),
        ];
    }
}
