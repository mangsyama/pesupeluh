<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportManagementController extends Controller
{
    private const FILTER_SESSION_KEY = 'reports-management.filters';

    /**
     * Halaman index / riwayat tugas kerja operasional untuk role yang bersangkutan.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleId = (int) $user->role_id;
        $userId = (int) $user->id;

        if ($request->filled('search') || $request->filled('status')) {
            $request->session()->put(self::FILTER_SESSION_KEY, [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
            ]);

            return redirect()->route('reports-management.index');
        }

        // Validasi akses operasional: hanya Admin, Management, Kepala Unit, Teknisi, dan Kepala Ruangan
        if (!in_array($roleId, [1, 2, 3, 4, 5, 6, 7])) {
            abort(403, 'Unauthorized action.');
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
            'category:id,name,feature_id',
            'category.unitFeature:id,name,supporting_unit_id',
            'category.unitFeature.supportingUnit:id,name',
        ])
        ->whereNull('deleted_at');

        // Scoping data berdasarkan peran/role:
        if ($roleId === 1) {
            // Admin: melihat semua
        } elseif (in_array($roleId, [2, 3, 4])) {
            // Direktur / Manajemen: melihat semua
        } elseif ($roleId === 5) {
            // Kepala Unit (IPRS/dll): melihat tiket yang berada di dalam unit penunjang mereka
            $query->whereHas('category.unitFeature', function ($q) use ($user) {
                $q->where('supporting_unit_id', $user->supporting_unit_id);
            });
        } elseif ($roleId === 6) {
            // Teknisi: melihat tiket yang ditugaskan kepada mereka
            $query->whereHas('assignments', function ($q) use ($userId) {
                $q->where('technician_id', $userId);
            });
        } elseif ($roleId === 7) {
            // Kepala Ruangan: melihat tiket yang dilaporkan dari ruangan mereka
            $query->where('room_id', $user->room_id);
        }

        $query->orderByDesc('created_at');

        // Filter pencarian
        if ($search = $filters['search']) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('problem_description', 'like', "%{$search}%")
                  ->orWhereHas('reporter', fn($r) => $r->where('name', 'like', "%{$search}%"));
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

        return Inertia::render('ReportManagement/Index', [
            'tickets'  => Inertia::defer(fn() => $query->paginate(15)),
            'filters'  => $filters,
        ]);
    }

    public function storeFilters(Request $request)
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $request->session()->put(self::FILTER_SESSION_KEY, $filters);

        return redirect()->route('reports-management.index');
    }

    /**
     * Halaman detail operasional untuk memproses disposisi / eksekusi tiket.
     */
    public function show(Request $request, ServiceTicket $ticket)
    {
        $user = $request->user();
        $roleId = (int) $user->role_id;
        $supportingUnitId = (int) $ticket->category->unitFeature->supporting_unit_id;

        // Otorisasi akses detail operasional
        if ($roleId === 1) {
            // Admin: bebas akses
        } elseif (in_array($roleId, [2, 3, 4])) {
            // Direktur/Manajemen: bebas pantau
        } elseif ($roleId === 5 && (int)$user->supporting_unit_id === $supportingUnitId) {
            // Kepala unit penunjang terkait
        } elseif ($roleId === 6 && $ticket->assignments()->where('technician_id', $user->id)->exists()) {
            // Teknisi yang ditugaskan
        } elseif ($roleId === 7 && $ticket->room_id === $user->room_id) {
            // Kepala ruangan tempat kejadian
        } else {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('ReportManagement/Show', [
            'ticket' => Inertia::defer(fn() => $ticket->load([
                'reporter:id,name,nip',
                'validator:id,name,nip',
                'room:id,name,location_floor',
                'category.unitFeature.supportingUnit.division',
                'assignments.technician:id,name,nip',
                'attachments.user:id,name',
            ])),
            'technicians' => Inertia::defer(function() use ($roleId, $user, $supportingUnitId) {
                if (($roleId === 5 && (int) $user->supporting_unit_id === $supportingUnitId) || $roleId === 1) {
                    return User::where('role_id', 6) // TECHNICIAN
                        ->where('is_active', 1)
                        ->with('supportingUnit:id,name')
                        ->orderBy('name')
                        ->get(['id', 'name', 'nip', 'supporting_unit_id']);
                }
                return [];
            }),
            'personal' => false,
        ]);
    }
}
