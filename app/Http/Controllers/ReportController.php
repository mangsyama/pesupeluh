<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
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
        $rooms = \App\Models\Room::select(['id', 'name', 'location_floor'])->orderBy('name')->get();
        $reporters = \App\Models\User::select(['id', 'name', 'room_id'])->orderBy('name')->get();

        return Inertia::render('ReportExport/Index', [
            'filters'         => $filters,
            'supportingUnits' => $supportingUnits,
            'rooms'           => $rooms,
            'reporters'       => $reporters,
            'tickets'         => Inertia::defer(function () use ($user, $request) {
                $query = ServiceTicket::with([
                    'reporter:id,name',
                    'room:id,name',
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
            'room:id,name',
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
            'tickets'  => Inertia::defer(fn() => $query->paginate(15)),
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
        $user = $request->user();
        
        // Authorization check: only let the reporter, room head of the ticket's room, or admins/unit heads view it
        if ($ticket->reporter_id !== $user->id) {
            if ($user->role_id === 7 && $ticket->room_id === $user->room_id) {
                // Room head of the ticket's room
            } elseif (in_array((int)$user->role_id, [1, 2, 3, 4, 5])) {
                // Admin, unit heads, directors can view
            } else {
                abort(403, 'Unauthorized action.');
            }
        }

        return Inertia::render('Report/Show', [
            'ticket' => Inertia::defer(fn() => $ticket->load([
                'reporter:id,name,nip,phone_number',
                'validator:id,name,nip',
                'room:id,name,location_floor',
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
        $user = $request->user();
        
        $query = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name',
            'category:id,name,supporting_unit_id',
            'category.supportingUnit:id,name',
            'assignments.technician:id,name',
            'attachments',
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

        $pdf = Pdf::loadView('exports.tickets_pdf', [
            'tickets'      => $tickets,
            'exportedAt'   => now()->format('d F Y H:i'),
            'startDate'    => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate'      => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
            'unitName'     => $unitName,
            'categoryName' => $categoryName,
            'roomName'     => $roomName,
            'reporterName' => $reporterName,
            'logoPath'     => public_path('images/logo-sidebar.png'),
        ])->setPaper('a4', 'landscape');

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
        $roleId = (int) ($user->role_id ?? 8);
        $userId = (int) $user->id;

        // Scoping data berdasarkan peran
        if ($roleId === 8 || ($user->role && $user->role->name === 'REPORTER')) {
            $query->where('reporter_id', $userId);
        } elseif (in_array($roleId, [5, 6]) && $user->supporting_unit_id) {
            $unitId = $user->supporting_unit_id;
            $query->whereHas('category', function ($q) use ($unitId) {
                $q->where('supporting_unit_id', $unitId);
            });
        } elseif ($roleId === 7 && $user->room_id) {
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
