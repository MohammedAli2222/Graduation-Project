<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserDeviceToken;
use App\Repositories\Contracts\FcmTokenRepositoryInterface;
use Illuminate\Support\Collection;

class FcmTokenRepository implements FcmTokenRepositoryInterface
{
    public function __construct(protected UserDeviceToken $model) {}

    public function storeToken(int $userId, string $fcmToken, ?string $deviceType = null): UserDeviceToken
    {
        // التوكن فريد بطبيعته لذلك يتم تحديث مالكه ونوع جهازه إذا كان موجوداً مسبقاً، أو إنشاء سجل جديد
        // هذا يسمح للمستخدم الواحد بامتلاك عدة توكنات في حال تسجيل الدخول من أكثر من جهاز
        return $this->model->updateOrCreate(
            ['fcm_token' => $fcmToken],
            ['user_id' => $userId, 'device_type' => $deviceType]
        );
    }

    public function getTokensForUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function deleteToken(string $fcmToken): bool
    {
        return $this->model->where('fcm_token', $fcmToken)->delete() > 0;
    }
}
