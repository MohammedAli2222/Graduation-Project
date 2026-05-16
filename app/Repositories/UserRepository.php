<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{

    public function createUser(array $data): User
    {
        return User::create([
            'first_name'   => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * جلب بيانات المستخدم مع البروفايل الخاص به بناءً على العلاقة.
     * @param User $user
     * @param string $relation اسم العلاقة (مثل studentProfile)
     */
    public function loadProfile(User $user, string $relation): User
    {
        return $user->load($relation);
    }
}
