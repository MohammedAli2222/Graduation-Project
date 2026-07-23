<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the full 20-category dental supplies taxonomy.
     */
    public function run(): void
    {
        Category::factory()->count(20)->create();
    }
}
