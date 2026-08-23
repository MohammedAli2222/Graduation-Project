<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Cart;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use Exception;

class StudentCartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepo
    ) {}

    public function getMyCart(int $studentId): ?Cart
    {
        $this->cartRepo->findOrCreateForStudent($studentId);
        
        return $this->cartRepo->getStudentCartWithDetails($studentId);
    }

    public function addProductToCart(int $studentId, int $productId, int $quantity): void
    {
        $product = Product::find($productId);

        if (! $product) {
            throw new Exception('المنتج غير موجود.', 404);
        }

        if ($product->store_id === $studentId) {
            throw new Exception('لا يمكنك شراء أداة تعرضها أنت للبيع.', 403);
        }

        if ($product->quantity < $quantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة. المتاح حالياً: {$product->quantity}", 400);
        }

        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        $cartWithDetails = $this->cartRepo->getStudentCartWithDetails($studentId);

        if ($cartWithDetails && $cartWithDetails->items->isNotEmpty()) {
            $currentStoreId = $cartWithDetails->items->first()->product->store_id;

            if ($currentStoreId !== $product->store_id) {
                throw new Exception('لا يمكنك إضافة منتجات من بائعين مختلفين في نفس السلة. يرجى إتمام طلبك الحالي أو تفريغ السلة أولاً.', 409);
            }
        }

        $this->cartRepo->addOrUpdateItem($cart, $productId, $quantity);
    }

    public function removeProductFromCart(int $studentId, int $cartItemId): void
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        
        $isRemoved = $this->cartRepo->removeItem($cart, $cartItemId);

        if (! $isRemoved) {
            throw new Exception('العنصر غير موجود في سلتك.', 404);
        }
    }

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

    public function clearMyCart(int $studentId): void
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        
        $this->cartRepo->clearCart($cart);
    }
}