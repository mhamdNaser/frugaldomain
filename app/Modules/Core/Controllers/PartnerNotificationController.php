<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Resources\PartnerNotificationDetailResource;
use App\Modules\Core\Resources\PartnerNotificationSummaryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class PartnerNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => PartnerNotificationSummaryResource::collection($notifications->getCollection()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function show(Request $request, string $id): PartnerNotificationDetailResource
    {
        $notification = $this->findUserNotification($request, $id);

        return new PartnerNotificationDetailResource($notification);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->findUserNotification($request, $id);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read successfully.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read successfully.',
        ]);
    }

    private function findUserNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();
    }
}
