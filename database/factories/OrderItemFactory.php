<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => 0,
            'subtotal' => 0,
        ];
    }

    /**
     * Derive unit_price/subtotal from the resolved product so factory usage
     * (tests, tinker) never produces figures inconsistent with the product.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (OrderItem $orderItem): void {
            $product = Product::find($orderItem->product_id);

            $orderItem->unit_price = $product?->price ?? $this->faker->randomFloat(2, 10, 500);
            $orderItem->subtotal = round($orderItem->unit_price * $orderItem->quantity, 2);
        });
    }
}
