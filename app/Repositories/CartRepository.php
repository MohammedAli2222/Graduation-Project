<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    public function __construct(
        protected Cart $model,
        protected CartItem $itemModel
    ) {}


    public function findOrCreateForStudent(int $studentId): Cart
    {
        return $this->model->firstOrCreate(['student_id' => $studentId]);
    }

    public function getStudentCartWithDetails(int $studentId): ?Cart
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->with(['items.product:id,store_id,name,price,availability_status,condition'])
            ->first();
    }


    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity): CartItem
    {

        $item = $this->itemModel->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
            return $item;
        }

        return $this->itemModel->create([
            'cart_id'    => $cart->id,
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]);
    }

    public function removeItem(Cart $cart, int $cartItemId): bool
    {
        return $this->itemModel->where('cart_id', $cart->id)
            ->where('id', $cartItemId)
            ->delete() > 0;
    }


    public function updateItemQuantity(Cart $cart, int $cartItemId, int $quantity): ?CartItem
    {
        $item = $this->itemModel->where('cart_id', $cart->id)
            ->where('id', $cartItemId)
            ->first();

        if ($item) {
            $item->quantity = $quantity;
            $item->save();
            return $item;
        }

        return null;
    }

    public function clearCart(Cart $cart): bool
    {
        return $this->itemModel->where('cart_id', $cart->id)->delete() > 0;
    }
}
