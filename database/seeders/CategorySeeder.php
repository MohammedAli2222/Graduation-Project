<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{

    public function run(): void
    {
        $categories = [
            [
                'name'        => 'أدوات لبية',
                'description' => 'كافة الأدوات المستخدمة في علاج العصب والمداواة اللبية مثل المبارد والموسعات.',
            ],
            [
                'name'        => 'مواد طبعات',
                'description' => 'المواد المستخدمة لأخذ طبعات الفم مثل الألجينات والسيليكون.',
            ],
            [
                'name'        => 'مستهلكات',
                'description' => 'المواد ذات الاستخدام الواحد مثل الكمامات، القفازات، والمحاقن (Syringes).',
            ],
            [
                'name'        => 'أدوات جراحية',
                'description' => 'الأدوات المستخدمة في جراحة الفم والأسنان مثل الكُلابات وروافع الجذور.',
            ],
            [
                'name'        => 'مواد ترميمية',
                'description' => 'مواد الحشوات التجميلية (الكومبوزيت)، الأملغم، واللواصق السنية.',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']] 
            );
        }
    }
}
