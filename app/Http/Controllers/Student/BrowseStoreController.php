<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\BrowseStoreService;
use App\Http\Resources\Store\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowseStoreController extends Controller
{
    public function __construct(
        protected BrowseStoreService $browseStoreService
    ) {}

    /**
     * عرض جميع المتاجر الرسمية للطلاب.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $stores = $this->browseStoreService->getStores($perPage);

        $formattedStores = $stores->through(function ($store) {
            return [
                'id'          => $store->id,
                'store_name'  => $store->storeProfile?->store_name ?? trim($store->first_name . ' ' . $store->last_name),
                'phone'       => $store->storeProfile?->store_phone ?? 'رقم الهاتف غير متوفر',
                'address'     => $store->storeProfile?->store_address ?? 'العنوان غير متوفر',
                'email'       => $store->email,
            ];
        });

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب قائمة المتاجر بنجاح.',
            'data'    => $formattedStores,
        ]);
    }

    /**
     * عرض جميع المنتجات التابعة لمتجر محدد.
     */
    public function showStoreProducts(Request $request, int $id): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $products = $this->browseStoreService->getStoreProducts($id, $perPage);

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب منتجات المتجر بنجاح.',
            'data'    => ProductResource::collection($products)->response()->getData(true),
        ]);
    }
}
