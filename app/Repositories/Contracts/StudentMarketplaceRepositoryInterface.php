<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StudentMarketplaceRepositoryInterface
{
    /**
     * جلب جميع الأدوات المعروضة للبيع من قبل الطلاب فقط (وليس المتاجر).
     */
    public function getAllStudentProducts(int $perPage = 15): LengthAwarePaginator;

    /**
     * جلب تفاصيل أداة محددة.
     */
    public function getProductDetails(int $productId): Product;

    /**
     * جلب الأدوات الأخرى التي يعرضها نفس الطالب (مع استبعاد الأداة الحالية المعروضة).
     */
    public function getOtherProductsByStudent(int $studentId, int $excludeProductId, int $limit = 4): Collection;
}
