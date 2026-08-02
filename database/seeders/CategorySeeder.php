<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'أدوات طب الأسنان', 'description' => 'كافة الأدوات اليدوية والمستلزمات العيادية'],
            ['name' => 'مواد الحشو والترميم', 'description' => 'مواد الكومبوزيت والأملغم والمواد الرابطة'],
            ['name' => 'معدات الوقاية والتعقيم', 'description' => 'كمامات، قفازات، ومواد التعقيم والتطهير'],
            ['name' => 'أجهزة طب الأسنان', 'description' => 'الأجهزة الكبيرة والصغيرة المستخدمة في العيادة'],
            ['name' => 'مستلزمات الأشعة', 'description' => 'أفلام الأشعة وحواملها ومواد التحميض'],
            ['name' => 'أدوات تقويم الأسنان', 'description' => 'أسلاك، حاصرات، ومطاط التقويم'],
            ['name' => 'مواد التخدير', 'description' => 'أمبولات التخدير الموضعي والإبر السنية'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
