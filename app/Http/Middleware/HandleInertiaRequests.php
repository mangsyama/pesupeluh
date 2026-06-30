<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'pending_approvals_count' => $request->user() ? \App\Models\User::whereNull('approved_by')->count() : 0,
            ],
            'notifications' => $request->user() ? $request->user()->unreadNotifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'user',
                    'title' => $notification->data['title'] ?? null,
                    'message' => $notification->data['message'] ?? null,
                    'route' => $notification->data['route'] ?? null,
                    'user_id' => $notification->data['user_id'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                ];
            }) : [],
            'unread_notifications_count' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            'locale' => app()->getLocale(),
            'translations' => $this->getTranslations(),
        ];
    }

    /**
     * Get translations for the current locale.
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();
        $file = base_path("lang/{$locale}.json");

        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?? [];
        }

        return [];
    }
}
