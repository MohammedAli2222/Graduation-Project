<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Seeder;

class EcommerceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('جاري بناء قسم التجارة الإلكترونية (العروض، السلات، المنتجات)...');

        $stores = User::role('store_owner')->get();
        $students = User::role('student')->get();

        if ($stores->isEmpty() || $students->isEmpty()) {
            $this->command->warn('يرجى عمل Seed للمتاجر والطلاب والمنتجات أولاً.');
            return;
        }

        foreach ($stores as $store) {
            $storeProducts = Product::where('store_id', $store->id)->get();

            if ($storeProducts->isNotEmpty()) {
                Promotion::factory(rand(1, 2))->create([
                    'store_id' => $store->id,
                ])->each(function (Promotion $promotion) use ($storeProducts) {
                    $randomProducts = $storeProducts->random(min($storeProducts->count(), rand(2, 5)));
                    $promotion->products()->attach($randomProducts->pluck('id')->toArray());
                });
            }
        }

        foreach ($students->random(min($students->count(), 20)) as $student) {
            $cart = Cart::factory()->create([
                'student_id' => $student->id,
            ]);

            $randomStore = $stores->random();
            $storeProducts = Product::where('store_id', $randomStore->id)
                ->where('availability_status', 'available')
                ->get();

            if ($storeProducts->isNotEmpty()) {
                $cartProducts = $storeProducts->random(min($storeProducts->count(), rand(1, 4)));

                foreach ($cartProducts as $product) {
                    CartItem::factory()->create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'quantity' => rand(1, 3),
                    ]);
                }
            }
        }

        $this->command->info('تم إنشاء العروض الترويجية والسلات بامتياز وحسب قواعد الأعمال!');
    }
}
