<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * حقن نموذج الطلب.
     */
    public function __construct(
        protected Order $model
    ) {}



    public function getStoreOrdersOptimized(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            // إضافة student.studentProfile
            ->with(['student.studentProfile', 'orderItems.product:id,name'])
            ->latest()
            ->paginate($perPage);
    }

    public function findStoreOrder(int $storeId, int $orderId): ?Order
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('id', $orderId)
            ->with(['student.studentProfile', 'orderItems.product:id,name'])
            ->first();
    }


    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function createOrder(array $orderData): Order
    {
        return $this->model->create($orderData);
    }

    public function getStudentPurchasesOptimized(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->with(['store.storeProfile', 'store.studentProfile', 'orderItems.product:id,name'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جلب تفاصيل طلب محدد ضمن مشتريات الطالب.
     */
    public function findStudentPurchase(int $studentId, int $orderId): ?Order
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->where('id', $orderId)
            ->with(['store.storeProfile', 'store.studentProfile', 'orderItems.product:id,name'])
            ->first();
    }
}
