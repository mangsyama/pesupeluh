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

    protected static ?array $cachedTranslations = null;

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        $user = $request->user();

        if ($user && !$user->is_active) {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $user = null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'page_permissions' => $user ? $user->getEffectivePermissions() : [],
                'pending_approvals_count' => $user ? \Inertia\Inertia::defer(fn() => \App\Models\User::whereNull('approved_by')->count()) : 0,
                'pending_reports_count' => $user ? \Inertia\Inertia::defer(function () use ($user) {
                    $roleId = (int) $user->role_id;
                    $userId = (int) $user->id;

                    $query = \App\Models\ServiceTicket::whereNull('deleted_at')
                        ->whereIn('status', ['PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING']);

                    if ($user->isAdmin() || $user->isDirector() || (int) $user->role_id === \App\Models\Role::KEPALA_BIDANG) {
                        // Admin, Direktur & Kabid (Full/Dashboard)
                    } elseif ((int) $user->role_id === \App\Models\Role::PJ_RUANGAN && $user->room_id) {
                        $query->where('room_id', $user->room_id);
                    } elseif ($user->canDisposisi() && $user->supporting_unit_id) {
                        $query->whereHas('category', function ($q) use ($user) {
                            $q->where('supporting_unit_id', $user->supporting_unit_id);
                        });
                    } elseif ($user->canDisposisi()) {
                        // Role disposisi lainnya tanpa pembatasan unit
                    } elseif ($user->isTechnician()) {
                        $query->whereHas('assignments', function ($q) use ($userId) {
                            $q->where('technician_id', $userId);
                        });
                    } else {
                        return 0;
                    }

                    return $query->count();
                }) : 0,
            ],
            'notifications' => $user ? \Inertia\Inertia::defer(fn() => $user->unreadNotifications()->take(15)->get()->map(function ($notification) {
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
            })) : [],
            'unread_notifications_count' => $user ? \Inertia\Inertia::defer(fn() => $user->unreadNotifications()->count()) : 0,
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
        if (isset(self::$cachedTranslations[$locale])) {
            return self::$cachedTranslations[$locale];
        }

        $file = base_path("lang/{$locale}.json");

        if (file_exists($file)) {
            return self::$cachedTranslations[$locale] = json_decode(file_get_contents($file), true) ?? [];
        }

        return self::$cachedTranslations[$locale] = [];
    }
}
