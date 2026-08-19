<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Events\NewStoreOrderPlacedEvent;
use App\Events\ProductLowStockEvent;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Store\StoreStatisticService;
use Illuminate\Support\Facades\DB;
use Exception;

class StudentOrderService
{
    // نفس حد "الكمية المنخفضة" المعتمد بإحصائيات المتجر (StoreStatisticService::LOW_STOCK_THRESHOLD)
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(
        protected CartRepositoryInterface $cartRepo,
        protected OrderRepositoryInterface $orderRepo,
        protected StoreStatisticService $statisticService
    ) {}

    public function checkout(int $studentId): Order
    {
        $cart = $this->cartRepo->getStudentCartWithDetails($studentId);

        if (! $cart || $cart->items->isEmpty()) {
            throw new Exception('سلة المشتريات فارغة. لا يمكن إتمام الطلب.', 400);
        }

        $storeId = $cart->items->first()->product->store_id;

        $productIds = $cart->items->pluck('product_id')->toArray();

        $lowStockProducts = [];

        $order = DB::transaction(function () use ($studentId, $storeId, $cart, $productIds, &$lowStockProducts) {
            $totalAmount = 0.0;
            $orderItemsData = [];

            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $product = $lockedProducts->get($item->product_id);

                if (! $product) {
                    throw new Exception("عذراً، المنتج غير متوفر حالياً.", 404);
                }

                if ($product->quantity < $item->quantity) {
                    throw new Exception("الكمية المطلوبة من الأداة ({$product->name}) غير متوفرة. المتاح: {$product->quantity}", 400);
                }

                $previousQuantity = $product->quantity;
                $product->quantity -= $item->quantity;

                if ($product->quantity === 0) {
                    $product->availability_status = ProductAvailability::OUT_OF_STOCK->value;
                }

                $product->save();

                // نشعر صاحب المتجر فقط لحظة العبور الأولى تحت الحد، لا مع كل عملية بيع لاحقة تبقيه تحته
                if ($product->quantity < self::LOW_STOCK_THRESHOLD && $previousQuantity >= self::LOW_STOCK_THRESHOLD) {
                    $lowStockProducts[] = $product;
                }

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

        // الطلب الجديد يغيّر عدّاد الطلبات المعلّقة ومخزون المنتجات معاً، وكان
        // يُبطَل فقط عند تحديث حالة الطلب لاحقاً (StoreOrderService::updateOrderStatus)
        // فتبقى لوحة تحكم المتجر تعرض أرقاماً قديمة حتى ١٠ دقائق بعد كل عملية شراء
        $this->statisticService->invalidateLiveCache($storeId);

        // إطلاق الحدث بعد نجاح المعاملة لإشعار صاحب المتجر بالطلب الجديد دون تأخير استجابة الـ API الرئيسية
        NewStoreOrderPlacedEvent::dispatch($order);

        foreach ($lowStockProducts as $product) {
            ProductLowStockEvent::dispatch($product);
        }

        return $order;
    }
}