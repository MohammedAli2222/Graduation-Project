<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    public function generateAndSendOtp(User $user): void
    {

        $otpCode = (string) random_int(100000, 999999);

        Cache::put('otp_user_'.$user->id, $otpCode, 300);

        $user->notify(new SendOtpNotification($otpCode));
    }

    public function verifyOtp(int $userId, string $inputCode)
    {
        $cacheKey = 'otp_user_'.$userId;

        $cachedOtp = Cache::get($cacheKey);

        if (! $cachedOtp || $cachedOtp !== $inputCode) {
            throw new \Exception('The verification code is invalid or has expired.', 422);
        }

        Cache::forget($cacheKey);
    }
}
