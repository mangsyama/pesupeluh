<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ServiceManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WaGatewayController;
use App\Http\Controllers\QrGeneratorController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

// ==============================================================================
// PUBLIC SANITATION & TOILET COMPLAINT PORTAL (PAN-SINAR KESLING)
// Diakses oleh Pengunjung / Pasien / Masyarakat via scan QR Code tanpa login
// ==============================================================================
Route::prefix('services/kesling/aduan-sanitasi')->name('public.sanitasi.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PublicSanitasiController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\PublicSanitasiController::class, 'store'])->name('store');
    Route::get('/sukses/{ticketNumber}', [\App\Http\Controllers\PublicSanitasiController::class, 'success'])->name('success');
    Route::get('/lacak', [\App\Http\Controllers\PublicSanitasiController::class, 'track'])->name('track');
});

// Alias & redirect untuk backward compatibility scan QR
Route::redirect('/aduan-sanitasi', '/services/kesling/aduan-sanitasi');
Route::redirect('/aduan-sanitasi/lacak', '/services/kesling/aduan-sanitasi/lacak');
Route::redirect('/aduan-sanitasi/track', '/services/kesling/aduan-sanitasi/lacak');
Route::redirect('/aduan-sanitasi/sukses/{ticketNumber}', '/services/kesling/aduan-sanitasi/sukses/{ticketNumber}');
Route::redirect('/services/kesling/aduan', '/services/kesling/aduan-sanitasi');
Route::redirect('/services/kesling/lacak', '/services/kesling/aduan-sanitasi/lacak');
Route::redirect('/public/report/toilet/{code}', '/services/kesling/aduan-sanitasi');

Route::get('/dashboard', function (Request $request) {
    if ($request->hasSession()) {
        $request->session()->save();
    }

    $user = $request->user()->load(['role', 'supportingUnit', 'room']);
    $roleId = (int) $user->role_id;

    return Inertia::render('Dashboard/Index', [
        'userRole' => $user->isReportOnly() ? 'REPORTER' : ($user->role->name ?? 'STAFF'),
        'dashboardStats' => Inertia::defer(function () use ($user, $roleId) {
            $baseQuery = \App\Models\ServiceTicket::query()->whereNull('service_tickets.deleted_at');

            // 1. STAFF / PELAPOR / BAGIAN (Report Only)
            if ($user->isReportOnly() || $roleId === \App\Models\Role::STAFF) {
                $baseQuery->where('reporter_id', $user->id);

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
                    ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                    ->select('issue_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('issue_categories.id', 'issue_categories.name')
                    ->orderByDesc('count')
                    ->take(5)
                    ->get();

                $breakdownData = $categoryCounts->map(function ($cat, $index) use ($totalTicketsCount) {
                    $percentage = $totalTicketsCount > 0 ? round(($cat->count / $totalTicketsCount) * 100) : 0;
                    $palette = ['bg-indigo-500', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];
                    return [
                        'name' => $cat->name,
                        'division_name' => 'Kategori Laporan',
                        'count' => (int) $cat->count,
                        'percentage' => $percentage,
                        'color' => $palette[$index % count($palette)],
                    ];
                });

                $recentTickets = (clone $baseQuery)
                    ->select([
                        'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                        'validated_at', 'validated_by', 'responded_at', 'resolved_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,supporting_unit_id',
                        'category.supportingUnit:id,name,type',
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
                    'breakdownData' => $breakdownData,
                    'analytics' => \App\Services\DashboardAnalyticsService::getAnalytics($baseQuery),
                ];
            }

            // 2. TEKNISI
            if ($user->isTechnician()) {
                $baseQuery->whereHas('assignments', function ($q) use ($user) {
                    $q->where('technician_id', $user->id);
                });

                $counts = (clone $baseQuery)
                    ->selectRaw("
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status IN ('ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_count,
                        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count
                    ")
                    ->first();

                $totalTicketsCount = (int) ($counts->total_count ?? 0);
                $inProgressCount = (int) ($counts->in_progress_count ?? 0);
                $completedCount = (int) ($counts->completed_count ?? 0);
                $pendingTicketsCount = (int) ($counts->pending_count ?? 0);

                $categoryCounts = (clone $baseQuery)
                    ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                    ->select('issue_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('issue_categories.id', 'issue_categories.name')
                    ->orderByDesc('count')
                    ->take(5)
                    ->get();

                $breakdownData = $categoryCounts->map(function ($cat, $index) use ($totalTicketsCount) {
                    $percentage = $totalTicketsCount > 0 ? round(($cat->count / $totalTicketsCount) * 100) : 0;
                    $palette = ['bg-indigo-500', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];
                    return [
                        'name' => $cat->name,
                        'division_name' => 'Kategori Tugas',
                        'count' => (int) $cat->count,
                        'percentage' => $percentage,
                        'color' => $palette[$index % count($palette)],
                    ];
                });

                $recentTickets = (clone $baseQuery)
                    ->select([
                        'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                        'validated_at', 'validated_by', 'responded_at', 'resolved_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,supporting_unit_id',
                        'category.supportingUnit:id,name,type',
                    ])
                    ->latest()
                    ->take(4)
                    ->get();

                return [
                    'role' => 'TECHNICIAN',
                    'stat1' => ['label' => 'Total Tugas Saya', 'value' => $totalTicketsCount, 'type' => 'total'],
                    'stat2' => ['label' => 'Dalam Pengerjaan', 'value' => $inProgressCount, 'type' => 'progress'],
                    'stat3' => ['label' => 'Selesai Dikerjakan', 'value' => $completedCount, 'type' => 'completed'],
                    'stat4' => ['label' => 'Ditangguhkan', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                    'recentTickets' => $recentTickets,
                    'breakdownData' => $breakdownData,
                    'analytics' => \App\Services\DashboardAnalyticsService::getAnalytics($baseQuery),
                ];
            }

            // 3. KEPALA INSTALASI / DISPOSISI UNIT PENUNJANG
            if (in_array($roleId, [\App\Models\Role::KEPALA_INSTALASI, \App\Models\Role::SEKRETARIS_INSTALASI]) && $user->supporting_unit_id) {
                $unitId = $user->supporting_unit_id;
                $baseQuery->whereHas('category', function ($q) use ($unitId) {
                    $q->where('supporting_unit_id', $unitId);
                });

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
                    ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                    ->select('issue_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('issue_categories.id', 'issue_categories.name')
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
                        'validated_at', 'validated_by', 'responded_at', 'resolved_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,supporting_unit_id',
                        'category.supportingUnit:id,name,type',
                    ])
                    ->latest()
                    ->take(4)
                    ->get();

                $unitName = $user->supportingUnit->name ?? 'Unit';

                return [
                    'role' => 'UNIT_HEAD',
                    'unitName' => $unitName,
                    'stat1' => ['label' => "Total Laporan {$unitName}", 'value' => $totalTicketsCount, 'type' => 'total'],
                    'stat2' => ['label' => 'Dalam Pengerjaan', 'value' => $inProgressCount, 'type' => 'progress'],
                    'stat3' => ['label' => 'Selesai Dikerjakan', 'value' => $completedCount, 'type' => 'completed'],
                    'stat4' => ['label' => 'Menunggu Disposisi', 'value' => $pendingTicketsCount, 'type' => 'pending'],
                    'recentTickets' => $recentTickets,
                    'breakdownData' => $breakdownData,
                    'analytics' => \App\Services\DashboardAnalyticsService::getAnalytics($baseQuery),
                ];
            }

            // 4. PJ RUANGAN
            if ($roleId === \App\Models\Role::PJ_RUANGAN && $user->room_id) {
                $baseQuery->where('room_id', $user->room_id);

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
                    ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                    ->select('issue_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                    ->groupBy('issue_categories.id', 'issue_categories.name')
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
                        'validated_at', 'validated_by', 'responded_at', 'resolved_at',
                        \Illuminate\Support\Facades\DB::raw(
                            \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                                ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                                : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                        )
                    ])
                    ->with([
                        'reporter:id,name',
                        'room:id,name',
                        'category:id,name,supporting_unit_id',
                        'category.supportingUnit:id,name,type',
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
                    'analytics' => \App\Services\DashboardAnalyticsService::getAnalytics($baseQuery),
                ];
            }

            // 4. GLOBAL (ADMINISTRATOR, DIRECTOR, DIVISION_HEAD, SECTION_HEAD)
            $ticketCounts = \Illuminate\Support\Facades\DB::table('service_tickets')
                ->whereNull('service_tickets.deleted_at')
                ->selectRaw("
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'PENDING_VALIDATION' THEN 1 ELSE 0 END) as pending_count
                ")
                ->first();

            $totalTicketsCount = (int) ($ticketCounts->total_count ?? 0);
            $pendingTicketsCount = (int) ($ticketCounts->pending_count ?? 0);

            $unitCounts = \Illuminate\Support\Facades\DB::table('service_tickets')
                ->whereNull('service_tickets.deleted_at')
                ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                ->join('supporting_units', 'issue_categories.supporting_unit_id', '=', 'supporting_units.id')
                ->select('supporting_units.id as unit_id', 'supporting_units.type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->groupBy('supporting_units.id', 'supporting_units.type')
                ->get();

            $medikTicketsCount = (int) $unitCounts->where('type', 'MEDIK')->sum('count');
            $nonMedikTicketsCount = (int) $unitCounts->where('type', 'NON_MEDIK')->sum('count');

            $categoryCounts = \Illuminate\Support\Facades\DB::table('service_tickets')
                ->whereNull('service_tickets.deleted_at')
                ->join('issue_categories', 'service_tickets.category_id', '=', 'issue_categories.id')
                ->select('issue_categories.name as name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->groupBy('issue_categories.id', 'issue_categories.name')
                ->orderByDesc('count')
                ->take(5)
                ->get();

            $breakdownData = $categoryCounts->map(function ($cat, $index) use ($totalTicketsCount) {
                $percentage = $totalTicketsCount > 0 ? round(($cat->count / $totalTicketsCount) * 100) : 0;
                $palette = ['bg-indigo-500', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];
                return [
                    'name' => $cat->name,
                    'division_name' => 'Kategori Laporan',
                    'count' => (int) $cat->count,
                    'percentage' => $percentage,
                    'color' => $palette[$index % count($palette)],
                ];
            });

            $recentTickets = \App\Models\ServiceTicket::select([
                'id', 'uuid', 'ticket_number', 'reporter_id', 'room_id', 'category_id', 'status', 'created_at',
                'validated_at', 'validated_by', 'responded_at', 'resolved_at',
                \Illuminate\Support\Facades\DB::raw(
                    \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' 
                        ? 'SUBSTR(problem_description, 1, 100) as problem_description' 
                        : 'SUBSTRING(problem_description, 1, 100) as problem_description'
                )
            ])
            ->with([
                'reporter:id,name',
                'room:id,name',
                'category:id,name,supporting_unit_id',
                'category.supportingUnit:id,name,type',
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
                'analytics' => \App\Services\DashboardAnalyticsService::getAnalytics(\App\Models\ServiceTicket::query()->whereNull('service_tickets.deleted_at')),
            ];
        }),
    ]);
})->middleware(['auth', 'verified', 'page.access'])->name('dashboard');

Route::middleware(['auth', 'verified', 'page.access'])->group(function () {
    Route::get('/services', function () {
        return Inertia::render('Service/Index', [
            'initialSection' => null,
            'units' => \App\Models\SupportingUnit::all(),
        ]);
    })->name('services.index');

    Route::get('/services/medik', function () {
        return redirect()->route('services.index');
    })->name('services.medik');

    Route::get('/services/non-medik', function () {
        return redirect()->route('services.index');
    })->name('services.non-medik');

    Route::post('/services/tickets', [\App\Http\Controllers\ServiceController::class, 'storeTicket'])->name('services.tickets.store');

    // Sub-Portal PAN-SINAR (Kesehatan Lingkungan)
    Route::prefix('services/kesling')->name('pansinar.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PanSinarController::class, 'index'])->name('index');
        Route::get('/sanitasi', [\App\Http\Controllers\PanSinarController::class, 'sanitasi'])->name('tickets.index');
        Route::get('/ceklis', [\App\Http\Controllers\PanSinarController::class, 'ceklis'])->name('inspections.index');
        // Pengelolaan Limbah dinonaktifkan sementara (file controller & vue tetap tersimpan)
        Route::get('/limbah-b3', function () {
            abort(404);
        })->name('waste.index');
        // Manajemen Toilet
        Route::get('/manajemen-toilet', [\App\Http\Controllers\PanSinarController::class, 'qr'])->name('toilets.index');
        Route::get('/qr-panic', function () {
            return redirect()->route('pansinar.toilets.index');
        })->name('qr.index');

        // Actions
        Route::post('/tickets', [\App\Http\Controllers\PanSinarController::class, 'storeTicket'])->name('tickets.store');
        Route::post('/tickets/{id}/start', [\App\Http\Controllers\PanSinarController::class, 'startTicket'])->name('tickets.start');
        Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\PanSinarController::class, 'resolveTicket'])->name('tickets.resolve');
        Route::delete('/tickets/{id}', [\App\Http\Controllers\PanSinarController::class, 'destroyTicket'])->name('tickets.destroy');
        Route::post('/inspections', [\App\Http\Controllers\PanSinarController::class, 'storeInspection'])->name('inspections.store');
        Route::post('/waste', [\App\Http\Controllers\PanSinarController::class, 'storeWaste'])->name('waste.store');
        Route::post('/toilets', [\App\Http\Controllers\PanSinarController::class, 'storeToilet'])->name('toilets.store');
        Route::post('/toilets/import', [\App\Http\Controllers\PanSinarController::class, 'importToilets'])->name('toilets.import');
        Route::put('/toilets/{id}', [\App\Http\Controllers\PanSinarController::class, 'updateToilet'])->name('toilets.update');
        Route::delete('/toilets/{id}', [\App\Http\Controllers\PanSinarController::class, 'destroyToilet'])->name('toilets.destroy');
    });

    // Backward compatibility redirects for legacy routes
    Route::redirect('/penunjang-non-medik/kesling', '/services/kesling');
    Route::redirect('/penunjang-non-medik/kesling/{any}', '/services/kesling/{any}')->where('any', '.*');
    Route::redirect('/services/units/{supportingUnit}', '/services/{supportingUnit}');

    // Live Technician Position & Duty Status
    Route::get('/technicians/position', [\App\Http\Controllers\TechnicianPositionController::class, 'index'])->name('technicians.position');
    Route::get('/technicians/radar', [\App\Http\Controllers\TechnicianPositionController::class, 'index'])->name('technicians.radar');
    Route::post('/technicians/status', [\App\Http\Controllers\TechnicianPositionController::class, 'updateStatus'])->name('technicians.update-status');
    Route::post('/technicians/working-hours', [\App\Http\Controllers\TechnicianPositionController::class, 'updateWorkingHours'])->name('technicians.update-working-hours');

    // Ticket Workflows & Actions
    Route::get('/tickets/{ticket:uuid}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show')->withTrashed();
    Route::post('/tickets/{ticket:uuid}/assign', [\App\Http\Controllers\TicketController::class, 'assign'])->name('tickets.assign')->withTrashed();
    Route::post('/tickets/{ticket:uuid}/respond', [\App\Http\Controllers\TicketController::class, 'respond'])->name('tickets.respond')->withTrashed();
    Route::post('/tickets/{ticket:uuid}/resolve', [\App\Http\Controllers\TicketController::class, 'resolve'])->name('tickets.resolve')->withTrashed();
    Route::post('/tickets/{ticket:uuid}/resume', [\App\Http\Controllers\TicketController::class, 'resume'])->name('tickets.resume')->withTrashed();
    Route::delete('/tickets/{ticket:uuid}', [\App\Http\Controllers\TicketController::class, 'destroy'])->name('tickets.destroy')->withTrashed();
});

Route::middleware(['auth', 'verified', 'page.access'])->group(function () {
    // User Management
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/approvals', [UserManagementController::class, 'indexApprovals'])->name('users.approvals');
    Route::get('/users/approvals/{user:uuid}', [UserManagementController::class, 'showApprovalDetail'])->name('users.approvals.show');
    Route::patch('/users/{user:uuid}/approve', [UserManagementController::class, 'approveUser'])->name('users.approve');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user:uuid}', [UserManagementController::class, 'show'])->name('users.show');
    Route::get('/users/{user:uuid}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user:uuid}', [UserManagementController::class, 'update'])->name('users.update');
    Route::patch('/users/{user:uuid}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user:uuid}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user:uuid}/permissions', [UserManagementController::class, 'updatePermissions'])->name('users.update-permissions');

    // WhatsApp Gateway Management
    Route::get('/wa-gateway', [WaGatewayController::class, 'index'])->name('admin.wa-gateway.index');
    Route::get('/wa-gateway/status', [WaGatewayController::class, 'status'])->name('admin.wa-gateway.status');
    Route::post('/wa-gateway/logout', [WaGatewayController::class, 'logout'])->name('admin.wa-gateway.logout');
    Route::post('/wa-gateway/test', [WaGatewayController::class, 'sendTest'])->name('admin.wa-gateway.test');

    // QR Code Generator
    Route::get('/qr-generator', [QrGeneratorController::class, 'index'])->name('admin.qr-code.index');

    // Layanan Manajemen
    Route::get('/service-management', function () {
        return redirect()->route('service-management.rooms');
    })->name('service-management.index');
    Route::get('/services/rooms', [ServiceManagementController::class, 'indexRooms'])->name('service-management.rooms');
    Route::get('/services/categories', [ServiceManagementController::class, 'indexCategories'])->name('service-management.categories');
    Route::get('/services/supporting-units', [ServiceManagementController::class, 'indexSupportingUnits'])->name('service-management.supporting-units');
    Route::get('/services/working-hours', [\App\Http\Controllers\TechnicianPositionController::class, 'indexWorkingHours'])->name('service-management.working-hours');
    
    // Dynamic supporting unit operational portal (e.g. /services/ipsrs)
    Route::get('/services/{supportingUnit}', [\App\Http\Controllers\ServiceController::class, 'showUnit'])->name('services.units.show');
    
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
    Route::delete('/reports-management/{ticket:uuid}', [\App\Http\Controllers\ReportManagementController::class, 'destroy'])->name('reports-management.destroy');

    Route::get('/reports-audit', [\App\Http\Controllers\ReportAuditController::class, 'index'])->name('reports-audit.index');
    Route::post('/reports-audit/filters', [\App\Http\Controllers\ReportAuditController::class, 'storeFilters'])->name('reports-audit.filters');
    Route::get('/reports-audit/{uuid}', [\App\Http\Controllers\ReportAuditController::class, 'show'])->name('reports-audit.show');
    Route::put('/reports-audit/{uuid}', [\App\Http\Controllers\ReportAuditController::class, 'update'])->name('reports-audit.update');
    Route::delete('/reports-audit/{uuid}', [\App\Http\Controllers\ReportAuditController::class, 'destroy'])->name('reports-audit.destroy');
    Route::post('/reports-audit/{uuid}/restore', [\App\Http\Controllers\ReportAuditController::class, 'restore'])->name('reports-audit.restore');
    Route::post('/reports-audit/{uuid}/attachments', [\App\Http\Controllers\ReportAuditController::class, 'uploadAttachment'])->name('reports-audit.upload-attachment');
    Route::post('/reports-audit/{uuid}/attachments/{attachmentId}/replace', [\App\Http\Controllers\ReportAuditController::class, 'replaceAttachment'])->name('reports-audit.replace-attachment');
    Route::delete('/reports-audit/{uuid}/attachments/{attachmentId}', [\App\Http\Controllers\ReportAuditController::class, 'deleteAttachment'])->name('reports-audit.delete-attachment');
    Route::post('/reports-audit/{uuid}/slots/{slotType}', [\App\Http\Controllers\ReportAuditController::class, 'updateSlotAttachment'])->name('reports-audit.update-slot');
    Route::delete('/reports-audit/{uuid}/slots/{slotType}', [\App\Http\Controllers\ReportAuditController::class, 'deleteSlotAttachment'])->name('reports-audit.delete-slot');

    Route::get('/settings', function () {
        return Inertia::render('UserSettings/Index');
    })->name('settings.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.update-notifications');
    Route::patch('/profile/face', [ProfileController::class, 'updateFace'])->name('profile.update-face');
    Route::delete('/profile/face', [ProfileController::class, 'deleteFace'])->name('profile.delete-face');

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
})->middleware('throttle:5,1')->name('login.face');

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
