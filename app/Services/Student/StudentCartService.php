<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StudentCartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepo
    ) {}


    public function getMyCart(int $studentId): Cart
    {
        $cart = $this->cartRepo->getStudentCartWithDetails($studentId);

        // إذا لم تكن هناك سلة بعد، السيرفس نفسه يتولى عملية الإنشاء
        if (! $cart) {
            $cart = $this->cartRepo->findOrCreateForStudent($studentId);
            $cart->load('items.product');
        }

        return $cart;
    }


    public function addProductToCart(int $studentId, int $productId, int $quantity): CartItem
    {
        $product = Product::find($productId);
        if (! $product) {
            throw new Exception('المنتج المطلوب غير موجود.', 404);
        }

        $cart = $this->cartRepo->findOrCreateForStudent($studentId);


        $firstItem = $cart->items()->with('product')->first();

        if ($firstItem !== null) {
            $existingStoreId = $firstItem->product->store_id;

            if ($existingStoreId !== $product->store_id) {
                throw new Exception(
                    'لا يمكنك إضافة منتجات من متاجر مختلفة في نفس السلة. يرجى إفراغ السلة الحالية أولاً قبل الطلب من متجر جديد.',
                    409
                );
            }
        }

        return $this->cartRepo->addOrUpdateItem($cart, $productId, $quantity);
    }


    public function removeProductFromCart(int $studentId, int $cartItemId): bool
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);

        $deleted = $this->cartRepo->removeItem($cart, $cartItemId);

        if (! $deleted) {
            throw new Exception('العنصر غير موجود في سلتك.', 404);
        }

        return true;
    }

    public function clearMyCart(int $studentId): bool
    {
        $cart = $this->cartRepo->findOrCreateForStudent($studentId);
        return $this->cartRepo->clearCart($cart);
    }
}
