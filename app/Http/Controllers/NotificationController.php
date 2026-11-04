<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

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

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->back();
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notifications = $user->notifications()->paginate(20)->through(function ($notification) {
            $rawRoute = $notification->data['route'] ?? null;
            $route = null;
            if ($rawRoute && (str_starts_with($rawRoute, 'http://') || str_starts_with($rawRoute, 'https://'))) {
                $parsed = parse_url($rawRoute);
                $route = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '') . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
            } else {
                $route = $rawRoute;
            }

            return [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'user',
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'route' => $route,
                'user_id' => $notification->data['user_id'] ?? null,
                'priority' => $notification->data['priority'] ?? null,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'time' => $notification->created_at ? \Carbon\Carbon::parse($notification->created_at, 'UTC')->setTimezone(config('app.timezone', 'Asia/Makassar'))->diffForHumans() : null,
            ];
        });

        return \Inertia\Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'allNotifications' => $notifications,
        ]);
    }
}
