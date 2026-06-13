<?php


namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{

    public function __construct(
        protected Product $model
    ) {}


    public function create(array $data): Product
    {
        return $this->model->create($data);
    }


    public function getStoreProductsOptimized(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->with('category:id,name')
            ->latest()
            ->paginate($perPage);
    }


    public function findStoreProduct(int $storeId, int $productId): ?Product
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('id', $productId)
            ->first();
    }

    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function delete(Product $product): bool
    {
        return $product->delete() ?? false;
    }

    /**
     * جلب عدد المنتجات المطابقة للتحقق من أن المتجر يملكها جميعاً.
     */
    public function countStoreProductsByIds(int $storeId, array $productIds): int
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->whereIn('id', $productIds)
            ->count();
    }
}
