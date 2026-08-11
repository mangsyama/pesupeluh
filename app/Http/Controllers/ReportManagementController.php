<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportManagementController extends Controller
{
    const FILTER_SESSION_KEY = 'report_management_filters';

    /**
     * Halaman index / riwayat tugas kerja operasional untuk role yang bersangkutan.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $userId = (int) $user->id;

        if ($request->filled('search') || $request->filled('status')) {
            $request->session()->put(self::FILTER_SESSION_KEY, [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
            ]);

            return redirect()->route('reports-management.index');
        }

        $sessionFilters = $request->session()->get(self::FILTER_SESSION_KEY, []);
        $filters = [
            'search' => $request->input('search', $sessionFilters['search'] ?? ''),
            'status' => $request->input('status', $sessionFilters['status'] ?? ''),
        ];

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
        ->whereNull('deleted_at');

        // Scoping data berdasarkan peran/role:
        if ($user->isAdmin() || $user->isDirector() || (int) $user->role_id === Role::KEPALA_BIDANG) {
            // Admin & Direktur & Kabid: melihat semua
        } elseif ($user->isTechnician()) {
            // Teknisi: HANYA melihat tiket yang ditugaskan kepada mereka
            $query->whereHas('assignments', function ($q) use ($userId) {
                $q->where('technician_id', $userId);
            });
        } elseif ((int) $user->role_id === Role::PJ_RUANGAN && $user->room_id) {
            // PJ Ruangan: melihat tiket dari ruangan mereka
            $query->where('room_id', $user->room_id);
        } elseif ($user->canDisposisi() && $user->supporting_unit_id) {
            // Kepala Instalasi / Disposisi Unit Penunjang: melihat tiket unit penunjang mereka
            $query->whereHas('category', function ($q) use ($user) {
                $q->where('supporting_unit_id', $user->supporting_unit_id);
            });
        } elseif ($user->canDisposisi()) {
            // Role disposisi lainnya tanpa pembatasan unit: melihat semua
        } else {
            // Role lainnya: memfilter unit jika ada
            if ($user->supporting_unit_id) {
                $query->whereHas('category', function ($q) use ($user) {
                    $q->where('supporting_unit_id', $user->supporting_unit_id);
                });
            }
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
            'tickets'  => Inertia::defer(fn() => $query->paginate(10)),
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
        /** @var \App\Models\User $user */
        $user = $request->user();
        $roleId = (int) $user->role_id;
        $supportingUnitId = (int) ($ticket->category?->supporting_unit_id ?? 0);

        // Otorisasi akses detail operasional
        if ($user->isAdmin() || $user->isDirector() || (int) $user->role_id === Role::KEPALA_BIDANG) {
            // Admin, Direktur, Kabid: bebas pantau
        } elseif ((int) $user->role_id === Role::PJ_RUANGAN) {
            // PJ Ruangan: KHUSUS melihat & mendisposisikan laporan dari ruangan sendiri
            if ((int) $ticket->room_id !== (int) $user->room_id) {
                abort(403, 'Akses ditolak. Sebagai Penanggung Jawab Ruangan, Anda hanya berwenang mengelola laporan dari ruangan Anda sendiri.');
            }
        } elseif ($user->canDisposisi() && (int)$user->supporting_unit_id === $supportingUnitId) {
            // Kepala Instalasi / Disposisi unit penunjang terkait
        } elseif ($user->isTechnician() && $ticket->assignments()->where('technician_id', $user->id)->exists()) {
            // Teknisi yang ditugaskan
        } elseif ($user->canDisposisi()) {
            // Disposisi role lainnya
        } else {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('ReportManagement/Show', [
            'ticket' => Inertia::defer(fn() => $ticket->load([
                'reporter:id,name,nip,phone_number',
                'validator:id,name,nip',
                'room:id,name,building_name,location_floor',
                'category.supportingUnit',
                'assignments.technician:id,name,nip',
                'attachments.user:id,name',
                'histories.user:id,name',
            ])),
            'technicians' => Inertia::defer(function() use ($user) {
                /** @var \App\Models\User $user */
                if ($user->canDisposisi() || $user->isAdmin()) {
                    return User::where('role_id', Role::TEKNISI)
                        ->where('is_active', 1)
                        ->with('supportingUnit:id,name')
                        ->orderBy('name')
                        ->get(['id', 'name', 'nip', 'supporting_unit_id']);
                }
                return [];
            }),
            'categories' => Inertia::defer(function() use ($user) {
                /** @var \App\Models\User $user */
                if ($user->canDisposisi() || $user->isAdmin()) {
                    return \App\Models\IssueCategory::with('supportingUnit:id,name')
                        ->orderBy('name')
                        ->get(['id', 'name', 'supporting_unit_id']);
                }
                return [];
            }),
            'personal' => false,
        ]);
    }

    /**
     * Soft delete laporan (Khusus Administrator).
     */
    public function destroy(Request $request, ServiceTicket $ticket)
    {
        $user = $request->user();
        if ((int) $user->role_id !== 1) {
            abort(403, 'Hanya Administrator yang memiliki wewenang untuk menghapus laporan.');
        }

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'status'    => $ticket->status,
            'action'    => 'DELETED',
            'notes'     => 'Laporan #' . $ticket->ticket_number . ' telah di-soft delete oleh Administrator (' . $user->name . ').',
        ]);

        $ticket->delete();

        return redirect()->route('reports-management.index')->with('success', 'Laporan #' . $ticket->ticket_number . ' berhasil dihapus (Soft Delete).');
    }
}
