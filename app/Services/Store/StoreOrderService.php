<?php


namespace App\Services\Store;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

class StoreOrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepo
    ) {}


    public function listStoreOrders(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepo->getStoreOrdersOptimized($storeId, $perPage);
    }


    public function updateOrderStatus(int $storeId, int $orderId, array $data): Order
    {
        $order = $this->orderRepo->findStoreOrder($storeId, $orderId);

        if (! $order) {
            throw new Exception('الطلب غير موجود أو لا تملك صلاحية تعديله.', 404);
        }

        if ($data['status'] !== OrderStatus::REJECTED->value) {
            $data['rejection_reason'] = null;
        }

        $this->orderRepo->update($order, $data);

        return $order->refresh();
    }

    public function getOrderDetails(int $storeId, int $orderId): Order
    {
        $order = $this->orderRepo->findStoreOrder($storeId, $orderId);

        if (! $order) {
            throw new Exception('الطلب غير موجود أو لا يتبع لمتجرك.', 404);
        }

        return $order;
    }

    
}
