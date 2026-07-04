<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class StoreProductService
{
    /**
     * حقن مستودع المنتجات.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}

    public function createProduct(int $storeId, array $productData, ?array $images): Product
    {
        $productData['store_id'] = $storeId;

        return DB::transaction(function () use ($productData, $images) {

            $product = $this->productRepo->create($productData);

            if (! empty($images)) {
                foreach ($images as $image) {
                    $product->addMedia($image)
                        ->toMediaCollection('products');
                }
            }

            return $product->load('media');
        });
    }


    public function updateProduct(int $storeId, int $productId, array $updateData): Product
    {
        $product = $this->productRepo->findStoreProduct($storeId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا تملك صلاحية تعديله.', 404);
        }

        $this->productRepo->update($product, $updateData);

        return $product->refresh()->load('media');
    }


    public function listStoreProducts(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepo->getStoreProductsOptimized($storeId, $perPage);
    }

    public function deleteProduct(int $storeId, int $productId): bool
    {
        $product = $this->productRepo->findStoreProduct($storeId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا تملك صلاحية حذفه.', 404);
        }

        return $this->productRepo->delete($product);
    }

    public function getProductDetails(int $storeId, int $productId): Product
    {
        $product = $this->productRepo->findStoreProduct($storeId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا يتبع لمتجرك.', 404);
        }

        // تحميل الصور والفئة لتكون جاهزة للعرض
        return $product->load(['media', 'category:id,name']);
    }
}
