<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Store\StoreStatisticService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class StoreOrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected StoreStatisticService $statisticService
    ) {}

    public function listStoreOrders(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepo->getStoreOrdersOptimized($storeId, $filters, $perPage);
    }

    public function updateOrderStatus(int $storeId, int $orderId, array $data): Order
    {
        $order = $this->orderRepo->findStoreOrder($storeId, $orderId);

        if (! $order) {
            throw new Exception('الطلب غير موجود أو لا تملك صلاحية تعديله.', 404);
        }

        if (in_array($order->status, [OrderStatus::COMPLETED->value, OrderStatus::REJECTED->value])) {
            throw new Exception('لا يمكن تعديل حالة طلب مكتمل أو تم رفضه مسبقاً.', 400);
        }

        if ($data['status'] !== OrderStatus::REJECTED->value) {
            $data['rejection_reason'] = null;
        }

        DB::beginTransaction();
        try {
            $this->orderRepo->update($order, $data);

            if ($data['status'] === OrderStatus::REJECTED->value) {
                foreach ($order->orderItems as $item) {
                    $product = $item->product;
                    
                    if ($product) {
                        $product->quantity += $item->quantity;
                        
                        if ($product->quantity > 0) {
                            $product->availability_status = ProductAvailability::AVAILABLE->value;
                        }

                        $product->save();
                    }
                }
            }

            DB::commit();

            $this->statisticService->invalidateLiveCache($storeId);

            return $order->refresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getOrderDetails(int $storeId, int $orderId): Order
    {
        $order = $this->orderRepo->findStoreOrder($storeId, $orderId);

        if (! $order) {
            throw new Exception('الطلب غير موجود أو لا يتبع لك.', 404);
        }

        return $order;
    }
}