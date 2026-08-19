<?php


namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class MarketplaceBrowseController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'category_id', 'condition']);

            // ما بنعرض على الطالب أدواته الخاصة يلي هو نفسه ناشرها للبيع وهو يتصفّح السوق كمشتري
            $products = $this->productRepo->getMarketplaceProducts($filters, 15, Auth::id());

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

    public function show(int $productId): JsonResponse
    {
        try {
            $product = $this->productRepo->findById($productId);

            if (! $product) {
                return response_error(null, 404, 'المنتج المطلوب غير موجود.');
            }

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
