<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(protected UserNotification $model) {}

    public function create(int $userId, string $title, string $body, string $type, array $data = []): UserNotification
    {
        return $this->model->create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);
    }

    public function paginateForUser(int $userId, bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->when($unreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->latest('id')
            ->paginate($perPage);
    }

    public function unreadCountForUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsReadForUser(int $userId, int $notificationId): bool
    {
        return $this->model
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]) > 0
            // إن كان الإشعار مقروءاً مسبقاً فلا صف يتحدّث (0)، لكن هذا ليس
            // خطأ 404: تحقّق ثانٍ من الملكية وحدها يفرّق بين "غير موجود" و
            // "مقروء أصلاً" دون تنفيذ تحديث إضافي بلا داعٍ.
            || $this->model->where('id', $notificationId)->where('user_id', $userId)->exists();
    }

    public function markAllAsReadForUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteForUser(int $userId, int $notificationId): bool
    {
        return $this->model
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
