<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;

interface FcmTokenRepositoryInterface
{
    public function storeToken(int $userId, string $fcmToken, ?string $deviceType = null): UserDeviceToken;

    public function getTokensForUser(int $userId): Collection;

    public function deleteToken(string $fcmToken): bool;

    /**
     * حذف توكن مملوك لهذا المستخدم تحديداً فقط — يُستخدم عند تسجيل الخروج، على
     * عكس deleteToken() غير المقيَّد بمالك والمخصّص لتنظيف توكنات أثبتت
     * Firebase عدم صلاحيتها (لا علاقة له بمدخلات المستخدم مباشرة).
     */
    public function deleteTokenForUser(int $userId, string $fcmToken): bool;
}
