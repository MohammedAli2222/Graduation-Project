<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest; // تم الاستيراد بنجاح
use App\Http\Requests\Store\UpdateStoreProductRequest;
use App\Http\Resources\Store\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentProductController extends Controller
{
    /**
     * حقن مستودع المنتجات.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}


    public function index(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $products = Product::query()
                ->where('store_id', $user->id)
                ->latest()
                ->get();

            return response_success(
                ProductResource::collection($products),
                200,
                'تم جلب منتجاتك المعروضة للبيع بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ في جلب منتجات الطالب البائع: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء جلب البيانات.');
        }
    }

    /**
     * إضافة أداة طبية جديدة من قبل الطالب لغرض البيع باستخدام التحقق الموحد.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // التحقق من الصلاحية الأمنية عبر الـ Policy
            if (! Gate::allows('create', Product::class)) {
                return response_error(null, 403, 'غير مصرح لك بنشر منتجات للبيع.');
            }

            // استخراج البيانات بعد التحقق منها عبر StoreProductRequest
            $validated = $request->validated();

            // ربط المنتج بالطالب مباشرة
            $validated['store_id'] = $user->id;

            // إنشاء المنتج
            $product = Product::create($validated);

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

    /**
     * تحديث بيانات أداة معروضة من قبل الطالب باستخدام التحقق الموحد.
     */
    public function update(UpdateStoreProductRequest $request, Product $product): JsonResponse
    {
        try {
            // الجدار الأمني: التأكد أن الطالب هو صاحب المنتج
            if (! Gate::allows('update', $product)) {
                return response_error(null, 403, 'عملية مرفوضة أمنياً: لا تمتلك صلاحية تعديل هذا المنتج.');
            }

            // استخراج البيانات المحدثة (يفترض أن واجهة الفلاتر ترسل جميع الحقول المطلوبة كـ PUT Request)
            $validated = $request->validated();

            // تحديث البيانات
            $product->update($validated);

            return response_success(
                new ProductResource($product),
                200,
                'تم تحديث بيانات الأداة الطبية بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء تحديث منتج الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء تحديث البيانات.');
        }
    }

    /**
     * حذف الأداة الطبية المعروضة.
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // الجدار الأمني: التحقق من ملكية الطالب للمنتج
            if (! Gate::allows('delete', $product)) {
                return response_error(null, 403, 'عملية مرفوضة أمنياً: لا يمكنك حذف منتج لا تملكه.');
            }

            $product->delete();

            return response_success(null, 200, 'تم سحب الأداة وحذفها من المنصة بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء حذف منتج الطالب: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء محاولة الحذف.');
        }
    }
}
