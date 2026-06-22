<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * تحديد ما إذا كان المستخدم يستطيع عرض تفاصيل منتج معين في الإدارة.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }

    /**
     * تحديد ما إذا كان المستخدم يمتلك صلاحية إضافة منتج جديد للبيع.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('store_owner') || $user->hasRole('student');
    }

    /**
     * تحديد ما إذا كان المستخدم يستطيع تحديث بيانات منتج معين.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }

    /**
     * تحديد ما إذا كان المستخدم يستطيع حذف منتج معين من المنصة.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }
}
