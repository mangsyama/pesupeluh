<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketsExport;

class ReportController extends Controller
{
    private const FILTER_SESSION_KEY = 'reports.history.filters';

    /**
     * Report Center – statistik ringkasan + halaman utama.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Default filters untuk form di frontend
        $filters = [
            'start_date'  => $request->input('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date'    => $request->input('end_date', now()->format('Y-m-d')),
            'unit_id'     => $request->input('unit_id', ''),
            'category_id' => $request->input('category_id', ''),
            'room_id'     => $request->input('room_id', ''),
            'reporter_id' => $request->input('reporter_id', ''),
        ];

        // Ambil data master untuk dropdown
        $supportingUnits = \App\Models\SupportingUnit::with('issueCategories')->get();
        $rooms = \App\Models\Room::select(['id', 'name', 'building_name', 'location_floor'])->orderBy('name')->get();
        $reporters = \App\Models\User::select(['id', 'name', 'room_id'])->orderBy('name')->get();

        return Inertia::render('ReportExport/Index', [
            'filters'         => $filters,
            'supportingUnits' => $supportingUnits,
            'rooms'           => $rooms,
            'reporters'       => $reporters,
            'tickets'         => Inertia::defer(function () use ($user, $request) {
                $query = ServiceTicket::with([
                    'reporter:id,name',
                    'room:id,name,building_name,location_floor',
                    'category:id,name,supporting_unit_id',
                    'category.supportingUnit:id,name',
                ])
                ->whereNull('deleted_at');

                $this->applyFilters($query, $request, $user);

                return $query->orderByDesc('created_at')->paginate(10)->withQueryString();
            }),
            'stats'           => Inertia::defer(function () use ($user, $request) {
                $query = ServiceTicket::whereNull('deleted_at');
                $this->applyFilters($query, $request, $user, true);

                return [
                    'total_month' => (clone $query)->count(),
                    'completed'   => (clone $query)->where('status', 'COMPLETED')->count(),
                    'pending'     => (clone $query)->whereIn('status', ['PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING'])->count(),
                ];
            }),
        ]);
    }

    /**
     * Riwayat laporan pribadi (Laporan Saya) – daftar tiket yang diajukan oleh user bersangkutan.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;

        if ($request->filled('search') || $request->filled('status')) {
            $request->session()->put(self::FILTER_SESSION_KEY, [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
            ]);

            return redirect()->route('reports.history');
        }

        $filters = $request->session()->get(self::FILTER_SESSION_KEY, [
            'search' => '',
            'status' => '',
        ]);

        $query = ServiceTicket::select([
            'id', 
            'uuid', 
            'ticket_number', 
            'reporter_id', 
            'room_id', 
            'category_id', 
            'status', 
            'priority',
            'problem_description',
            'created_at',
        ])
        ->with([
            'reporter:id,name,phone_number',
            'room:id,name,building_name,location_floor',
            'category:id,name,supporting_unit_id',
            'category.supportingUnit:id,name',
        ])
        ->whereNull('deleted_at')
        ->where('reporter_id', $userId);

        $query->orderByDesc('created_at');

        // Filter pencarian
        if ($search = $filters['search']) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('problem_description', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $filters['status']) {
            if (str_contains($status, ',')) {
                $query->whereIn('status', explode(',', $status));
            } else {
                $query->where('status', $status);
            }
        }

        return Inertia::render('Report/Index', [
            'tickets'  => Inertia::defer(fn() => $query->paginate(10)),
            'filters'  => $filters,
            'personal' => true,
        ]);
    }

    public function storeFilters(Request $request)
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $request->session()->put(self::FILTER_SESSION_KEY, $filters);

        return redirect()->route('reports.history');
    }

    /**
     * Detail laporan pribadi (Laporan Saya) – read-only tracker.
     */
    public function show(Request $request, ServiceTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        
        // Authorization check: only let the reporter, PJ Ruangan of the ticket's room, or admins/disposisi roles view it
        if ($ticket->reporter_id !== $user->id) {
            if ((int) $user->role_id === Role::PJ_RUANGAN) {
                if ((int) $ticket->room_id !== (int) $user->room_id) {
                    abort(403, 'Akses ditolak. Sebagai Penanggung Jawab Ruangan, Anda hanya dapat melihat laporan dari ruangan Anda.');
                }
            } elseif ($user->isAdmin() || $user->isDirector() || $user->canDisposisi()) {
                // Admin, disposisi roles, directors can view
            } else {
                abort(403, 'Unauthorized action.');
            }
        }

        // Automatically mark unread notifications for this ticket as read for current user
        if ($user instanceof User) {
            $user->unreadNotifications()
                ->where(function ($query) use ($ticket) {
                    $query->where('data->ticket_id', $ticket->id)
                          ->orWhere('data->route', 'like', "%{$ticket->uuid}%");
                })
                ->get()
                ->each(fn ($notification) => $notification->markAsRead());
        }

        return Inertia::render('Report/Show', [
            'ticket' => Inertia::defer(fn() => $ticket->load([
                'reporter:id,name,nip,phone_number',
                'validator:id,name,nip',
                'room:id,name,building_name,location_floor',
                'category.supportingUnit',
                'assignments.technician:id,name,nip',
                'attachments.user:id,name',
                'histories.user:id,name',
            ])),
            'personal' => true,
        ]);
    }

    /**
     * Export PDF – data terfilter sesuai otorisasi peran user.
     */
    public function exportPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', 300);

        $user = $request->user();
        
        $query = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name,building_name,location_floor',
            'category:id,name,supporting_unit_id',
            'category.supportingUnit:id,name',
            'assignments.technician:id,name',
        ])
        ->whereNull('deleted_at');

        $this->applyFilters($query, $request, $user);

        $tickets = $query->orderByDesc('created_at')->get();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if (!$startDate && !$endDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $unitName = null;
        if ($request->filled('unit_id')) {
            $unitName = \App\Models\SupportingUnit::find($request->input('unit_id'))?->name;
        }
        $categoryName = null;
        if ($request->filled('category_id')) {
            $categoryName = \App\Models\IssueCategory::find($request->input('category_id'))?->name;
        }
        $roomName = null;
        if ($request->filled('room_id')) {
            $roomName = \App\Models\Room::find($request->input('room_id'))?->name;
        }
        $reporterName = null;
        if ($request->filled('reporter_id')) {
            $reporterName = \App\Models\User::find($request->input('reporter_id'))?->name;
        }

        $logoPath = public_path('images/logo-sidebar.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        if (!file_exists(storage_path('fonts'))) {
            mkdir(storage_path('fonts'), 0755, true);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'tempDir' => sys_get_temp_dir(),
            'chroot' => [public_path(), storage_path()],
        ], true);

        $pdf->loadView('exports.tickets_pdf', [
            'tickets'      => $tickets,
            'exportedAt'   => now()->format('d F Y H:i'),
            'startDate'    => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate'      => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
            'unitName'     => $unitName,
            'categoryName' => $categoryName,
            'roomName'     => $roomName,
            'reporterName' => $reporterName,
            'logoBase64'   => $logoBase64,
            'logoPath'     => $logoPath,
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download('laporan_tiket_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export CSV – data terfilter sesuai otorisasi peran user.
     */
    public function exportCsv(Request $request)
    {
        return Excel::download(
            new TicketsExport($request->user(), $request->all()),
            'laporan_tiket_' . now()->format('Ymd_His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Helper privat untuk menerapkan filter pencarian dan otorisasi.
     */
    private function applyFilters($query, Request $request, $user, $ignoreStatus = false)
    {
        /** @var \App\Models\User $user */
        $userId = (int) $user->id;

        // Scoping data berdasarkan peran
        if ($user->isReportOnly()) {
            $query->where('reporter_id', $userId);
        } elseif ($user->isTechnician()) {
            $query->whereHas('assignments', function ($q) use ($userId) {
                $q->where('technician_id', $userId);
            });
        } elseif ($user->canDisposisi() && $user->supporting_unit_id) {
            $unitId = $user->supporting_unit_id;
            $query->whereHas('category', function ($q) use ($unitId) {
                $q->where('supporting_unit_id', $unitId);
            });
        } elseif ((int) $user->role_id === Role::PJ_RUANGAN && $user->room_id) {
            $query->where('room_id', $user->room_id);
        }

        // Filter unit penunjang (supporting_unit_id)
        if ($request->filled('unit_id')) {
            $unitId = $request->input('unit_id');
            $query->whereHas('category', function ($q) use ($unitId) {
                $q->where('supporting_unit_id', $unitId);
            });
        }

        // Filter kategori kerusakan (category_id)
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter ruangan (room_id)
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        // Filter staf/pelapor (reporter_id)
        if ($request->filled('reporter_id')) {
            $query->where('reporter_id', $request->input('reporter_id'));
        }

        // Filter range tanggal
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate && !$endDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }
}
