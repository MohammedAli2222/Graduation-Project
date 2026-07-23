<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 2000;

    /**
     * Seed the product catalog across every real store and category,
     * exercising every ProductCondition / ProductAvailability edge case.
     */
    public function run(): void
    {
        $stores = User::role('store_owner')->pluck('id');
        $categories = Category::pluck('id');

        if ($stores->isEmpty() || $categories->isEmpty()) {
            $this->command?->warn('Run StoreSeeder and CategorySeeder before ProductSeeder.');

            return;
        }

        Product::factory()
            ->count(self::TOTAL_PRODUCTS)
            ->state(fn (): array => [
                'store_id' => $stores->random(),
                'category_id' => $categories->random(),
            ])
            ->create();
    }
}
