<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15)->through(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'user',
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'route' => $notification->data['route'] ?? null,
                'user_id' => $notification->data['user_id'] ?? null,
                'priority' => $notification->data['priority'] ?? null,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'time' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
            ];
        });

        return \Inertia\Inertia::render('Notifications/Index', [
            'allNotifications' => $notifications,
        ]);
    }
}
