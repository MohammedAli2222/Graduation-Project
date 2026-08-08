<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\ProductResource;
use App\Services\Student\StudentMarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentSellerBrowseController extends Controller
{
    public function __construct(
        protected StudentMarketplaceService $marketplaceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $filters = $request->only(['search', 'category_id', 'condition']);

            $products = $this->marketplaceService->getAllStudentProducts($filters, $perPage);

            $paginatedData = ProductResource::collection($products)->response()->getData(true);

            return response_success($paginatedData, 200, 'تم جلب منتجات الزملاء الطلاب بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب منتجات الطلاب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    public function show(int $productId): JsonResponse
    {
        try {
            $result = $this->marketplaceService->getProductWithSellerOthers($productId);

            $responseData = [
                'product'        => new ProductResource($result['product']),
                'other_products' => ProductResource::collection($result['other_products']),
            ];

            return response_success(
                $responseData,
                200,
                'تم جلب تفاصيل المنتج بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء عرض تفاصيل منتج الطالب: " . $e->getMessage());
            return response_error(null, 404, 'المنتج المطلوب غير موجود أو حدث خطأ داخلي.');
        }
    }
}
