<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{

    public function create(array $data): Product;

    public function getStoreProductsOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findStoreProduct(int $storeId, int $productId): ?Product;

    public function update(Product $product, array $data): bool;

    public function delete(Product $product): bool;

    public function countStoreProductsByIds(int $storeId, array $productIds): int;

    public function getMarketplaceProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $productId): ?Product;

    public function countLowStockProducts(int $storeId, int $threshold = 5): int;

    public function getTopSellingProducts(int $storeId, int $limit = 5): \Illuminate\Support\Collection;
}
