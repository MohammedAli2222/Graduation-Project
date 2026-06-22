<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * إدخال منتجات طبية تجريبية لاختبار العروض الترويجية.
     */
    public function run(): void
    {
        // 1. تجهيز صاحب متجر تجريبي (Store Owner)
        // نستخدم firstOrCreate لمنع التكرار عند تشغيل السيدر أكثر من مرة
        $storeOwner = User::firstOrCreate(
            ['email' => 'store@example.com'],
            [
                'first_name'   => 'متجر',
                'last_name'    => 'الجامعة الطبي',
                'password'     => Hash::make('password123'),
                // تأكد من إضافة Role المتجر لاحقاً إذا لزم الأمر في السيدر الخاص بالصلاحيات
            ]
        );

        // 2. جلب الفئات (يجب أن تكون قد شغلت CategorySeeder مسبقاً)
        $endoCategory = Category::where('name', 'أدوات لبية')->first();
        $impressionCategory = Category::where('name', 'مواد طبعات')->first();
        $consumablesCategory = Category::where('name', 'مستهلكات')->first();
        $surgicalCategory = Category::where('name', 'أدوات جراحية')->first();

        // التوقف إذا لم تكن الفئات موجودة
        if (! $endoCategory || ! $impressionCategory) {
            $this->command->warn('الرجاء تشغيل CategorySeeder أولاً: php artisan db:seed --class=CategorySeeder');
            return;
        }

        // 3. قائمة المنتجات الطبية التجريبية
        $products = [
            [
                'category_id'         => $endoCategory->id,
                'name'                => 'مجموعة مبارد K-Files',
                'description'         => 'مجموعة مبارد يدوية لتوسيع الأقنية الجذرية بمقاسات مختلفة (15-40).',
                'price'               => 150.00,
                'brand'               => 'Mani',
                'availability_status' => ProductAvailability::AVAILABLE->value,
                'condition'           => ProductCondition::NEW->value,
            ],
            [
                'category_id'         => $endoCategory->id,
                'name'                => 'محدد ذروة (Apex Locator) مستعمل',
                'description'         => 'جهاز قياس طول القناة الجذرية بحالة ممتازة وبطارية جديدة.',
                'price'               => 1200.00,
                'brand'               => 'Woodpecker',
                'availability_status' => ProductAvailability::LIMITED->value,
                'condition'           => ProductCondition::USED->value, // منتج مستعمل لتجربة الفلاتر
            ],
            [
                'category_id'         => $impressionCategory->id,
                'name'                => 'مادة ألجينات (Alginate)',
                'description'         => 'مادة طبعات سريعة التصلب بنكهة النعناع.',
                'price'               => 45.50,
                'brand'               => 'Cavex',
                'availability_status' => ProductAvailability::AVAILABLE->value,
                'condition'           => ProductCondition::NEW->value,
            ],
            [
                'category_id'         => $impressionCategory->id,
                'name'                => 'سيليكون إضافي (Addition Silicone)',
                'description'         => 'مادة طبعات دقيقة جداً للتيجان والجسور مع معجون قاعدي ومحفز.',
                'price'               => 350.00,
                'brand'               => 'Zhermack',
                'availability_status' => ProductAvailability::AVAILABLE->value,
                'condition'           => ProductCondition::NEW->value,
            ],
            [
                'category_id'         => $consumablesCategory->id,
                'name'                => 'علبة كمامات طبية',
                'description'         => 'كمامات طبية 3 طبقات، العلبة تحتوي على 50 قطعة.',
                'price'               => 20.00,
                'brand'               => 'Medicomp',
                'availability_status' => ProductAvailability::AVAILABLE->value,
                'condition'           => ProductCondition::NEW->value,
            ],
            [
                'category_id'         => $surgicalCategory->id,
                'name'                => 'كلابة قلع علوية مستقيمة',
                'description'         => 'كلابة لقلع القواطع العلوية مصنوعة من الستانلس ستيل المقاوم للصدأ.',
                'price'               => 85.00,
                'brand'               => 'Hu-Friedy',
                'availability_status' => ProductAvailability::LIMITED->value,
                'condition'           => ProductCondition::NEW->value,
            ],
        ];

        // 4. إدخال المنتجات في قاعدة البيانات
        foreach ($products as $productData) {
            Product::firstOrCreate(
                [
                    'store_id' => $storeOwner->id,
                    'name'     => $productData['name'], // نمنع التكرار بناءً على اسم المنتج للمتجر الواحد
                ],
                $productData
            );
        }

        $this->command->info('تم بذر المنتجات بنجاح! رقم المتجر التجريبي هو: ' . $storeOwner->id);
    }
}
