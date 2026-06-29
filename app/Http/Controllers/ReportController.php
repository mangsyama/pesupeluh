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
    /**
     * Report Center – statistik ringkasan + halaman utama.
     */
    public function index()
    {
        $now = now();

        // Statistik bulan berjalan
        $totalMonth = ServiceTicket::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        $completedMonth = ServiceTicket::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->where('status', 'COMPLETED')
            ->count();

        $pendingMonth = ServiceTicket::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->whereIn('status', ['PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING'])
            ->count();

        return Inertia::render('Report/Index', [
            'stats' => [
                'total_month'    => $totalMonth,
                'completed'      => $completedMonth,
                'pending'        => $pendingMonth,
            ],
        ]);
    }

    /**
     * Riwayat laporan – daftar tiket dari database dengan filter & search.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $roleId = (int) $user->role_id;
        $userId = (int) $user->id;

        $query = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name',
            'category:id,name,feature_id',
            'category.unitFeature:id,name,supporting_unit_id',
            'category.unitFeature.supportingUnit:id,name',
        ])
        ->whereNull('deleted_at');

        // Scoping based on role:
        if ($roleId === 1) {
            // Administrator: all tickets
        } elseif (in_array($roleId, [2, 3, 4])) {
            // Director, Division Head, Section Head: all tickets
        } elseif ($roleId === 5) {
            // Unit Head: tickets in their supporting unit
            $query->whereHas('category.unitFeature', function ($q) use ($user) {
                $q->where('supporting_unit_id', $user->supporting_unit_id);
            });
        } elseif ($roleId === 6) {
            // Technician: tickets assigned to them
            $query->whereHas('assignments', function ($q) use ($userId) {
                $q->where('technician_id', $userId);
            });
        } elseif ($roleId === 7) {
            // Room Head: tickets in their room
            $query->where('room_id', $user->room_id);
        } elseif ($roleId === 8) {
            // Reporter: only their own tickets
            $query->where('reporter_id', $userId);
        }

        $query->orderByDesc('created_at');

        // Filter pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('problem_description', 'like', "%{$search}%")
                  ->orWhereHas('reporter', fn($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return Inertia::render('Report/History', [
            'tickets'  => Inertia::defer(fn() => $query->paginate(15)->withQueryString()),
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Export PDF – data riil dari database.
     */
    public function exportPdf(Request $request)
    {
        $tickets = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name',
            'category:id,name',
        ])
        ->whereNull('deleted_at')
        ->orderByDesc('created_at')
        ->get();

        $pdf = Pdf::loadView('exports.tickets_pdf', [
            'tickets'    => $tickets,
            'exportedAt' => now()->format('d F Y H:i'),
        ]);

        return $pdf->download('laporan_tiket_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export CSV – data riil dari database.
     */
    public function exportCsv()
    {
        return Excel::download(
            new TicketsExport(),
            'laporan_tiket_' . now()->format('Ymd_His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
