<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Promotion;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use App\Support\CacheGroup;
use App\Support\CacheVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PromotionRepository implements PromotionRepositoryInterface
{

    public function __construct(
        protected Promotion $model
    ) {}


    public function create(array $data): Promotion
    {
        return $this->model->create($data);
    }


    public function getStorePromotions(int $storeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->withCount('products')
            ->latest()
            ->paginate($perPage);
    }


    /**
     * العروض السارية حالياً من كل المتاجر (لتصفّح الطالب) — مفعّلة يدوياً
     * وضمن تاريخها الفعلي، بعكس getStorePromotions التي تعرض للمتجر كل
     * عروضه (منتهية أو مستقبلية أو معطّلة) لأنه هو من يديرها.
     */
    public function getActivePromotions(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->withCount('products')
            ->with(['products' => function ($query) {
                $query->with(['seller:id,first_name,last_name', 'media']);
            }])
            ->latest()
            ->paginate($perPage);
    }


    public function findStorePromotion(int $storeId, int $promotionId): ?Promotion
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('id', $promotionId)
            ->with('products:id,name,price')
            ->first();
    }

    public function update(Promotion $promotion, array $data): bool
    {
        return $promotion->update($data);
    }


    public function delete(Promotion $promotion): bool
    {
        return $promotion->delete() ?? false;
    }

    public function syncProducts(Promotion $promotion, array $productIds): array
    {
        $result = $promotion->products()->sync($productIds);

        // sync() على علاقة pivot لا يُطلق حدث updated على Promotion نفسه
        // (بخلاف حفظ حقول الموديل مباشرة)، فـ PromotionObserver وحده لا يكفي
        // لإبطال الكاش هنا رغم أن هذا هو التغيير الفعلي على "أي منتج عليه خصم".
        CacheVersion::bump(CacheGroup::store($promotion->store_id));

        return $result;
    }
}
