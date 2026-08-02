<?php


namespace App\Repositories;

use App\Enums\ProductAvailability;
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
            ->with(['category:id,name', 'media'])
            ->latest()
            ->paginate($perPage);
    }


    public function findStoreProduct(int $storeId, int $productId): ?Product
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('id', $productId)
            ->with('media')
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

  public function getMarketplaceProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()
            ->whereIn('availability_status', [
                ProductAvailability::AVAILABLE->value,
                ProductAvailability::LIMITED->value
            ])

            ->when(isset($filters['search']), function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            })

            ->when(isset($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })

            ->when(isset($filters['condition']), function ($query) use ($filters) {
                $query->where('condition', $filters['condition']);
            })

            ->with(['seller.storeProfile', 'seller.studentProfile', 'category'])

            ->latest()
            ->paginate($perPage);
    }


    public function findById(int $productId): ?Product
    {
        return $this->model->with('media')->find($productId);
    }
}

