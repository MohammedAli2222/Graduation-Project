<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\OrderResource;
use App\Services\Student\StudentOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentCheckoutController extends Controller
{
    public function __construct(
        protected StudentOrderService $orderService
    ) {}

    public function checkout(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $order = $this->orderService->checkout($user->id);

            return response_success(new OrderResource($order), 201, 'تم إنشاء الطلب بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 400) ? 400 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء عملية الدفع للطالب: " . $e->getMessage());
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
