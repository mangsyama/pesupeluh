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
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        return Inertia::render('Report/Index', [
            'stats' => [
                'total_month'    => ServiceTicket::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count(),
                'completed'      => ServiceTicket::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->where('status', 'COMPLETED')
                    ->count(),
                'pending'        => ServiceTicket::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->whereIn('status', ['PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING'])
                    ->count(),
            ],
        ]);
    }

    /**
     * Riwayat laporan pribadi (Laporan Saya) – daftar tiket yang diajukan oleh user bersangkutan.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;

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
            'reporter:id,name',
            'room:id,name',
            'category:id,name,feature_id',
            'category.unitFeature:id,name,supporting_unit_id',
            'category.unitFeature.supportingUnit:id,name',
        ])
        ->whereNull('deleted_at')
        ->where('reporter_id', $userId);

        $query->orderByDesc('created_at');

        // Filter pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('problem_description', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $request->input('status')) {
            if (str_contains($status, ',')) {
                $query->whereIn('status', explode(',', $status));
            } else {
                $query->where('status', $status);
            }
        }

        return Inertia::render('Report/History', [
            'tickets'  => $query->paginate(15)->withQueryString(),
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Detail laporan pribadi (Laporan Saya) – read-only tracker.
     */
    public function show(Request $request, ServiceTicket $ticket)
    {
        $ticket->load([
            'reporter:id,name,nip',
            'validator:id,name,nip',
            'room:id,name,location_floor',
            'category.unitFeature.supportingUnit.division',
            'assignments.technician:id,name,nip',
            'attachments.user:id,name',
        ]);

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
            'ticket' => $ticket,
            'personal' => true,
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
