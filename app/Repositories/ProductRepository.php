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
            // تصفية اختيارية حسب حالة التوفر (available/limited/out_of_stock)؛
            // when() لا يُضيف شرط WHERE إطلاقاً إن لم يُرسَل الفلتر، فيبقى
            // الاستعلام الافتراضي (بدون فلتر) بنفس أداء الكود الأصلي تماماً
            ->when(isset($filters['availability_status']), function ($query) use ($filters) {
                $query->where('availability_status', $filters['availability_status']);
            })
            // الحفاظ على التحميل المسبق (Eager Loading) الأصلي لمنع مشكلة N+1
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

    /**
     * عدد المنتجات منخفضة المخزون عبر استعلام count() واحد مفهرس
     * (store_id مفهرس عبر constrained()، وquantity < threshold مقارنة مباشرة).
     */
    public function countLowStockProducts(int $storeId, int $threshold = 5): int
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('quantity', '<', $threshold)
            ->count();
    }

    /**
     * أفضل N منتجات مبيعاً: JOIN واحد بين products وorder_items وorders،
     * مُصفّى على حالة completed فقط، مع GROUP BY وORDER BY وLIMIT منفَّذة
     * بالكامل داخل قاعدة البيانات (لا يوجد أي تجميع يدوي في PHP).
     */
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

