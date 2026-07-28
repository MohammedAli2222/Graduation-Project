<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Repositories\Contracts\BrowseStoreRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrowseStoreService
{
    public function __construct(
        protected BrowseStoreRepositoryInterface $browseStoreRepo
    ) {}


    public function getStores(int $perPage = 15): LengthAwarePaginator
    {
        return $this->browseStoreRepo->getAllStores($perPage);
    }


    public function getStoreProducts(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->browseStoreRepo->getStoreProducts($storeId, $filters, $perPage);
    }
}
