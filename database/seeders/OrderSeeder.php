<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    private const TOTAL_ORDERS = 5000;

    private const CHUNK_SIZE = 250;

    /**
     * Seed orders + order items in chunks: orders go through the factory
     * (so status/rejection_reason stay realistic), while order_items are
     * bulk-inserted per chunk for performance across ~5,000 orders.
     */
    public function run(): void
    {
        $students = User::role('student')->pluck('id');

        $productsByStore = Product::query()
            ->select(['id', 'store_id', 'price'])
            ->get()
            ->groupBy('store_id');

        if ($students->isEmpty() || $productsByStore->isEmpty()) {
            $this->command?->warn('Run StudentSeeder and ProductSeeder before OrderSeeder.');

            return;
        }

        $storeIds = $productsByStore->keys();
        $ordersCreated = 0;

        while ($ordersCreated < self::TOTAL_ORDERS) {
            $batchSize = min(self::CHUNK_SIZE, self::TOTAL_ORDERS - $ordersCreated);
            $orderItemsBatch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $storeId = $storeIds->random();
                $storeProducts = $productsByStore->get($storeId);

                $order = Order::factory()->create([
                    'student_id' => $students->random(),
                    'store_id' => $storeId,
                    'total_amount' => 0,
                ]);

                $itemsCount = random_int(1, min(5, $storeProducts->count()));
                $selectedProducts = $storeProducts->random($itemsCount);

                if (! $selectedProducts instanceof Collection) {
                    $selectedProducts = collect([$selectedProducts]);
                }

                $total = 0.0;
                $now = now();

                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 4);
                    $subtotal = round($product->price * $quantity, 2);
                    $total += $subtotal;

                    $orderItemsBatch[] = [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $order->update(['total_amount' => $total]);
            }

            DB::table('order_items')->insert($orderItemsBatch);
            $ordersCreated += $batchSize;
        }
    }
}
