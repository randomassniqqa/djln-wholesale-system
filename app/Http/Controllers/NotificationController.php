<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ──────────────────────────────────────────────
    // INDEX — Full notifications page
    // GET /notifications
    // ──────────────────────────────────────────────

    public function index(): View
    {
        $user          = auth()->user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $unreadCount   = $user->unreadNotifications()->count();

        // Mark all as read when viewing the page
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    // ──────────────────────────────────────────────
    // UNREAD COUNT — JSON for sidebar badge polling
    // GET /notifications/unread-count
    // ──────────────────────────────────────────────

    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    // ──────────────────────────────────────────────
    // RECENT — JSON for dropdown panel (top 10)
    // GET /notifications/recent
    // ──────────────────────────────────────────────

    public function recent(): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'data'       => $n->data,
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json($notifications);
    }

    // ──────────────────────────────────────────────
    // MARK AS READ — single notification
    // PUT /notifications/{notification}/read
    // ──────────────────────────────────────────────

    public function markAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // MARK ALL READ
    // PUT /notifications/mark-all-read
    // ──────────────────────────────────────────────

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // DESTROY — delete one notification
    // DELETE /notifications/{notification}
    // ──────────────────────────────────────────────

    public function destroy(string $id): JsonResponse|RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();

        // Support both AJAX and full-page requests
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification dismissed.');
    }

    // ──────────────────────────────────────────────
    // CLEAR ALL — wipe all of the user's notifications
    // DELETE /notifications
    // ──────────────────────────────────────────────

    public function destroyAll(): RedirectResponse
    {
        auth()->user()->notifications()->delete();

        return back()->with('success', 'All notifications cleared.');
    }
}
