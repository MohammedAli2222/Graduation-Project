<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * يبني بيانات التجارة الإلكترونية: الفئات، المنتجات (موزّعة على المتاجر
 * العشرة التي أنشأها UserSeeder)، العروض الترويجية، والطلبات مع عناصرها.
 */
class StoreSeeder extends Seeder
{
    private const TOTAL_CATEGORIES = 15;
    private const TOTAL_PRODUCTS = 300;
    private const TOTAL_ORDERS = 1000;
    private const ORDER_CHUNK_SIZE = 100;

    /**
     * الفئات فقط هي بيانات مرجعية أساسية (صاحب المتجر يحتاجها ليضيف منتجاً)،
     * أما المنتجات والعروض والطلبات فبيانات وهمية. اجعل هذا false لتوليدها مجدداً.
     */
    private const CATEGORIES_ONLY = true;

    public function run(): void
    {
        $categories = $this->seedCategories();

        if (self::CATEGORIES_ONLY) {
            return;
        }

        $stores = User::role('store_owner')->pluck('id');

        if ($stores->isEmpty()) {
            $this->command?->warn('يرجى تشغيل UserSeeder أولاً لوجود أصحاب متاجر قبل توليد بيانات التجارة.');

            return;
        }

        $this->seedProducts($stores, $categories);
        $this->seedPromotions($stores);
        $this->seedOrders($stores);
    }

    /**
     * @return Collection<int, int>
     */
    private function seedCategories(): Collection
    {
        return Category::factory(self::TOTAL_CATEGORIES)->create()->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $stores
     * @param  Collection<int, int>  $categories
     */
    private function seedProducts(Collection $stores, Collection $categories): void
    {
        Product::factory()
            ->count(self::TOTAL_PRODUCTS)
            ->state(fn (): array => [
                'store_id' => $stores->random(),
                'category_id' => $categories->random(),
            ])
            ->create();
    }

    /**
     * عرض ترويجي أو اثنان لكل متجر، مرتبط فقط بمنتجات ذلك المتجر نفسه
     * (لأن Promotion.store_id يمثّل ملكية العرض ولا يجوز أن يضم منتجات متجر آخر).
     *
     * @param  Collection<int, int>  $stores
     */
    private function seedPromotions(Collection $stores): void
    {
        foreach ($stores as $storeId) {
            $storeProducts = Product::where('store_id', $storeId)->pluck('id');

            if ($storeProducts->isEmpty()) {
                continue;
            }

            Promotion::factory(random_int(1, 2))->create(['store_id' => $storeId])
                ->each(function (Promotion $promotion) use ($storeProducts): void {
                    $randomProducts = $storeProducts->random(min($storeProducts->count(), random_int(2, 5)));
                    $promotion->products()->attach($randomProducts);
                });
        }
    }

    /**
     * نولّد 1000 طلب على دفعات: كل طلب عبر الـ Factory (لضمان توزيع حالات
     * واقعي عبر OrderFactory)، بينما نُدخل عناصر الطلب (order_items) دفعة
     * واحدة (Bulk Insert) في كل دفعة لتفادي آلاف الاستعلامات الفردية.
     *
     * @param  Collection<int, int>  $stores
     */
    private function seedOrders(Collection $stores): void
    {
        $students = User::role('student')->pluck('id');

        $productsByStore = Product::query()
            ->select(['id', 'store_id', 'price'])
            ->get()
            ->groupBy('store_id');

        if ($students->isEmpty() || $productsByStore->isEmpty()) {
            $this->command?->warn('يرجى تشغيل UserSeeder وتوليد المنتجات أولاً قبل إنشاء الطلبات.');

            return;
        }

        $this->command?->getOutput()->writeln('توليد الطلبات وعناصرها...');
        $this->command?->getOutput()->progressStart(self::TOTAL_ORDERS);

        $storeIds = $productsByStore->keys();
        $ordersCreated = 0;

        while ($ordersCreated < self::TOTAL_ORDERS) {
            $batchSize = min(self::ORDER_CHUNK_SIZE, self::TOTAL_ORDERS - $ordersCreated);
            $orderItemsBatch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $storeId = $storeIds->random();
                $storeProducts = $productsByStore->get($storeId);

                // الطلب أقدم بقليل من "الآن" لمحاكاة تاريخ شراء واقعي، وليس كل الطلبات بنفس اللحظة
                $orderDate = now()->subDays(random_int(0, 120))->subHours(random_int(0, 23));

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
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ];
                }

                // تحديث واحد يجمع بين المبلغ الإجمالي المحسوب وتاريخ الشراء الفعلي في الماضي
                $order->forceFill([
                    'total_amount' => $total,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ])->save();
                $this->command?->getOutput()->progressAdvance();
            }

            DB::table('order_items')->insert($orderItemsBatch);
            $ordersCreated += $batchSize;
        }

        $this->command?->getOutput()->progressFinish();
    }
}
