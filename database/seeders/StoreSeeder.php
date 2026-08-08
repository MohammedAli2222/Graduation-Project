<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    private const TOTAL_STORES = 50;

    public function run(): void
    {
        User::factory()->count(self::TOTAL_STORES)->asStoreOwner()->create();
    }
}
