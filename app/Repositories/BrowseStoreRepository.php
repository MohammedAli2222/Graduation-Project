<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductAvailability;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\BrowseStoreRepositoryInterface;
use App\Support\CacheGroup;
use App\Support\CacheVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;

class BrowseStoreRepository implements BrowseStoreRepositoryInterface
{
    public function __construct(
        protected User $userModel,
        protected Product $productModel
    ) {}


    public function getAllStores(int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $cacheKey = "official_stores_per_page_{$perPage}_page_{$page}";

        return Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return $this->userModel->newQuery()
                ->role('store_owner')
                ->select(['id', 'first_name', 'last_name', 'email'])
                ->with('storeProfile')
                ->latest()
                ->paginate($perPage);
        });
    }

   public function getStoreProducts(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $filterHash = md5(json_encode($filters));
        $cacheKey = "store_{$storeId}_products_per_page_{$perPage}_page_{$page}_filters_{$filterHash}";

        return Cache::remember(CacheVersion::key(CacheGroup::store($storeId), $cacheKey), 86400, function () use ($storeId, $filters, $perPage) {
            return $this->productModel->newQuery()
                ->where('store_id', $storeId)
                ->whereIn('availability_status', [
                    ProductAvailability::AVAILABLE->value,
                    ProductAvailability::LIMITED->value,
                ])
                ->filter($filters)
                // كل عروض المنتج تُحمَّل هنا بلا فلترة تاريخ داخل الاستعلام
                // المخزَّن كاش ليوم كامل: فلترة "هل العرض ساري الآن؟" تجري
                // لاحقاً بالـ Resource وقت كل طلب فعلي، لا وقت كتابة الكاش —
                // وإلا بقي عرض منتهي يظهر كساري حتى نهاية مدة الكاش.
                ->with(['category', 'media', 'seller.storeProfile', 'promotions'])
                ->latest()
                ->paginate($perPage);
        });
    }
}
