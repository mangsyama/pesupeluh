<?php

use App\Http\Controllers\ProfileController;
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

Route::get('/dashboard', function () {
    $totalTicketsCount = \App\Models\ServiceTicket::count();

    $medikTicketsCount = \App\Models\ServiceTicket::whereHas('category.unitFeature.supportingUnit', function ($q) {
        $q->where('division_id', 1);
    })->count();

    $nonMedikTicketsCount = \App\Models\ServiceTicket::whereHas('category.unitFeature.supportingUnit', function ($q) {
        $q->where('division_id', 2);
    })->count();

    $pendingTicketsCount = \App\Models\ServiceTicket::where('status', 'PENDING_VALIDATION')->count();

    $recentTickets = \App\Models\ServiceTicket::with([
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

    $breakdownData = \App\Models\SupportingUnit::with('division')
        ->get()
        ->map(function ($unit) {
            $count = \App\Models\ServiceTicket::whereHas('category.unitFeature', function ($q) use ($unit) {
                $q->where('supporting_unit_id', $unit->id);
            })->count();

            return [
                'name' => $unit->name,
                'division_name' => $unit->division->name,
                'count' => $count,
            ];
        })
        ->sortByDesc('count')
        ->values()
        ->take(5)
        ->map(function ($item) use ($totalTicketsCount) {
            $percentage = $totalTicketsCount > 0 ? round(($item['count'] / $totalTicketsCount) * 100) : 0;
            
            // Map unit name to a color
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
            
            $color = $colors[strtoupper($item['name'])] ?? 'bg-slate-500';
            
            return [
                'name' => $item['name'],
                'division_name' => $item['division_name'],
                'count' => $item['count'],
                'percentage' => $percentage,
                'color' => $color,
            ];
        });

    return Inertia::render('Dashboard/Index', [
        'totalTicketsCount' => $totalTicketsCount,
        'medikTicketsCount' => $medikTicketsCount,
        'nonMedikTicketsCount' => $nonMedikTicketsCount,
        'pendingTicketsCount' => $pendingTicketsCount,
        'recentTickets' => $recentTickets,
        'breakdownData' => $breakdownData,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/services', function () {
        $divisions = \App\Models\Division::with('supportingUnits')->get();
        return Inertia::render('Service/Index', [
            'initialSection' => null,
            'divisions' => $divisions
        ]);
    })->name('services.index');

    Route::get('/services/medik', function () {
        $divisions = \App\Models\Division::with('supportingUnits')->get();
        return Inertia::render('Service/Index', [
            'initialSection' => 'medik',
            'divisions' => $divisions
        ]);
    })->name('services.medik');

    Route::get('/services/non-medik', function () {
        $divisions = \App\Models\Division::with('supportingUnits')->get();
        return Inertia::render('Service/Index', [
            'initialSection' => 'non-medik',
            'divisions' => $divisions
        ]);
    })->name('services.non-medik');

    Route::get('/services/units/{supportingUnit:name}', [\App\Http\Controllers\ServiceController::class, 'showUnit'])->name('services.units.show');
    Route::post('/services/tickets', [\App\Http\Controllers\ServiceController::class, 'storeTicket'])->name('services.tickets.store');

    // Ticket Workflows & Actions
    Route::get('/tickets/{ticket:uuid}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket:uuid}/assign', [\App\Http\Controllers\TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket:uuid}/respond', [\App\Http\Controllers\TicketController::class, 'respond'])->name('tickets.respond');
    Route::post('/tickets/{ticket:uuid}/resolve', [\App\Http\Controllers\TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket:uuid}/resume', [\App\Http\Controllers\TicketController::class, 'resume'])->name('tickets.resume');

    // User Management
    Route::get('/users/approvals', [UserManagementController::class, 'indexApprovals'])->name('users.approvals');
    Route::patch('/users/{user}/approve', [UserManagementController::class, 'approveUser'])->name('users.approve');
    Route::get('/users/admin', [UserManagementController::class, 'indexAdmin'])->name('users.admin');
    Route::get('/users/management', [UserManagementController::class, 'indexManagement'])->name('users.management');
    Route::get('/users/unit-head', [UserManagementController::class, 'indexUnitHead'])->name('users.unit-head');
    Route::get('/users/technician', [UserManagementController::class, 'indexTechnician'])->name('users.technician');
    Route::get('/users/room-head', [UserManagementController::class, 'indexRoomHead'])->name('users.room-head');
    Route::get('/users/reporter', [UserManagementController::class, 'indexReporter'])->name('users.reporter');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    // Manajemen Layanan
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

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/history', [ReportController::class, 'history'])->name('reports.history');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

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
