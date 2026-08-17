<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(protected NotificationRepositoryInterface $repo) {}

    public function getForUser(int $userId, bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateForUser($userId, $unreadOnly, $perPage);
    }

    public function unreadCount(int $userId): int
    {
        return $this->repo->unreadCountForUser($userId);
    }

    public function markAsRead(int $userId, int $notificationId): void
    {
        if (! $this->repo->markAsReadForUser($userId, $notificationId)) {
            throw new Exception('Notification not found.', 404);
        }
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->repo->markAllAsReadForUser($userId);
    }

    public function delete(int $userId, int $notificationId): void
    {
        if (! $this->repo->deleteForUser($userId, $notificationId)) {
            throw new Exception('Notification not found.', 404);
        }
    }
}
