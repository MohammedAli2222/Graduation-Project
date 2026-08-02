<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AddToCartRequest;
use App\Http\Resources\Student\CartResource;
use App\Services\Student\StudentCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentCartController extends Controller
{
    public function __construct(
        protected StudentCartService $cartService
    ) {}

    /**
     * استعراض سلة المشتريات الخاصة بالطالب.
     */
    
    public function show(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $cart = $this->cartService->getMyCart($user->id);

            return response_success(new CartResource($cart), 200, 'تم جلب السلة بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ في جلب سلة الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي.');
        }
    }

    /**
     * إضافة منتج إلى السلة.
     */
    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $validated = $request->validated();

            $this->cartService->addProductToCart(
                $user->id,
                (int) $validated['product_id'],
                (int) $validated['quantity']
            );

            // جلب السلة المحدثة لعرضها في الرد
            $cart = $this->cartService->getMyCart($user->id);

            return response_success(new CartResource($cart), 200, 'تمت إضافة المنتج للسلة بنجاح.');
        } catch (Exception $e) {
            // التعامل مع الخطأ 409 (اختلاف المتجر) الذي هندسناه مسبقاً
            $statusCode = in_array($e->getCode(), [404, 409]) ? $e->getCode() : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    /**
     * إزالة منتج من السلة.
     */
    public function remove(int $cartItemId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $this->cartService->removeProductFromCart($user->id, $cartItemId);

            return response_success(null, 200, 'تمت إزالة العنصر من السلة.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    /**
     * تحديث كمية عنصر في السلة.
     */
    public function updateQuantity(\App\Http\Requests\Student\UpdateCartItemRequest $request, int $cartItemId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $validated = $request->validated();

            $this->cartService->updateCartItemQuantity(
                $user->id,
                $cartItemId,
                (int) $validated['quantity']
            );

            $cart = $this->cartService->getMyCart($user->id);

            return response_success(new CartResource($cart), 200, 'تم تحديث كمية المنتج بالسلة بنجاح.');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 404]) ? $e->getCode() : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    /**
     * إفراغ السلة بالكامل.
     */
    public function clear(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $this->cartService->clearMyCart($user->id);

            return response_success(null, 200, 'تم إفراغ السلة بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ في إفراغ سلة الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء إفراغ السلة.');
        }
    }
}
