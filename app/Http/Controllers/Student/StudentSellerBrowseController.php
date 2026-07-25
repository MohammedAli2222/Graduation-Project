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

    /**
     * عرض جميع الأدوات المعروضة للبيع من قبل الطلاب فقط.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);

            $products = $this->marketplaceService->getAllStudentProducts($perPage);

            $paginatedData = ProductResource::collection($products)->response()->getData(true);

            return response_success(
                $paginatedData,
                200,
                'تم جلب منتجات الزملاء الطلاب بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب منتجات الطلاب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    /**
     * عرض تفاصيل أداة محددة لطالب مع اقتراح أدوات أخرى تابعة لنفس الزميل.
     */
    public function show(int $productId): JsonResponse
    {
        try {
            // جلب المصفوفة التي تحتوي على المنتج الأساسي والمنتجات الأخرى عبر الخدمة
            $result = $this->marketplaceService->getProductWithSellerOthers($productId);

            // تجهيز البيانات بالشكل المطلوب
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
