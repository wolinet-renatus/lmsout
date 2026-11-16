<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared portal notification feed and read-state handlers for student & instructor controllers.
 *
 * @author Renatus Bernardo
 */
trait HandlesPortalNotifications
{
    /**
     * Return JSON notification feed + unread counts for header bell and inbox polling.
     */
    public function feed(Request $request, ChatService $chatService): JsonResponse
    {
        $user = $request->user();
        $role = $this->portalNotificationRole();
        $readRouteName = $role . '.notifications.read';
        $readAllRouteName = $role . '.notifications.readAll';
        $indexRouteName = $role . '.notifications.index';

        $items = $user->notifications()
            ->latest()
            ->limit(12)
            ->get()
            ->map(function ($notification) use ($readRouteName): array {
                $data = is_array($notification->data) ? $notification->data : [];
                $type = $data['type'] ?? 'info';

                return [
                    'id' => $notification->id,
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'message' => (string) ($data['message'] ?? ''),
                    'type' => $type,
                    'url' => $data['url'] ?? null,
                    'read_url' => route($readRouteName, $notification->id),
                    'is_unread' => $notification->read_at === null,
                    'time_ago' => $notification->created_at?->diffForHumans() ?? 'Just now',
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'unread_messages' => $chatService->getUnreadCount($user->id, $role),
            'read_all_url' => route($readAllRouteName),
            'index_url' => route($indexRouteName),
            'notifications' => $items,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark one notification as read; supports JSON or browser redirect.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications()->count(),
                'url' => $url,
            ]);
        }

        if ($url) {
            return redirect($url);
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications as read; supports JSON or browser redirect.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back();
    }
}
