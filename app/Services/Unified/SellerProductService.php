<?php

declare(strict_types=1);

namespace App\Services\Unified;

use App\Jobs\ProcessProductImagesJob;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class SellerProductService
{
    /**
     * حقن مستودع المنتجات.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}

  
    public function listProducts(int $sellerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepo->getStoreProductsOptimized($sellerId, $perPage);
    }


    public function createProduct(int $sellerId, array $productData, ?array $images): Product
    {
        $productData['store_id'] = $sellerId;

        $product = DB::transaction(function () use ($productData) {
            return $this->productRepo->create($productData);
        });

        if (! empty($images)) {
            $this->dispatchImageProcessing($product->id, $images);
        }

        return $product;
    }


    public function updateProduct(int $sellerId, int $productId, array $updateData, ?array $images = null): Product
    {
        $product = $this->productRepo->findStoreProduct($sellerId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا تملك صلاحية تعديله.', 404);
        }

        $this->productRepo->update($product, $updateData);

        if (! empty($images)) {
            $this->dispatchImageProcessing($product->id, $images);
        }

        return $product->refresh();
    }

    /**
     * حذف المنتج.
     */
    public function deleteProduct(int $sellerId, int $productId): bool
    {
        $product = $this->productRepo->findStoreProduct($sellerId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا تملك صلاحية حذفه.', 404);
        }

        return $this->productRepo->delete($product);
    }

    /**
     * تفاصيل المنتج.
     */
    public function getProductDetails(int $sellerId, int $productId): Product
    {
        $product = $this->productRepo->findStoreProduct($sellerId, $productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود أو لا يتبع لك.', 404);
        }

        return $product->load(['media', 'category:id,name']);
    }

   
    protected function dispatchImageProcessing(int $productId, array $images): void
    {
        $tempPaths = [];

        foreach ($images as $image) {
            $path = $image->store('temp/products', 'local');
            if ($path) {
                $tempPaths[] = $path;
            }
        }

        if (! empty($tempPaths)) {
            ProcessProductImagesJob::dispatch($productId, $tempPaths);
        }
    }
}