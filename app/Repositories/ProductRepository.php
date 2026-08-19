<?php


namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{

    public function __construct(
        protected Product $model
    ) {}


    public function create(array $data): Product
    {
        return $this->model->create($data);
    }


    public function getStoreProductsOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->when(isset($filters['availability_status']), function ($query) use ($filters) {
                $query->where('availability_status', $filters['availability_status']);
            })
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

    public function countStoreProductsByIds(int $storeId, array $productIds): int
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->whereIn('id', $productIds)
            ->count();
    }

  public function getMarketplaceProducts(array $filters = [], int $perPage = 15, ?int $excludeSellerId = null): LengthAwarePaginator
    {
        return $this->model->query()
            ->whereIn('availability_status', [
                ProductAvailability::AVAILABLE->value,
                ProductAvailability::LIMITED->value
            ])

            // الطالب البائع ما لازم يشوف أدواته الخاصة وهو يتصفّح السوق كمشتري
            ->when($excludeSellerId, function ($query) use ($excludeSellerId) {
                $query->where('store_id', '!=', $excludeSellerId);
            })

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

    public function countLowStockProducts(int $storeId, int $threshold = 5): int
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('quantity', '<', $threshold)
            ->count();
    }

    public function getTopSellingProducts(int $storeId, int $limit = 5): Collection
    {
        return $this->model->newQuery()
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'products.quantity as stock_quantity',
            ])
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.store_id', $storeId)
            ->where('orders.status', OrderStatus::COMPLETED->value)
            ->groupBy('products.id', 'products.name', 'products.price', 'products.quantity')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }
}

