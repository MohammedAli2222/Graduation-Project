<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PromotionRepositoryInterface
{

    public function create(array $data): Promotion;


    public function getStorePromotions(int $storeId, int $perPage = 15): LengthAwarePaginator;


    public function getActivePromotions(int $perPage = 15): LengthAwarePaginator;


    public function findStorePromotion(int $storeId, int $promotionId): ?Promotion;


    public function update(Promotion $promotion, array $data): bool;


    public function delete(Promotion $promotion): bool;

    
    public function syncProducts(Promotion $promotion, array $productIds): array;
}
