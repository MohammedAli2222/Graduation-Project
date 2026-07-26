<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

class StudentPurchaseService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepo
    ) {}

    /**
     * جلب سجل مشتريات الطالب.
     */
    public function listStudentPurchases(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepo->getStudentPurchasesOptimized($studentId, $perPage);
    }

    /**
     * جلب تفاصيل فاتورة/طلب محدد ضمن مشتريات الطالب.
     */
    public function getPurchaseDetails(int $studentId, int $orderId): Order
    {
        $order = $this->orderRepo->findStudentPurchase($studentId, $orderId);

        if (! $order) {
            throw new Exception('الطلب غير موجود أو لا يتبع لسجل مشترياتك.', 404);
        }

        return $order;
    }
}
