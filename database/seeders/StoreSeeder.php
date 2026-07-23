<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    private const TOTAL_STORES = 50;

    /**
     * توليد حسابات متاجر طبية رسمية،
     * وإسناد صلاحية "store_owner" والملف الشخصي لكل منها.
     */
    public function run(): void
    {
        // تم استخدام asStoreOwner بدلاً من store لتجنب تضارب الأسماء مع نواة لارافل
        User::factory()->count(self::TOTAL_STORES)->asStoreOwner()->create();
    }
}
