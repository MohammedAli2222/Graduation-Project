<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StudentOrderService
{
    /**
     * حقن مستودعات السلة والطلبات.
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepo,
        protected OrderRepositoryInterface $orderRepo
    ) {}


    public function checkout(int $studentId): Order
    {
        $cart = $this->cartRepo->getStudentCartWithDetails($studentId);

        if (! $cart || $cart->items->isEmpty()) {
            throw new Exception('سلة المشتريات فارغة. لا يمكن إتمام الطلب.', 400);
        }

        $storeId = $cart->items->first()->product->store_id;

        $totalAmount = 0.0;
        $orderItemsData = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            $unitPrice = (float) $product->price;
            $subtotal = $unitPrice * $item->quantity;

            $totalAmount += $subtotal;

            $orderItemsData[] = [
                'product_id' => $product->id,
                'quantity'   => $item->quantity,
                'unit_price' => $unitPrice,
                'subtotal'   => $subtotal,
            ];
        }

        return DB::transaction(function () use ($studentId, $storeId, $totalAmount, $orderItemsData, $cart) {

            $order = $this->orderRepo->createOrder([
                'student_id'   => $studentId,
                'store_id'     => $storeId,
                'total_amount' => $totalAmount,
                'status'       => OrderStatus::PENDING->value,
            ]);

            $order->orderItems()->createMany($orderItemsData);

            $this->cartRepo->clearCart($cart);

            return $order->load(['orderItems.product', 'store.storeProfile']);
        });
    }
}
