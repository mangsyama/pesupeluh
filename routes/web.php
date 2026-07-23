<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ServiceManagementController;
use App\Http\Controllers\ReportController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function (Request $request) {
    if ($request->hasSession()) {
        $request->session()->save();
    }

    $user = $request->user()->load(['role', 'supportingUnit', 'room']);
    $roleName = $user->role->name ?? 'REPORTER';

    return Inertia::render('Dashboard/Index', [
        'userRole' => $roleName,
        'dashboardStats' => Inertia::defer(function () use ($user, $roleName) {
            $baseQuery = \App\Models\ServiceTicket::query();

            // Apply role-based scoping
            if ($roleName === 'REPORTER') {
                $baseQuery->where('reporter_id', $user->id);
            } elseif (in_array($roleName, ['UNIT_HEAD', 'TECHNICIAN']) && $user->supporting_unit_id) {
                $unitId = $user->supporting_unit_id;
                $baseQuery->whereHas('category.unitFeature', function ($q) use ($unitId) {
                    $q->where('supporting_unit_id', $unitId);
                });
            } elseif ($roleName === 'ROOM_HEAD' && $user->room_id) {
                $baseQuery->where('room_id', $user->room_id);
            }

            // 1. STAFF / REPORTER
            if ($roleName === 'REPORTER') {
                $counts = (clone $baseQuery)
                    ->selectRaw("
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status IN ('ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_count,
                        SUM(CASE WHEN status = 'PENDING_VALIDATION' THEN 1 ELSE 0 END) as pending_count
                    ")
                    ->first();

                $totalTicketsCount = (int) ($counts->total_count ?? 0);
                $inProgressCount = (int) ($counts->in_progress_count ?? 0);
                $completedCount = (int) ($counts->completed_count ?? 0);
                $pendingTicketsCount = (int) ($counts->pending_count ?? 0);

                $recentTickets = (clone $baseQuery)
                    ->select([
                        'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,feature_id',
                        'category.unitFeature:id,name,supporting_unit_id',
                        'category.unitFeature.supportingUnit:id,name,division_id',
                        'category.unitFeature.supportingUnit.division:id,name',
                    ])
                    ->latest()
                    ->take(4)
                    ->get();

                return [
                    'role' => 'REPORTER',
                    'stat1' => ['label' => 'Total Laporan Saya', 'value' => $totalTicketsCount, 'type' => 'total'],
                    'stat2' => ['label' => 'Dalam Pengerjaan', 'value' => $inProgressCount, 'type' => 'progress'],
                    'stat3' => ['label' => 'Selesai Dikerjakan', 'value' => $completedCount, 'type' => 'completed'],
                    'stat4' => ['label' => 'Menunggu Verifikasi', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                    'recentTickets' => $recentTickets,
                    'breakdownData' => [],
                ];
            }

            // 2. KEPALA UNIT / TEKNISI
            if (in_array($roleName, ['UNIT_HEAD', 'TECHNICIAN'])) {
                $counts = (clone $baseQuery)
                    ->selectRaw("
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status IN ('ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_count,
                        SUM(CASE WHEN status = 'PENDING_VALIDATION' THEN 1 ELSE 0 END) as pending_count
                    ")
                    ->first();

                $totalTicketsCount = (int) ($counts->total_count ?? 0);
                $inProgressCount = (int) ($counts->in_progress_count ?? 0);
                $completedCount = (int) ($counts->completed_count ?? 0);
                $pendingTicketsCount = (int) ($counts->pending_count ?? 0);

                $categoryCounts = (clone $baseQuery)
                    ->join('feature_categories', 'service_tickets.category_id', '=', 'feature_categories.id')
                    ->select('feature_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('feature_categories.id', 'feature_categories.name')
                    ->orderByDesc('count')
                    ->take(5)
                    ->get();

                $breakdownData = $categoryCounts->map(function ($cat, $index) use ($totalTicketsCount) {
                    $percentage = $totalTicketsCount > 0 ? round(($cat->count / $totalTicketsCount) * 100) : 0;
                    $palette = ['bg-indigo-500', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];
                    return [
                        'name' => $cat->name,
                        'division_name' => 'Kategori Unit',
                        'count' => (int) $cat->count,
                        'percentage' => $percentage,
                        'color' => $palette[$index % count($palette)],
                    ];
                });

                $recentTickets = (clone $baseQuery)
                    ->select([
                        'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,feature_id',
                        'category.unitFeature:id,name,supporting_unit_id',
                        'category.unitFeature.supportingUnit:id,name,division_id',
                        'category.unitFeature.supportingUnit.division:id,name',
                    ])
                    ->latest()
                    ->take(4)
                    ->get();

                $unitName = $user->supportingUnit->name ?? 'Unit';

                return [
                    'role' => $roleName,
                    'unitName' => $unitName,
                    'stat1' => ['label' => "Total Laporan {$unitName}", 'value' => $totalTicketsCount, 'type' => 'total'],
                    'stat2' => ['label' => 'Dalam Pengerjaan', 'value' => $inProgressCount, 'type' => 'progress'],
                    'stat3' => ['label' => 'Selesai Dikerjakan', 'value' => $completedCount, 'type' => 'completed'],
                    'stat4' => ['label' => 'Menunggu Disposisi', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                    'recentTickets' => $recentTickets,
                    'breakdownData' => $breakdownData,
                ];
            }

            // 3. KEPALA RUANGAN
            if ($roleName === 'ROOM_HEAD') {
                $counts = (clone $baseQuery)
                    ->selectRaw("
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status IN ('ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_count,
                        SUM(CASE WHEN status = 'PENDING_VALIDATION' THEN 1 ELSE 0 END) as pending_count
                    ")
                    ->first();

                $totalTicketsCount = (int) ($counts->total_count ?? 0);
                $inProgressCount = (int) ($counts->in_progress_count ?? 0);
                $completedCount = (int) ($counts->completed_count ?? 0);
                $pendingTicketsCount = (int) ($counts->pending_count ?? 0);

                $categoryCounts = (clone $baseQuery)
                    ->join('feature_categories', 'service_tickets.category_id', '=', 'feature_categories.id')
                    ->select('feature_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('feature_categories.id', 'feature_categories.name')
                    ->orderByDesc('count')
                    ->take(5)
                    ->get();

                $breakdownData = $categoryCounts->map(function ($cat, $index) use ($totalTicketsCount) {
                    $percentage = $totalTicketsCount > 0 ? round(($cat->count / $totalTicketsCount) * 100) : 0;
                    $palette = ['bg-indigo-500', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];
                    return [
                        'name' => $cat->name,
                        'division_name' => 'Kategori Ruangan',
                        'count' => (int) $cat->count,
                        'percentage' => $percentage,
                        'color' => $palette[$index % count($palette)],
                    ];
                });

                $recentTickets = (clone $baseQuery)
                    ->select([
                        'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,feature_id',
                        'category.unitFeature:id,name,supporting_unit_id',
                        'category.unitFeature.supportingUnit:id,name,division_id',
                        'category.unitFeature.supportingUnit.division:id,name',
                    ])
                    ->latest()
                    ->take(4)
                    ->get();

                $roomName = $user->room->name ?? 'Ruangan';

                return [
                    'role' => 'ROOM_HEAD',
                    'roomName' => $roomName,
                    'stat1' => ['label' => "Total Laporan {$roomName}", 'value' => $totalTicketsCount, 'type' => 'total'],
                    'stat2' => ['label' => 'Dalam Pengerjaan', 'value' => $inProgressCount, 'type' => 'progress'],
                    'stat3' => ['label' => 'Selesai Dikerjakan', 'value' => $completedCount, 'type' => 'completed'],
                    'stat4' => ['label' => 'Menunggu Validasi', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                    'recentTickets' => $recentTickets,
                    'breakdownData' => $breakdownData,
                ];
            }

            // 4. GLOBAL (ADMINISTRATOR, DIRECTOR, DIVISION_HEAD, SECTION_HEAD)
            $ticketCounts = \Illuminate\Support\Facades\DB::table('service_tickets')
                ->selectRaw("
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'PENDING_VALIDATION' THEN 1 ELSE 0 END) as pending_count
                ")
                ->first();

            $totalTicketsCount = (int) ($ticketCounts->total_count ?? 0);
            $pendingTicketsCount = (int) ($ticketCounts->pending_count ?? 0);

            $unitCounts = \Illuminate\Support\Facades\DB::table('service_tickets')
                ->join('feature_categories', 'service_tickets.category_id', '=', 'feature_categories.id')
                ->join('unit_features', 'feature_categories.feature_id', '=', 'unit_features.id')
                ->join('supporting_units', 'unit_features.supporting_unit_id', '=', 'supporting_units.id')
                ->select('supporting_units.id as unit_id', 'supporting_units.division_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->groupBy('supporting_units.id', 'supporting_units.division_id')
                ->get();

            $medikTicketsCount = (int) $unitCounts->where('division_id', 1)->sum('count');
            $nonMedikTicketsCount = (int) $unitCounts->where('division_id', 2)->sum('count');

            $unitCountMap = $unitCounts->pluck('count', 'unit_id');
            $breakdownData = \App\Models\SupportingUnit::with('division:id,name')
                ->get()
                ->map(function ($unit) use ($unitCountMap, $totalTicketsCount) {
                    $count = (int) ($unitCountMap->get($unit->id) ?? 0);
                    $percentage = $totalTicketsCount > 0 ? round(($count / $totalTicketsCount) * 100) : 0;
                    
                    $colors = [
                        'LABORATORIUM' => 'bg-indigo-500',
                        'KESLING'      => 'bg-emerald-500',
                        'IPSRS'        => 'bg-amber-500',
                        'FARMASI'      => 'bg-sky-500',
                        'RADIOLOGI'    => 'bg-teal-500',
                        'GIZI'         => 'bg-pink-500',
                        'LAUNDRY'      => 'bg-purple-500',
                        'CSSD'         => 'bg-slate-500',
                    ];
                    
                    $color = $colors[strtoupper($unit->name)] ?? 'bg-slate-500';

                    return [
                        'name' => $unit->name,
                        'division_name' => $unit->division->name ?? '',
                        'count' => $count,
                        'percentage' => $percentage,
                        'color' => $color,
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->take(5);

            $recentTickets = \App\Models\ServiceTicket::select([
                'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                \Illuminate\Support\Facades\DB::raw(
                    \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                        ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                        : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                )
            ])
            ->with([
                'reporter:id,name',
                'room:id,name',
                'category:id,name,feature_id',
                'category.unitFeature:id,name,supporting_unit_id',
                'category.unitFeature.supportingUnit:id,name,division_id',
                'category.unitFeature.supportingUnit.division:id,name',
            ])
            ->latest()
            ->take(4)
            ->get();

            return [
                'role' => 'GLOBAL',
                'stat1' => ['label' => 'Total Laporan', 'value' => $totalTicketsCount, 'type' => 'total'],
                'stat2' => ['label' => 'Penunjang Medik', 'value' => $medikTicketsCount, 'type' => 'medik'],
                'stat3' => ['label' => 'Penunjang Non-Medik', 'value' => $nonMedikTicketsCount, 'type' => 'non_medik'],
                'stat4' => ['label' => 'Menunggu Verifikasi', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                'recentTickets' => $recentTickets,
                'breakdownData' => $breakdownData,
            ];
        }),
    ]);
})->middleware(['auth', 'verified', 'page.access'])->name('dashboard');

Route::middleware(['auth', 'verified', 'page.access'])->group(function () {
    Route::get('/services', function () {
        return Inertia::render('Service/Index', [
            'initialSection' => null,
            'divisions' => Inertia::defer(fn() => \App\Models\Division::with('supportingUnits')->get()),
        ]);
    })->name('services.index');

    Route::get('/services/medik', function () {
        return Inertia::render('Service/Index', [
            'initialSection' => 'medik',
            'divisions' => Inertia::defer(fn() => \App\Models\Division::with('supportingUnits')->get()),
        ]);
    })->name('services.medik');

    Route::get('/services/non-medik', function () {
        return Inertia::render('Service/Index', [
            'initialSection' => 'non-medik',
            'divisions' => Inertia::defer(fn() => \App\Models\Division::with('supportingUnits')->get()),
        ]);
    })->name('services.non-medik');

    Route::get('/services/units/{supportingUnit}', [\App\Http\Controllers\ServiceController::class, 'showUnit'])->name('services.units.show');
    Route::post('/services/tickets', [\App\Http\Controllers\ServiceController::class, 'storeTicket'])->name('services.tickets.store');

    // Ticket Workflows & Actions
    Route::get('/tickets/{ticket:uuid}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket:uuid}/assign', [\App\Http\Controllers\TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket:uuid}/respond', [\App\Http\Controllers\TicketController::class, 'respond'])->name('tickets.respond');
    Route::post('/tickets/{ticket:uuid}/resolve', [\App\Http\Controllers\TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket:uuid}/resume', [\App\Http\Controllers\TicketController::class, 'resume'])->name('tickets.resume');
});

Route::middleware(['auth', 'verified', 'page.access'])->group(function () {
    // User Management
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/approvals', [UserManagementController::class, 'indexApprovals'])->name('users.approvals');
    Route::get('/users/approvals/{user:uuid}', [UserManagementController::class, 'showApprovalDetail'])->name('users.approvals.show');
    Route::patch('/users/{user:uuid}/approve', [UserManagementController::class, 'approveUser'])->name('users.approve');
    Route::get('/users/admin', [UserManagementController::class, 'indexAdmin'])->name('users.admin');
    Route::get('/users/management', [UserManagementController::class, 'indexManagement'])->name('users.management');
    Route::get('/users/unit-head', [UserManagementController::class, 'indexUnitHead'])->name('users.unit-head');
    Route::get('/users/technician', [UserManagementController::class, 'indexTechnician'])->name('users.technician');
    Route::get('/users/room-head', [UserManagementController::class, 'indexRoomHead'])->name('users.room-head');
    Route::get('/users/reporter', [UserManagementController::class, 'indexReporter'])->name('users.reporter');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user:uuid}', [UserManagementController::class, 'update'])->name('users.update');
    Route::patch('/users/{user:uuid}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user:uuid}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user:uuid}/permissions', [UserManagementController::class, 'updatePermissions'])->name('users.update-permissions');

    // Layanan Manajemen
    Route::get('/service-management', function () {
        return redirect()->route('service-management.rooms');
    })->name('service-management.index');
    Route::get('/services/rooms', [ServiceManagementController::class, 'indexRooms'])->name('service-management.rooms');
    Route::get('/services/categories', [ServiceManagementController::class, 'indexCategories'])->name('service-management.categories');
    Route::get('/services/supporting-units', [ServiceManagementController::class, 'indexSupportingUnits'])->name('service-management.supporting-units');
    
    Route::post('/service-management/rooms', [ServiceManagementController::class, 'storeRoom'])->name('service-management.rooms.store');
    Route::put('/service-management/rooms/{room}', [ServiceManagementController::class, 'updateRoom'])->name('service-management.rooms.update');
    Route::delete('/service-management/rooms/{room}', [ServiceManagementController::class, 'destroyRoom'])->name('service-management.rooms.destroy');

    Route::post('/service-management/categories', [ServiceManagementController::class, 'storeCategory'])->name('service-management.categories.store');
    Route::put('/service-management/categories/{category}', [ServiceManagementController::class, 'updateCategory'])->name('service-management.categories.update');
    Route::delete('/service-management/categories/{category}', [ServiceManagementController::class, 'destroyCategory'])->name('service-management.categories.destroy');

    Route::post('/service-management/divisions', [ServiceManagementController::class, 'storeDivision'])->name('service-management.divisions.store');
    Route::put('/service-management/divisions/{division}', [ServiceManagementController::class, 'updateDivision'])->name('service-management.divisions.update');
    Route::delete('/service-management/divisions/{division}', [ServiceManagementController::class, 'destroyDivision'])->name('service-management.divisions.destroy');

    Route::post('/service-management/units', [ServiceManagementController::class, 'storeSupportingUnit'])->name('service-management.units.store');
    Route::put('/service-management/units/{unit}', [ServiceManagementController::class, 'updateSupportingUnit'])->name('service-management.units.update');
    Route::delete('/service-management/units/{unit}', [ServiceManagementController::class, 'destroySupportingUnit'])->name('service-management.units.destroy');

    Route::get('/reports/export', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

    Route::get('/reports', [ReportController::class, 'history'])->name('reports.history');
    Route::post('/reports/filters', [ReportController::class, 'storeFilters'])->name('reports.filters');
    Route::get('/reports/{ticket:uuid}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('/reports-management', [\App\Http\Controllers\ReportManagementController::class, 'index'])->name('reports-management.index');
    Route::post('/reports-management/filters', [\App\Http\Controllers\ReportManagementController::class, 'storeFilters'])->name('reports-management.filters');
    Route::get('/reports-management/{ticket:uuid}', [\App\Http\Controllers\ReportManagementController::class, 'show'])->name('reports-management.show');

    Route::get('/settings', function () {
        return Inertia::render('UserSettings/Index');
    })->name('settings.index');

    // Design System / Component Testing
    Route::get('/design-system', function () {
        return Inertia::render('DesignSystem/Index');
    })->name('design-system.index');

    Route::get('/design-system/buttons-badges', function () {
        return Inertia::render('DesignSystem/ButtonsBadges');
    })->name('design-system.buttons-badges');

    Route::get('/design-system/forms', function () {
        return Inertia::render('DesignSystem/Forms');
    })->name('design-system.forms');

    Route::get('/design-system/modals-alerts', function () {
        return Inertia::render('DesignSystem/ModalsAlerts');
    })->name('design-system.modals-alerts');

    Route::get('/design-system/tables', function () {
        return Inertia::render('DesignSystem/Tables');
    })->name('design-system.tables');

    Route::get('/design-system/cards', function () {
        return Inertia::render('DesignSystem/Cards');
    })->name('design-system.cards');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification mark-as-read & list
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session()->put('locale', $locale);
        session()->save();
    }
    return redirect()->back();
})->name('lang.switch');

Route::post('/login-face', function (Request $request) {
    $request->validate([
        'face_descriptor' => 'required|array|min:128|max:128',
    ]);

    $inputDescriptor = $request->input('face_descriptor');
    $users = User::whereNotNull('face_descriptor')->get();

    $matchedUser = null;
    $bestDistance = 999.0;
    $threshold = 0.45; // smaller means more similar, 0.45 is secure for tinyFaceDetector + ResNet-150

    foreach ($users as $user) {
        $storedDescriptor = $user->face_descriptor;
        if (!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
            continue;
        }

        // Calculate Euclidean distance
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $diff = (float)$inputDescriptor[$i] - (float)$storedDescriptor[$i];
            $sum += $diff * $diff;
        }
        $distance = sqrt($sum);

        if ($distance < $threshold && $distance < $bestDistance) {
            $bestDistance = $distance;
            $matchedUser = $user;
        }
    }

    if ($matchedUser) {
        if (!$matchedUser->is_active) {
            $isPending = is_null($matchedUser->approved_by);
            return response()->json([
                'success' => false,
                'status_type' => $isPending ? 'PENDING_APPROVAL' : 'SUSPENDED',
                'message' => $isPending
                    ? '[PENDING_APPROVAL] Akun Anda masih dalam proses pendaftaran dan menunggu verifikasi oleh Administrator.'
                    : '[SUSPENDED] Akun Anda telah ditangguhkan (suspended) oleh Administrator. Silakan hubungi Administrator.',
            ], 403);
        }

        Auth::login($matchedUser, true);
        $request->session()->regenerate();
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'name' => $matchedUser->name,
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => __('Face not recognized or not registered.'),
    ], 401);
})->name('login.face');

Route::get('/models/{file}', function ($file) {
    $basePath = public_path('models');
    $realBasePath = realpath($basePath);
    $filePath = realpath($basePath . '/' . $file);

    if ($filePath && strpos($filePath, $realBasePath) === 0 && is_file($filePath)) {
        return response()->file($filePath, [
            'Content-Type' => 'application/octet-stream',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
    abort(404);
})->where('file', '.*');

require __DIR__ . '/auth.php';
