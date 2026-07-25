<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\BrowseStoreRepositoryInterface;
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

        return Cache::tags(['stores', 'b2c'])->remember($cacheKey, 86400, function () use ($perPage) {
            return $this->userModel->newQuery()
                ->role('store_owner')
                ->select(['id', 'first_name', 'last_name', 'email'])
                ->with('storeProfile')
                ->latest()
                ->paginate($perPage);
        });
    }

    public function getStoreProducts(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $cacheKey = "store_{$storeId}_products_per_page_{$perPage}_page_{$page}";

        return Cache::tags(['stores', 'b2c', "store_{$storeId}"])->remember($cacheKey, 86400, function () use ($storeId, $perPage) {
            return $this->productModel->newQuery()
                ->where('store_id', $storeId)
                ->where('availability_status', 'available')
                ->with(['category', 'media', 'seller.storeProfile'])
                ->latest()
                ->paginate($perPage);
        });
    }
}
