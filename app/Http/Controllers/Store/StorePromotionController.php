<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StorePromotionRequest;
use App\Http\Requests\Store\UpdateStorePromotionRequest;
use App\Http\Resources\Store\PromotionResource;
use App\Services\Store\StorePromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StorePromotionController extends Controller
{
    public function __construct(
        protected StorePromotionService $promotionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $perPage = (int) $request->query('per_page', 15);

            $promotions = $this->promotionService->listPromotions($user->id, $perPage);
            $resourceData = PromotionResource::collection($promotions)->response()->getData(true);

            return response_success($resourceData, 200, 'تم جلب العروض الترويجية بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ في جلب عروض المتجر: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي.');
        }
    }

    public function show(int $promotionId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $promotion = $this->promotionService->getPromotionDetails($user->id, $promotionId);

            return response_success(new PromotionResource($promotion), 200, 'تم جلب تفاصيل العرض بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404 || $e->getCode() === 403) ? $e->getCode() : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function store(StorePromotionRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $validated = $request->validated();

            $productIds = $validated['product_ids'];
            unset($validated['product_ids']);

            $promotion = $this->promotionService->createPromotion($user->id, $validated, $productIds);

            return response_success(new PromotionResource($promotion), 201, 'تم إنشاء العرض الترويجي بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 403) ? 403 : 500;
            if ($statusCode === 500) Log::error("خطأ إضافة عرض للمتجر: " . $e->getMessage());
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function update(UpdateStorePromotionRequest $request, int $promotionId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $validated = $request->validated();

            $productIds = $validated['product_ids'] ?? null;
            unset($validated['product_ids']);

            $promotion = $this->promotionService->updatePromotion($user->id, $promotionId, $validated, $productIds);

            return response_success(new PromotionResource($promotion), 200, 'تم تحديث العرض بنجاح.');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 404]) ? $e->getCode() : 500;
            if ($statusCode === 500) {
                Log::error("خطأ تحديث عرض: " . $e->getMessage());
            }
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function destroy(int $promotionId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $this->promotionService->deletePromotion($user->id, $promotionId);

            return response_success(null, 200, 'تم حذف العرض الترويجي بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
