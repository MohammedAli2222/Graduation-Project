<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Repositories\Contracts\StudentMarketplaceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentMarketplaceService
{
    public function __construct(
        protected StudentMarketplaceRepositoryInterface $repository
    ) {}

  public function getAllStudentProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllStudentProducts($filters, $perPage);
    }


    public function getProductWithSellerOthers(int $productId): array
    {
        $product = $this->repository->getProductDetails($productId);

        $otherProducts = $this->repository->getOtherProductsByStudent($product->store_id, $productId, 4);

        return [
            'product'        => $product,
            'other_products' => $otherProducts,
        ];
    }
}
