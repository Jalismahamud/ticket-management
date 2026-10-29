<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Fetch user notifications list with unread counter (AJAX / JSON)
     */
    public function fetch(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []], 401);
        }

        $filter = $request->query('filter', 'all');

        $query = $user->notifications();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->take(20)->get()->map(function (DatabaseNotification $n) {
            $data = $n->data;
            $type = $data['type'] ?? 'general';
            $ticketId = $data['ticket_id'] ?? null;

            return [
                'id' => $n->id,
                'type' => $type,
                'message' => $data['message'] ?? 'Notification alert',
                'subject' => $data['subject'] ?? null,
                'ticket_id' => $ticketId,
                'ticket_number' => $data['ticket_number'] ?? null,
                'url' => $ticketId ? route('tickets.show', $ticketId) : '#',
                'is_unread' => is_null($n->read_at),
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
                'created_at_human' => $n->created_at?->diffForHumans() ?? 'Just now',
            ];
        });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all unread notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user?->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a single notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->delete();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Clear all read notifications
     */
    public function clearRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->notifications()->whereNotNull('read_at')->delete();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}

