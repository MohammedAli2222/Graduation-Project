<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Cart;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use Exception;

class StudentCartService
{
    /**
     * حقن مستودع السلة.
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepo
    ) {}

    /**
     * جلب سلة الطالب الحالية مع التفاصيل.
     */
    public function getMyCart(int $studentId): ?Cart
    {
        // التأكد من وجود السلة أولاً
        $this->cartRepo->findOrCreateForStudent($studentId);
        
        return $this->cartRepo->getStudentCartWithDetails($studentId);
    }

    /**
     * إضافة منتج إلى السلة مع تطبيق القواعد المعمارية للمخزون وتعدد البائعين.
     */
    public function addProductToCart(int $studentId, int $productId, int $quantity): void
    {
        // 1. جلب المنتج المراد إضافته
        $product = Product::find($productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود.', 404);
        }

        // 2. التحقق المبدئي من المخزون
        if ($product->quantity < $quantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة. المتاح حالياً: {$product->quantity}", 400);
        }

        // 3. جلب سلة الطالب
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        $cartWithDetails = $this->cartRepo->getStudentCartWithDetails($studentId);

        // 4. الجدار الأمني: منع السلة المختلطة (Single-Store Cart Rule)
        if ($cartWithDetails && $cartWithDetails->items->isNotEmpty()) {
            $currentStoreId = $cartWithDetails->items->first()->product->store_id;

            if ($currentStoreId !== $product->store_id) {
                throw new Exception('لا يمكنك إضافة منتجات من بائعين مختلفين في نفس السلة. يرجى إتمام طلبك الحالي أو تفريغ السلة أولاً.', 409);
            }
        }

        // 5. الإضافة أو التحديث
        $this->cartRepo->addOrUpdateItem($cart, $productId, $quantity);
    }

    /**
     * إزالة منتج محدد من السلة.
     */
    public function removeProductFromCart(int $studentId, int $cartItemId): void
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        
        $isRemoved = $this->cartRepo->removeItem($cart, $cartItemId);

        if (! $isRemoved) {
            throw new Exception('العنصر غير موجود في سلتك.', 404);
        }
    }

    /**
     * تحديث كمية عنصر محدد في سلة الطالب.
     */
    public function updateCartItemQuantity(int $studentId, int $cartItemId, int $quantity): void
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        $cartWithDetails = $this->cartRepo->getStudentCartWithDetails($studentId);

        $cartItem = $cartWithDetails?->items->firstWhere('id', $cartItemId);

        if (! $cartItem || ! $cartItem->product) {
            throw new Exception('العنصر غير موجود في سلتك.', 404);
        }

        if ($cartItem->product->quantity < $quantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة في المستودع. المتاح حالياً: {$cartItem->product->quantity}", 400);
        }

        $this->cartRepo->updateItemQuantity($cart, $cartItemId, $quantity);
    }

    /**
     * إفراغ السلة بالكامل.
     */
    public function clearMyCart(int $studentId): void
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        
        $this->cartRepo->clearCart($cart);
    }
}