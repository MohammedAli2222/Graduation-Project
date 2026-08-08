<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StudentMarketplaceRepositoryInterface
{
    public function getAllStudentProducts(array $filters = [] , int $perPage = 15): LengthAwarePaginator;

    public function getProductDetails(int $productId): Product;

    public function getOtherProductsByStudent(int $studentId, int $excludeProductId, int $limit = 4): Collection;
}
