<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Promotion;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class StorePromotionService
{

    public function __construct(
        protected PromotionRepositoryInterface $promotionRepo,
        protected ProductRepositoryInterface $productRepo
    ) {}

    /**
     * جلب قائمة العروض الترويجية الخاصة بالمتجر.
     */
    public function listPromotions(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->promotionRepo->getStorePromotions($storeId, $perPage);
    }

    /**
     * جلب تفاصيل عرض ترويجي محدد.
     */
    public function getPromotionDetails(int $storeId, int $promotionId): Promotion
    {
        $promotion = $this->promotionRepo->findStorePromotion($storeId, $promotionId);

        if (! $promotion) {
            throw new Exception('العرض الترويجي غير موجود أو لا تملك صلاحية الوصول إليه.', 404);
        }

        return $promotion;
    }

    /**
     * إنشاء عرض ترويجي جديد وربطه بالمنتجات بشكل آمن.
     */
    public function createPromotion(int $storeId, array $promotionData, array $productIds): Promotion
    {
        $this->verifyProductsOwnership($storeId, $productIds);

        $promotionData['store_id'] = $storeId;

        return DB::transaction(function () use ($promotionData, $productIds) {
            $promotion = $this->promotionRepo->create($promotionData);

            $this->promotionRepo->syncProducts($promotion, $productIds);

            return $promotion->load('products:id,name,price');
        });
    }

    /**
     * تحديث بيانات العرض الترويجي وتعديل المنتجات المشمولة.
     */
    public function updatePromotion(int $storeId, int $promotionId, array $promotionData, ?array $productIds = null): Promotion
    {
        $promotion = $this->getPromotionDetails($storeId, $promotionId);

        return DB::transaction(function () use ($promotion, $storeId, $promotionData, $productIds) {

            $this->promotionRepo->update($promotion, $promotionData);

            if ($productIds !== null) {
                $this->verifyProductsOwnership($storeId, $productIds);
                $this->promotionRepo->syncProducts($promotion, $productIds);
            }

            return $promotion->refresh()->load('products:id,name,price');
        });
    }

    /**
     * حذف العرض الترويجي.
     */
    public function deletePromotion(int $storeId, int $promotionId): bool
    {
        $promotion = $this->getPromotionDetails($storeId, $promotionId);

        return $this->promotionRepo->delete($promotion);
    }

    /**
     * دالة مساعدة (Helper) للتحقق الأمني من ملكية المنتجات للمتجر.
     *
     * @throws Exception
     */
    private function verifyProductsOwnership(int $storeId, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        // جلب عدد المنتجات التي تطابق أرقامها أرقام المنتجات المرسلة، وتعود لنفس المتجر
        $validCount = $this->productRepo->countStoreProductsByIds($storeId, $productIds);

        if ($validCount !== count($productIds)) {
            throw new Exception('عملية مرفوضة أمنياً: أحد المنتجات المحددة في العرض لا يتبع لمتجرك.', 403);
        }
    }
}
