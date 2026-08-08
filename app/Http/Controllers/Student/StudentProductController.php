<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Http\Requests\Store\UpdateStoreProductRequest;
use App\Http\Resources\Store\ProductResource;
use App\Models\Product;
use App\Services\Unified\SellerProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentProductController extends Controller
{
  
    public function __construct(
        protected SellerProductService $productService
    ) {}

    public function index(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $products = $this->productService->listProducts($user->id, [], 50);

            $resourceData = ProductResource::collection($products)->response()->getData(true);

            return response_success($resourceData, 200, 'تم جلب منتجاتك المعروضة للبيع بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ في جلب منتجات الطالب البائع: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! Gate::allows('create', Product::class)) {
                return response_error(null, 403, 'غير مصرح لك بنشر منتجات للبيع.');
            }

            $validated = $request->validated();
            $images = $request->file('images');

            $product = $this->productService->createProduct($user->id, $validated, $images);

            return response_success(
                new ProductResource($product),
                210,
                'تم نشر أداة البيع الخاصة بك بنجاح على المنصة.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء إضافة منتج بواسطة طالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء حفظ المنتج.');
        }
    }

    public function update(UpdateStoreProductRequest $request, Product $product): JsonResponse
    {
        try {
            if (! Gate::allows('update', $product)) {
                return response_error(null, 403, 'عملية مرفوضة أمنياً: لا تمتلك صلاحية تعديل هذا المنتج.');
            }

            $validated = $request->validated();
            $images = $request->file('images');

            $updatedProduct = $this->productService->updateProduct($user->id, $product->id, $validated, $images);

            return response_success(
                new ProductResource($updatedProduct),
                200,
                'تم تحديث بيانات الأداة الطبية بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء تحديث منتج الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء تحديث البيانات.');
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            if (! Gate::allows('delete', $product)) {
                return response_error(null, 403, 'عملية مرفوضة أمنياً: لا يمكنك حذف منتج لا تملكه.');
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            $this->productService->deleteProduct($user->id, $product->id);

            return response_success(null, 200, 'تم سحب الأداة وحذفها من المنصة بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء حذف منتج الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء محاولة الحذف.');
        }
    }
}