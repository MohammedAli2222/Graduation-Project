<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بيانات المتجر: التصنيفات (الفئات) والمنتجات (المواد والأدوات الطبية).
 *
 * يعتمد على UserSeeder لأن products.store_id مفتاح خارجي نحو جدول users
 * (وليس نحو store_profiles) — أي أن مالك المنتج هو حساب صاحب المتجر نفسه.
 */
class StoreSeeder extends Seeder
{
    /** بريد صاحب المتجر المُنشأ في UserSeeder */
    private const STORE_OWNER_EMAIL = 'store@test.com';

    public function run(): void
    {
        $storeOwner = User::where('email', self::STORE_OWNER_EMAIL)->first();

        if (! $storeOwner) {
            $this->command?->warn('لم يُعثر على صاحب المتجر — شغّل UserSeeder أولاً.');

            return;
        }

        $categories = $this->seedCategories();
        $this->seedProducts($storeOwner, $categories);
    }

    /**
     * التصنيفات: بيانات مرجعية يختار منها صاحب المتجر عند إضافة أي منتج
     * (products.category_id مقيّد بـ restrictOnDelete، أي لا يمكن حذف تصنيف مستخدم).
     *
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $definitions = [
            'surgical' => ['أدوات جراحية', 'مشارط، ملاقط، وأدوات القلع والجراحة الفموية.'],
            'restorative' => ['مواد ترميمية', 'كومبوزيت، أسمنت، ومواد الحشو والترميم السني.'],
            'orthodontic' => ['أدوات تقويم', 'حاصرات، أسلاك، وحلقات التقويم الثابت والمتحرك.'],
            'sterilization' => ['مواد تعقيم ووقاية', 'قفازات، كمامات، ومحاليل التعقيم والتطهير.'],
        ];

        $categories = [];

        foreach ($definitions as $key => [$name, $description]) {
            $categories[$key] = Category::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        return $categories;
    }

    /**
     * المنتجات: ثمانية منتجات تغطي كل الحالات التي تحتاجها الواجهة أثناء الاختبار
     * (متوفر / كمية محدودة / نفد من المخزون، وجديد / مستعمل).
     *
     * تنبيه: DatabaseSeeder يستخدم WithoutModelEvents، لذلك لا يعمل هوك saving
     * في موديل Product أثناء الـ Seeding — ولهذا نضبط availability_status صراحةً
     * بما يوافق الكمية بدل الاعتماد على الاشتقاق التلقائي.
     *
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(User $storeOwner, array $categories): void
    {
        $products = [
            [
                'category' => 'surgical',
                'name' => 'طقم ملاقط قلع للفك العلوي',
                'description' => 'طقم ملاقط قلع من الفولاذ المقاوم للصدأ مخصص لأسنان الفك العلوي.',
                'price' => 145000.00,
                'brand' => 'Medesy',
                'quantity' => 12,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::AVAILABLE,
            ],
            [
                'category' => 'surgical',
                'name' => 'مبعد سمحاقي',
                'description' => 'مبعد سمحاقي دقيق يُستخدم في رفع الشريحة أثناء الجراحة الفموية.',
                'price' => 38000.00,
                'brand' => 'Hu-Friedy',
                'quantity' => 3,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::LIMITED,
            ],
            [
                'category' => 'restorative',
                'name' => 'كومبوزيت ضوئي A2',
                'description' => 'حشوة كومبوزيت ضوئية التصلب بلون A2، عبوة 4 غرام.',
                'price' => 62000.00,
                'brand' => '3M ESPE',
                'quantity' => 25,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::AVAILABLE,
            ],
            [
                'category' => 'restorative',
                'name' => 'أسمنت زجاجي شاردي',
                'description' => 'أسمنت زجاجي شاردي للتبطين والترميم، عبوة مسحوق وسائل.',
                'price' => 47500.00,
                'brand' => 'GC',
                'quantity' => 0,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::OUT_OF_STOCK,
            ],
            [
                'category' => 'orthodontic',
                'name' => 'حاصرات تقويم معدنية (طقم كامل)',
                'description' => 'طقم حاصرات تقويم معدنية للفكين، شق 0.022 إنش.',
                'price' => 210000.00,
                'brand' => 'Ormco',
                'quantity' => 8,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::AVAILABLE,
            ],
            [
                'category' => 'orthodontic',
                'name' => 'أسلاك تقويم نيكل تيتانيوم',
                'description' => 'أسلاك تقويم مرنة من النيكل تيتانيوم بقياسات متعددة.',
                'price' => 55000.00,
                'brand' => 'American Orthodontics',
                'quantity' => 15,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::AVAILABLE,
            ],
            [
                'category' => 'orthodontic',
                'name' => 'كماشة تقويم مستعملة',
                'description' => 'كماشة ثني أسلاك تقويم بحالة جيدة جداً، استُعملت لفصل واحد.',
                'price' => 29000.00,
                'brand' => 'Dentaurum',
                'quantity' => 2,
                'condition' => ProductCondition::USED,
                'availability_status' => ProductAvailability::LIMITED,
            ],
            [
                'category' => 'sterilization',
                'name' => 'قفازات فحص لاتكس (علبة 100)',
                'description' => 'قفازات فحص من اللاتكس بودرة خفيفة، علبة تحتوي 100 قفاز.',
                'price' => 18000.00,
                'brand' => 'Safeguard',
                'quantity' => 40,
                'condition' => ProductCondition::NEW,
                'availability_status' => ProductAvailability::AVAILABLE,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['name' => $product['name'], 'store_id' => $storeOwner->id],
                [
                    'category_id' => $categories[$product['category']]->id,
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'brand' => $product['brand'],
                    'quantity' => $product['quantity'],
                    'condition' => $product['condition']->value,
                    'availability_status' => $product['availability_status']->value,
                ]
            );
        }
    }
}
