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
    /**
     * تشغيل بذور قاعدة البيانات للقسم التجاري.
     */
    public function run(): void
    {
        $this->command->info('جاري بناء قسم التجارة الإلكترونية (العروض، السلات، المنتجات)...');

        $stores = User::role('store_owner')->get();
        $students = User::role('student')->get();

        if ($stores->isEmpty() || $students->isEmpty()) {
            $this->command->warn('يرجى عمل Seed للمتاجر والطلاب والمنتجات أولاً.');
            return;
        }

        // 1. توليد العروض الترويجية للمتاجر وربطها بمنتجاتهم
        foreach ($stores as $store) {
            $storeProducts = Product::where('store_id', $store->id)->get();

            if ($storeProducts->isNotEmpty()) {
                // إنشاء 1 إلى 2 عروض لكل متجر
                Promotion::factory(rand(1, 2))->create([
                    'store_id' => $store->id,
                ])->each(function (Promotion $promotion) use ($storeProducts) {
                    // ربط العرض بـ 2 إلى 5 منتجات من نفس المتجر حصراً عبر الجدول الوسيط
                    $randomProducts = $storeProducts->random(min($storeProducts->count(), rand(2, 5)));
                    $promotion->products()->attach($randomProducts->pluck('id')->toArray());
                });
            }
        }

        // 2. توليد السلات للطلاب (بناءً على قاعدة Single-Store Cart Rule)
        // سنقوم بإنشاء سلات لـ 20 طالباً بشكل عشوائي كعينة اختبار
        foreach ($students->random(min($students->count(), 20)) as $student) {
            $cart = Cart::factory()->create([
                'student_id' => $student->id,
            ]);

            // اختيار متجر عشوائي واحد لتطبيق قاعدة البائع الواحد للسلة
            $randomStore = $stores->random();
            $storeProducts = Product::where('store_id', $randomStore->id)
                ->where('availability_status', 'available')
                ->get();

            if ($storeProducts->isNotEmpty()) {
                // إضافة 1 إلى 4 منتجات للسلة من هذا المتجر فقط
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
