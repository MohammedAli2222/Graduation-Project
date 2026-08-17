<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserNotificationResource;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service) {}

    public function index(Request $request)
    {
        $notifications = $this->service->getForUser(
            $request->user()->id,
            $request->boolean('unread'),
            (int) $request->query('per_page', 15)
        );

        return response_success([
            'data' => UserNotificationResource::collection($notifications->items()),
            'pagination' => [
                'total' => $notifications->total(),
                'count' => $notifications->count(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ], 200, 'Notifications retrieved successfully.');
    }

    public function unreadCount(Request $request)
    {
        return response_success(
            ['count' => $this->service->unreadCount($request->user()->id)],
            200,
            'Unread notifications count retrieved successfully.'
        );
    }

    public function markAsRead(Request $request, int $id)
    {
        try {
            $this->service->markAsRead($request->user()->id, $id);

            return response_success(null, 200, 'Notification marked as read.');
        } catch (Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? (int) $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function markAllAsRead(Request $request)
    {
        $count = $this->service->markAllAsRead($request->user()->id);

        return response_success(['updated_count' => $count], 200, 'All notifications marked as read.');
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $this->service->delete($request->user()->id, $id);

            return response_success(null, 200, 'Notification deleted.');
        } catch (Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? (int) $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
