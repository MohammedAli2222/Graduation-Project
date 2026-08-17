<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Repositories\Contracts\PromotionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PromotionBrowseService
{
    public function __construct(
        protected PromotionRepositoryInterface $promotionRepo
    ) {}

    public function getActivePromotions(int $perPage = 15): LengthAwarePaginator
    {
        return $this->promotionRepo->getActivePromotions($perPage);
    }
}
