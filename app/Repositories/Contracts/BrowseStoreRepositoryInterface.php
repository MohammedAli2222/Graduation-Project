<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BrowseStoreRepositoryInterface
{
    public function getAllStores(int $perPage = 15): LengthAwarePaginator;

    public function getStoreProducts( int $storeId,array $filters = [],int $perPage = 15): LengthAwarePaginator;
}
