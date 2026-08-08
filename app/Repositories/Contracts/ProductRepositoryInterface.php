<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{

    public function create(array $data): Product;

    /**
     * جلب منتجات المتجر مع دعم تصفية اختيارية حسب حالة التوفر
     * (availability_status)، مع الحفاظ على الترقيم (Pagination) القياسي —
     * لا تُجمَّع كل الحالات أبداً في استجابة واحدة ضخمة.
     *
     * @param array{availability_status?: string} $filters
     */
    public function getStoreProductsOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findStoreProduct(int $storeId, int $productId): ?Product;

    public function update(Product $product, array $data): bool;

    public function delete(Product $product): bool;

    /**
     * حساب عدد المنتجات المملوكة للمتجر من ضمن مصفوفة أرقام محددة).
     */
    public function countStoreProductsByIds(int $storeId, array $productIds): int;

    public function getMarketplaceProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $productId): ?Product;

    /**
     * عدد المنتجات التي أوشكت كميتها على النفاد (quantity < الحد المحدد)
     * لمتجر معيّن، عبر استعلام count() واحد على الفهرس.
     */
    public function countLowStockProducts(int $storeId, int $threshold = 5): int;

    /**
     * أفضل N منتجات مبيعاً لمتجر معيّن، محسوبة من مجموع الكميات ضمن عناصر
     * الطلبات المكتملة فقط (JOIN + GROUP BY + ORDER BY في استعلام واحد).
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function getTopSellingProducts(int $storeId, int $limit = 5): \Illuminate\Support\Collection;
}
