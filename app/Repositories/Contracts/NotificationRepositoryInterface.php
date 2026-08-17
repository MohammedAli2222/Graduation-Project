<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, string $title, string $body, string $type, array $data = []): UserNotification;

    public function paginateForUser(int $userId, bool $unreadOnly, int $perPage): LengthAwarePaginator;

    public function unreadCountForUser(int $userId): int;

    /**
     * @return bool  false إذا لم يوجد الإشعار أو لم يعد للمستخدم المطالِب
     */
    public function markAsReadForUser(int $userId, int $notificationId): bool;

    /**
     * @return int  عدد الإشعارات التي تحوّلت إلى مقروءة
     */
    public function markAllAsReadForUser(int $userId): int;

    /**
     * @return bool  false إذا لم يوجد الإشعار أو لم يعد للمستخدم المطالِب
     */
    public function deleteForUser(int $userId, int $notificationId): bool;
}
