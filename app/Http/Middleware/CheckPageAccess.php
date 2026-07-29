<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPageAccess
{
    /**
     * Map route names to their permission keys.
     * Child routes inherit from parent permission keys.
     */
    private const ROUTE_PERMISSION_MAP = [
        // Dashboard
        'dashboard' => 'dashboard',

        // Services
        'services.index' => 'services.index',
        'services.medik' => 'services.index',
        'services.non-medik' => 'services.index',
        'services.units.show' => 'services.index',
        'services.tickets.store' => 'services.index',

        // Ticket Actions & Workflows
        'tickets.show' => 'reports.history',
        'tickets.assign' => 'reports-management.index',
        'tickets.respond' => 'reports-management.index',
        'tickets.resolve' => 'reports-management.index',
        'tickets.resume' => 'reports-management.index',

        // Reports - History
        'reports.history' => 'reports.history',
        'reports.show' => 'reports.history',
        'reports.filters' => 'reports.history',

        // Reports - Management
        'reports-management.index' => 'reports-management.index',
        'reports-management.show' => 'reports-management.index',
        'reports-management.filters' => 'reports-management.index',

        // Reports - Export
        'reports.index' => 'reports.index',
        'reports.export.pdf' => 'reports.index',
        'reports.export.csv' => 'reports.index',

        // Service Management (Master Data)
        'service-management.index' => 'service-management.rooms',
        'service-management.rooms' => 'service-management.rooms',
        'service-management.rooms.store' => 'service-management.rooms',
        'service-management.rooms.update' => 'service-management.rooms',
        'service-management.rooms.destroy' => 'service-management.rooms',
        'service-management.categories' => 'service-management.categories',
        'service-management.categories.store' => 'service-management.categories',
        'service-management.categories.update' => 'service-management.categories',
        'service-management.categories.destroy' => 'service-management.categories',
        'service-management.supporting-units' => 'service-management.supporting-units',
        'service-management.divisions.store' => 'service-management.supporting-units',
        'service-management.divisions.update' => 'service-management.supporting-units',
        'service-management.divisions.destroy' => 'service-management.supporting-units',
        'service-management.units.store' => 'service-management.supporting-units',
        'service-management.units.update' => 'service-management.supporting-units',
        'service-management.units.destroy' => 'service-management.supporting-units',

        // User Management
        'users.approvals' => 'users.approvals',
        'users.approvals.show' => 'users.approvals',
        'users.approve' => 'users.approvals',
        'users.index' => 'users.index',
        'users.admin' => 'users.index',
        'users.management' => 'users.index',
        'users.unit-head' => 'users.index',
        'users.technician' => 'users.index',
        'users.room-head' => 'users.index',
        'users.reporter' => 'users.index',
        'users.store' => 'users.index',
        'users.update' => 'users.index',
        'users.toggle-active' => 'users.index',
        'users.destroy' => 'users.index',
        'users.update-permissions' => 'users.index',

        // Settings & System
        'settings.index' => 'settings.index',
        'admin.wa-gateway.index' => 'admin.wa-gateway.index',
        'admin.wa-gateway.status' => 'admin.wa-gateway.index',
        'admin.wa-gateway.logout' => 'admin.wa-gateway.index',
        'admin.wa-gateway.test' => 'admin.wa-gateway.index',
        'admin.qr-code.index' => 'admin.qr-code.index',

        // Design System
        'design-system.index' => 'design-system.index',
        'design-system.buttons-badges' => 'design-system.index',
        'design-system.forms' => 'design-system.index',
        'design-system.modals-alerts' => 'design-system.index',
        'design-system.tables' => 'design-system.index',
        'design-system.cards' => 'design-system.index',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        // Look up the permission key for this route
        $permissionKey = self::ROUTE_PERMISSION_MAP[$routeName] ?? null;

        // If route is not in the map, allow access (e.g. profile, notifications, etc.)
        if ($permissionKey === null) {
            return $next($request);
        }

        // Check if user has access
        if (!$user->hasPageAccess($permissionKey)) {
            if ($request->expectsJson() || $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            // Redirect to dashboard with error message
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
