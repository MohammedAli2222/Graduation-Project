<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductAvailability;
use App\Models\Product;
use App\Repositories\Contracts\StudentMarketplaceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;

class StudentMarketplaceRepository implements StudentMarketplaceRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

   public function getAllStudentProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $filterHash = md5(json_encode($filters));
        $cacheKey = "student_products_per_page_{$perPage}_page_{$page}_filters_{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            return $this->model->newQuery()
                ->whereIn('availability_status', [
                    ProductAvailability::AVAILABLE->value,
                    ProductAvailability::LIMITED->value,
                ])
                ->whereHas('seller', function ($query) {
                    $query->role('student');
                })
                ->filter($filters)
                ->with(['category', 'media', 'seller.studentProfile'])
                ->latest()
                ->paginate($perPage);
        });
    }


    public function getProductDetails(int $productId): Product
    {
        $cacheKey = "student_product_details_{$productId}";

        return Cache::remember($cacheKey, 300, function () use ($productId) {
            return $this->model->newQuery()
                ->whereIn('availability_status', [
                    ProductAvailability::AVAILABLE->value,
                    ProductAvailability::LIMITED->value,
                ])
                ->with(['category', 'media', 'seller.studentProfile'])
                ->findOrFail($productId);
        });
    }


    public function getOtherProductsByStudent(int $studentId, int $excludeProductId, int $limit = 4): Collection
    {
        $cacheKey = "student_{$studentId}_other_products_exclude_{$excludeProductId}_limit_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($studentId, $excludeProductId, $limit) {
            return $this->model->newQuery()
                ->where('store_id', $studentId)
                ->whereIn('availability_status', [
                    ProductAvailability::AVAILABLE->value,
                    ProductAvailability::LIMITED->value,
                ])
                ->where('id', '!=', $excludeProductId)
                ->with(['category', 'media'])
                ->latest()
                ->take($limit)
                ->get();
        });
    }
}
