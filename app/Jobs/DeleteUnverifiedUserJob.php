<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DeleteUnverifiedUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        // إذا كان المستخدم موجوداً ولم يقم بتأكيد الـ OTP بعد ساعة من التسجيل
        if ($user && is_null($user->email_verified_at)) {
            DB::transaction(function () use ($user) {
                // 1. مسح جميع التوكنات
                $user->tokens()->delete();

                // 2. مسح البروفايل المرتبط
                $user->studentProfile()?->delete();
                $user->instructorProfile()?->delete();
                $user->departmentHeadProfile()?->delete();
                $user->storeProfile()?->delete();

                // 3. حذف المستخدم نهائياً
                $user->delete();
            });
        }
    }
}
