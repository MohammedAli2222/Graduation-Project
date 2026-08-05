<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{
    /**
     * الوسم (tag) المستخدم لتجميع كاش الفئات، لتسهيل تفريغه دفعة واحدة عند أي تعديل.
     */
    private const CACHE_TAG = 'categories';

    private const CACHE_KEY = 'categories:dropdown';

    private const CACHE_TTL_SECONDS = 86400; // 24 ساعة

    /**
     * جلب قائمة الفئات المختصرة (id, name) لاستخدامها في القوائم المنسدلة.
     * بيانات الفئات شبه ثابتة ونادراً ما تتغير، لذا نكاشها لمدة 24 ساعة لتفادي
     * ضرب قاعدة البيانات في كل طلب لهذه القائمة.
     */
    public function getDropdown(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => Category::select('id', 'name')->get()
        );
    }
}
