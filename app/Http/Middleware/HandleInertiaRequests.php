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
                'pending_approvals_count' => $user ? \App\Models\User::whereNull('approved_by')->count() : 0,
                'pending_reports_count' => $user ? (function () use ($user) {
                    $roleId = (int) $user->role_id;
                    $userId = (int) $user->id;

                    // Teknisi: Menghitung penugasan baru yang belum dikerjakan / belum tiba di lokasi ('ASSIGNED')
                    if ($user->isTechnician()) {
                        return \App\Models\ServiceTicket::whereNull('deleted_at')
                            ->where('status', 'ASSIGNED')
                            ->whereHas('assignments', function ($q) use ($userId) {
                                $q->where('technician_id', $userId);
                            })
                            ->count();
                    }

                    // Admin, Ka. Unit, Kabid, PJ Ruangan & Role Disposisi:
                    // Menghitung laporan yang MENUNGGU VALIDASI / DISPOSISI ('PENDING_VALIDATION')
                    $query = \App\Models\ServiceTicket::whereNull('deleted_at')
                        ->where('status', 'PENDING_VALIDATION');

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
                    } else {
                        return 0;
                    }

                    return $query->count();
                })() : 0,
            ],
            'notifications' => $user ? $user->unreadNotifications()->take(15)->get()->map(function ($notification) {
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
            }) : [],
            'unread_notifications_count' => $user ? $user->unreadNotifications()->count() : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info'    => fn () => $request->session()->get('info'),
            ],
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
