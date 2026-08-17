<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\PromotionResource;
use App\Services\Student\PromotionBrowseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionBrowseController extends Controller
{
    public function __construct(
        protected PromotionBrowseService $promotionBrowseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $promotions = $this->promotionBrowseService->getActivePromotions($perPage);

            $resourceData = PromotionResource::collection($promotions)->response()->getData(true);

            return response_success($resourceData, 200, 'تم جلب العروض الترويجية بنجاح.');
        } catch (Exception $e) {
            Log::error('خطأ أثناء تصفح العروض الترويجية: ' . $e->getMessage());

            return response_error(null, 500, 'حدث خطأ داخلي.');
        }
    }
}
