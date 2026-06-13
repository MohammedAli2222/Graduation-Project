<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{

    public function create(array $data): Product;

    public function getStoreProductsOptimized(int $storeId, int $perPage = 15): LengthAwarePaginator;

    public function findStoreProduct(int $storeId, int $productId): ?Product;

    public function update(Product $product, array $data): bool;

    public function delete(Product $product): bool;

    /**
     * حساب عدد المنتجات المملوكة للمتجر من ضمن مصفوفة أرقام محددة).
     */
    public function countStoreProductsByIds(int $storeId, array $productIds): int;
}
