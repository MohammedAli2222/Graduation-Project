<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\OrderResource;
use App\Services\Student\StudentPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentPurchaseController extends Controller
{
    public function __construct(
        protected StudentPurchaseService $purchaseService
    ) {}

    /**
     * عرض سجل مشتريات الطالب بالكامل.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $perPage = (int) $request->query('per_page', 15);

            $purchases = $this->purchaseService->listStudentPurchases($user->id, $perPage);

            $resourceData = OrderResource::collection($purchases)->response()->getData(true);

            return response_success(
                $resourceData,
                200,
                'تم جلب سجل المشتريات بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب سجل مشتريات الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    /**
     * عرض تفاصيل طلب محدد من سجل المشتريات.
     */
    public function show(int $orderId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $purchase = $this->purchaseService->getPurchaseDetails($user->id, $orderId);

            return response_success(
                new OrderResource($purchase),
                200,
                'تم جلب تفاصيل المشتريات بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            Log::error("خطأ أثناء عرض تفاصيل فاتورة الطالب: " . $e->getMessage());
            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
