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

    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('store_owner') || $user->hasRole('student');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->store_id;
    }
}
