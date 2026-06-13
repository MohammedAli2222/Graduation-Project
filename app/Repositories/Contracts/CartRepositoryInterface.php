<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\CartItem;

interface CartRepositoryInterface
{
    /**
     * جلب سلة الطالب أو إنشاؤها إن لم تكن موجودة.
     */
    public function findOrCreateForStudent(int $studentId): Cart;

    /**
     * جلب سلة الطالب مع التفاصيل (العناصر والمنتجات المرتبطة).
     */
    public function getStudentCartWithDetails(int $studentId): ?Cart;

    /**
     * إضافة منتج إلى السلة أو زيادة كميته إذا كان موجوداً مسبقاً.
     */
    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity): CartItem;

    /**
     * إزالة عنصر محدد من السلة.
     */
    public function removeItem(Cart $cart, int $cartItemId): bool;

    /**
     * تفريغ السلة بالكامل.
     */
    public function clearCart(Cart $cart): bool;
}
