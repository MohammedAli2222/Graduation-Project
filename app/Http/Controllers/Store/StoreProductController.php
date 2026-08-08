<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Enums\ProductAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Http\Requests\Store\UpdateStoreProductRequest;
use App\Http\Resources\Store\ProductResource;
use App\Repositories\CategoryRepository;
use App\Services\Unified\SellerProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StoreProductController extends Controller
{
    /**
     * حقن الخدمة الموحدة ومستودع الفئات.
     */
    public function __construct(
        protected SellerProductService $productService,
        protected CategoryRepository $categoryRepo
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $perPage = (int) $request->query('per_page', 15);

            // بناء مصفوفة الفلاتر من الـ Query Parameters فقط عند إرسالها فعلياً
            // (وليس مجرد فحص وجود المفتاح)، حتى لا يُطبَّق فلتر بقيمة فارغة
            $filters = [];
            $availabilityStatus = $request->query('availability_status');

            if ($availabilityStatus !== null) {
                // تحقق صارم من أن القيمة المرسلة إحدى قيم Enum الفعلية
                // (available/limited/out_of_stock) قبل تمريرها للطبقات الأعمق
                if (ProductAvailability::tryFrom($availabilityStatus) === null) {
                    return response_error(null, 422, 'قيمة availability_status غير صالحة.');
                }

                $filters['availability_status'] = $availabilityStatus;
            }

            $products = $this->productService->listProducts($user->id, $filters, $perPage);

            $resourceData = ProductResource::collection($products)->response()->getData(true);

            return response_success($resourceData, 200, 'تم جلب منتجات المتجر بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب منتجات المتجر: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validated = $request->validated();
            $images = $request->file('images');

            $product = $this->productService->createProduct($user->id, $validated, $images);

            return response_success(
                new ProductResource($product),
                201, 
                'تم إضافة المنتج بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء إضافة منتج للمتجر: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء حفظ المنتج.');
        }
    }

    public function update(UpdateStoreProductRequest $request, int $productId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $validated = $request->validated();
            $images = $request->file('images');

            $product = $this->productService->updateProduct($user->id, $productId, $validated, $images);

            return response_success(
                new ProductResource($product),
                200,
                'تم تحديث بيانات المنتج بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء تحديث منتج المتجر: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء تحديث المنتج.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function destroy(int $productId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $this->productService->deleteProduct($user->id, $productId);

            return response_success(null, 200, 'تم حذف المنتج بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء حذف منتج المتجر: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء حذف المنتج.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function show(int $productId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $product = $this->productService->getProductDetails($user->id, $productId);

            return response_success(new ProductResource($product), 200, 'تم جلب تفاصيل المنتج بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function getCategoriesDropdown(): JsonResponse
    {
        // القراءة تمر عبر المستودع الذي يتكفّل بالكاش داخلياً (24 ساعة) بدل ضرب القاعدة مباشرة
        $categories = $this->categoryRepo->getDropdown();
        return response_success($categories, 200, 'تم جلب الفئات بنجاح.');
    }
}