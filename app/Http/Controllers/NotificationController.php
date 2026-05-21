<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications
     */
    public function recent(): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     * ✅ HARDENED: Added ownership verification before update
     */
    public function markAsRead(string $id): JsonResponse
    {
        // ✅ CRITICAL FIX: Verify user owns this notification
        $notification = auth()->user()->notifications()->findOrFail($id);
        
        // Double-check authentication context
        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification
     * ✅ HARDENED: Added ownership verification before delete
     */
    public function destroy(string $id): JsonResponse
    {
        // ✅ CRITICAL FIX: Verify user owns this notification
        $notification = auth()->user()->notifications()->findOrFail($id);
        
        // Double-check authentication context
        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notification->delete();

        return response()->json(['success' => true]);
    }
}
