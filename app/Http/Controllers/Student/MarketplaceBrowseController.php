<?php


namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class MarketplaceBrowseController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}

    /**
     * استعراض السوق العام مع الفلترة والبحث.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // استقبال الفلاتر المسموح بها فقط من الرابط (Query Parameters)
            $filters = $request->only(['search', 'category_id', 'condition']);

            // جلب البيانات من المستودع بشكل مقسم (Paginated)
            $products = $this->productRepo->getMarketplaceProducts($filters);

            // استخراج البيانات مع الـ Meta (معلومات الصفحات) لتتوافق مع الـ Flutter
            $paginatedData = ProductResource::collection($products)->response()->getData(true);

            return response_success(
                $paginatedData,
                200,
                'تم جلب منتجات السوق بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء تصفح السوق: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    /**
     * عرض تفاصيل منتج واحد محدد في السوق.
     */
    public function show(int $productId): JsonResponse
    {
        try {
            $product = $this->productRepo->findById($productId);

            if (! $product) {
                return response_error(null, 404, 'المنتج المطلوب غير موجود.');
            }

            // تحميل العلاقات اللازمة للعرض
            $product->load(['seller', 'category']);

            return response_success(
                new ProductResource($product),
                200,
                'تم جلب تفاصيل المنتج بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء عرض تفاصيل المنتج: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي.');
        }
    }
}
