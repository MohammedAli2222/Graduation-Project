<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\UpdateOrderStatusRequest;
use App\Http\Resources\Store\OrderResource;
use App\Services\Store\StoreOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StoreOrderController extends Controller
{
    public function __construct(
        protected StoreOrderService $orderService
    ) {}


    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $perPage = (int) $request->query('per_page', 15);

            // بناء مصفوفة الفلاتر من الـ Query Parameters فقط عند إرسالها فعلياً
            $filters = [];
            $status = $request->query('status');

            if ($status !== null) {
                // تحقق صارم من أن القيمة المرسلة إحدى قيم Enum الفعلية
                // (pending/processing/ready/completed/rejected) قبل تمريرها للطبقات الأعمق
                if (OrderStatus::tryFrom($status) === null) {
                    return response_error(null, 422, 'قيمة status غير صالحة.');
                }

                $filters['status'] = $status;
            }

            $orders = $this->orderService->listStoreOrders($user->id, $filters, $perPage);
            $resourceData = OrderResource::collection($orders)->response()->getData(true);

            return response_success($resourceData, 200, 'تم جلب الطلبات بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب طلبات المتجر: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي.');
        }
    }


    public function updateStatus(UpdateOrderStatusRequest $request, int $orderId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $validated = $request->validated();

            $order = $this->orderService->updateOrderStatus($user->id, $orderId, $validated);

            return response_success(new OrderResource($order), 200, 'تم تحديث حالة الطلب بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function show(int $orderId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $order = $this->orderService->getOrderDetails($user->id, $orderId);

            return response_success(new OrderResource($order), 200, 'تم جلب تفاصيل الطلب بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
