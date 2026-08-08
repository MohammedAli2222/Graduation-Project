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
}
