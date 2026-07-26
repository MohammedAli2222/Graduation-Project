<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Models\Order;
use App\Models\Product;
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

    /**
     * تنفيذ عملية الشراء مع الخصم الآمن باستخدام القفل المتشائم.
     */
    public function checkout(int $studentId): Order
    {
        $cart = $this->cartRepo->getStudentCartWithDetails($studentId);

        if (! $cart || $cart->items->isEmpty()) {
            throw new Exception('سلة المشتريات فارغة. لا يمكن إتمام الطلب.', 400);
        }

        // تحديد البائع (المنتجات كلها تعود لبائع واحد بفضل حماية السلة المختلطة)
        $storeId = $cart->items->first()->product->store_id;

        // استخراج معرفات المنتجات لقفلها
        $productIds = $cart->items->pluck('product_id')->toArray();

        // بدء المعاملة الآمنة
        return DB::transaction(function () use ($studentId, $storeId, $cart, $productIds) {
            $totalAmount = 0.0;
            $orderItemsData = [];

            // القفل المتشائم (Pessimistic Locking) لمنع التضارب Race Conditions
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $product = $lockedProducts->get($item->product_id);

                if (! $product) {
                    throw new Exception("عذراً، المنتج غير متوفر حالياً.", 404);
                }

                // التحقق النهائي من المخزون بعد القفل
                if ($product->quantity < $item->quantity) {
                    throw new Exception("الكمية المطلوبة من الأداة ({$product->name}) غير متوفرة. المتاح: {$product->quantity}", 400);
                }

                // الخصم الفعلي من المخزون
                $product->quantity -= $item->quantity;

                // تحديث الحالة إذا نفدت الكمية
                if ($product->quantity === 0) {
                    $product->availability_status = ProductAvailability::OUT_OF_STOCK->value;
                }

                $product->save();

                // حساب التكلفة بالسعر الآمن الموثوق
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

            // إنشاء الطلب
            $order = $this->orderRepo->createOrder([
                'student_id'   => $studentId,
                'store_id'     => $storeId,
                'total_amount' => $totalAmount,
                'status'       => OrderStatus::PENDING->value,
            ]);

            // إرفاق عناصر الطلب
            $order->orderItems()->createMany($orderItemsData);

            // تفريغ السلة
            $this->cartRepo->clearCart($cart);

            return $order->load(['orderItems.product', 'store.storeProfile']);
        });
    }
}