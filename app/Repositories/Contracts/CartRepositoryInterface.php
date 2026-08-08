<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\CartItem;

interface CartRepositoryInterface
{
    public function findOrCreateForStudent(int $studentId): Cart;

    public function getStudentCartWithDetails(int $studentId): ?Cart;

    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity): CartItem;

    public function removeItem(Cart $cart, int $cartItemId): bool;

    public function updateItemQuantity(Cart $cart, int $cartItemId, int $quantity): ?CartItem;

    public function clearCart(Cart $cart): bool;
}
