<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{

    public function getStoreOrdersOptimized(int $storeId, int $perPage = 15): LengthAwarePaginator;

    public function findStoreOrder(int $storeId, int $orderId): ?Order;

    public function update(Order $order, array $data): bool;

    public function createOrder(array $orderData): Order;

    /**
     * جلب سجل المشتريات الخاص بالطالب (كمشتري).
     */
    public function getStudentPurchasesOptimized(int $studentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * جلب تفاصيل طلب محدد قام الطالب بشرائه.
     */
    public function findStudentPurchase(int $studentId, int $orderId): ?Order;
}
