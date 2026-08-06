<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusUpdatedNotification;
use App\Services\SecureFileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TicketController extends Controller
{
    /**
     * Authorize action based on user role and ticket details.
     */
    private function authorizeTicket(ServiceTicket $ticket, string $action = 'view')
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            abort(401);
        }

        $roleId = (int) $user->role_id;
        $userId = (int) $user->id;

        // Administrator has full access
        if ($roleId === 1) {
            return true;
        }

        $supportingUnitId = (int) ($ticket->category?->supporting_unit_id ?? 0);

        // PJ Ruangan is strictly scoped to their assigned room
        if ($roleId === Role::PJ_RUANGAN) {
            if ((int) $user->room_id !== (int) $ticket->room_id) {
                abort(403, 'Akses ditolak. Sebagai Penanggung Jawab Ruangan, Anda hanya dapat mengelola laporan dari ruangan Anda sendiri.');
            }
            if ($action === 'view' || $action === 'assign') {
                return true;
            }
        }

        if ($action === 'view') {
            // Admin and Disposisi roles can view any ticket
            if ($user->isAdmin() || $user->canDisposisi()) {
                return true;
            }

            // Technician can view if assigned
            if ($user->isTechnician() && $ticket->assignments()->where('technician_id', $userId)->exists()) {
                return true;
            }

            // Reporter can view if they submitted it
            if ($userId === (int) $ticket->reporter_id) {
                return true;
            }
        } elseif ($action === 'assign') {
            // Disposisi roles (Kepala Bidang, Kepala Seksi, Kepala Instalasi, Sekretaris Instalasi, Admin) can validate/assign
            if ($user->canDisposisi() || $user->isAdmin()) {
                return true;
            }
        } elseif ($action === 'execute') {
            // Assigned Technician can execute actions
            if ($user->isTechnician() && $ticket->assignments()->where('technician_id', $userId)->exists()) {
                return true;
            }
        }

        abort(403, 'Unauthorized action.');
    }

    /**
     * Display ticket details and actions.
     */
    public function show(Request $request, ServiceTicket $ticket)
    {
        $this->authorizeTicket($ticket, 'view');

        $user = \Illuminate\Support\Facades\Auth::user();
        $roleId = (int) $user->role_id;
        $supportingUnitId = (int) ($ticket->category?->supporting_unit_id ?? 0);

        if ($request->boolean('personal')) {
            return Inertia::render('Report/Show', [
                'ticket' => Inertia::defer(fn() => $ticket->load([
                    'reporter:id,name,nip',
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

        return Inertia::render('ReportManagement/Show', [
            'ticket' => Inertia::defer(fn() => $ticket->load([
                'reporter:id,name,nip',
                'validator:id,name,nip',
                'room:id,name,building_name,location_floor',
                'category.supportingUnit',
                'assignments.technician:id,name,nip',
                'attachments.user:id,name',
                'histories.user:id,name',
            ])),
            'technicians' => Inertia::defer(function() use ($user, $supportingUnitId) {
                /** @var \App\Models\User $user */
                if ($user->canDisposisi() || $user->isAdmin()) {
                    $techQuery = User::where('role_id', Role::TEKNISI)
                        ->where('is_active', 1);

                    if ($supportingUnitId > 0) {
                        if ((clone $techQuery)->where('supporting_unit_id', $supportingUnitId)->exists()) {
                            $techQuery->where('supporting_unit_id', $supportingUnitId);
                        }
                    }

                    return $techQuery->with('supportingUnit:id,name')
                        ->orderBy('name')
                        ->get(['id', 'name', 'nip', 'supporting_unit_id']);
                }
                return [];
            }),
        ]);
    }

    /**
     * Validate ticket and assign it to technicians.
     */
    public function assign(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.supportingUnit');
        $this->authorizeTicket($ticket, 'assign');

        $validated = $request->validate([
            'priority' => 'required|in:URGENT,ROUTINE',
            'technician_ids' => 'required|array|min:1',
            'technician_ids.*' => 'required|exists:users,id',
        ]);

        // Update ticket
        $ticket->update([
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'priority' => $validated['priority'],
            'status' => 'ASSIGNED',
        ]);

        // Insert assignments
        foreach ($validated['technician_ids'] as $techId) {
            TicketAssignment::firstOrCreate([
                'ticket_id' => $ticket->id,
                'technician_id' => $techId,
            ], [
                'assigned_at' => now(),
            ]);
        }

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'status' => 'ASSIGNED',
            'action' => 'ASSIGNED',
            'notes' => 'Laporan divalidasi dan ditugaskan ke teknisi.',
        ]);

        // Notify assigned technicians
        $ticket->load(['room', 'category']);
        $technicians = \App\Models\User::whereIn('id', $validated['technician_ids'])->get();

        if ($technicians->isNotEmpty()) {
            try {
                \Illuminate\Support\Facades\Notification::send($technicians, new \App\Notifications\TicketAssignedNotification($ticket));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi penugasan ke Teknisi: ' . $e->getMessage());
            }
        }

        $this->sendTicketStatusNotification($ticket, 'ASSIGNED');

        return redirect()->back()->with('success', 'Tiket pelaporan berhasil divalidasi dan ditugaskan.');
    }

    /**
     * Technician arrives at location and records response time.
     */
    public function respond(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.supportingUnit');
        $this->authorizeTicket($ticket, 'execute');

        if ($ticket->status !== 'ASSIGNED') {
            return redirect()->back()->with('error', 'Status tiket tidak valid untuk aksi ini.');
        }

        $validated = $request->validate([
            'attachments' => 'required|array|min:1|max:5',
            'attachments.*' => 'required|string',
        ], [
            'attachments.required' => 'Wajib mengunggah minimal 1 foto bukti kedatangan di lokasi.',
            'attachments.min' => 'Wajib mengunggah minimal 1 foto bukti kedatangan di lokasi.',
        ]);

        $ticket->update([
            'responded_at' => now(),
            'status' => 'IN_PROGRESS',
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'status' => 'IN_PROGRESS',
            'action' => 'ARRIVED',
            'notes' => 'Teknisi tiba di lokasi & mulai pengerjaan.',
        ]);

        $this->sendTicketStatusNotification($ticket, 'ARRIVED');

        // Save arrival images securely
        $attachments = $request->input('attachments', []);
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $dataUrl) {
                $filePath = SecureFileUpload::saveBase64($dataUrl, 'ticket_attachments', 'ticket_arr_');
                if ($filePath) {
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $filePath,
                        'uploaded_by' => $request->user()->id,
                        'uploaded_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Bukti kedatangan berhasil disimpan. Waktu respon tercatat dan status tiket diubah menjadi Dikerjakan.');
    }

    /**
     * Technician completes, pends, or cancels the ticket.
     */
    public function resolve(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.supportingUnit');
        $this->authorizeTicket($ticket, 'execute');

        $validated = $request->validate([
            'resolution_status' => 'required|in:COMPLETED,PENDING,CANCEL',
            'notes' => 'required_if:resolution_status,PENDING,CANCEL|nullable|string|min:5',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|string',
        ]);

        $status = $validated['resolution_status'];
        $notes = $validated['notes'];

        if ($status === 'COMPLETED') {
            // Update ticket details
            $ticket->update([
                'status' => 'COMPLETED',
                'resolved_at' => now(),
                'completion_notes' => $notes,
            ]);

            // Calculate pending duration if last_paused_at was active
            if ($ticket->last_paused_at) {
                $pausedDiff = (int) abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($ticket->last_paused_at)));
                $ticket->increment('paused_duration_seconds', $pausedDiff);
                $ticket->update(['last_paused_at' => null]);
            }

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'status' => 'COMPLETED',
                'action' => 'COMPLETED',
                'notes' => $notes ?: 'Pekerjaan dinyatakan selesai.',
            ]);

            $this->sendTicketStatusNotification($ticket, 'COMPLETED', $notes);

            // Save resolution images securely
            $attachments = $request->input('attachments', []);
            if (is_array($attachments) && count($attachments) > 0) {
                foreach ($attachments as $dataUrl) {
                    $filePath = SecureFileUpload::saveBase64($dataUrl, 'ticket_attachments', 'ticket_res_');
                    if ($filePath) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'file_path' => $filePath,
                            'uploaded_by' => $request->user()->id,
                            'uploaded_at' => now(),
                        ]);
                    }
                }
            }

            return redirect()->back()->with('success', 'Laporan berhasil diselesaikan.');
        } elseif ($status === 'PENDING') {
            $ticket->update([
                'status' => 'PENDING',
                'pending_reason' => $notes,
                'last_paused_at' => now(),
            ]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'status' => 'PENDING',
                'action' => 'PAUSED',
                'notes' => $notes,
            ]);

            $this->sendTicketStatusNotification($ticket, 'PENDING', $notes);

            return redirect()->back()->with('success', 'Laporan berhasil ditangguhkan.');
        } elseif ($status === 'CANCEL') {
            $ticket->update([
                'status' => 'CANCEL',
                'resolved_at' => now(),
                'completion_notes' => $notes,
            ]);

            // Clear any active pause
            if ($ticket->last_paused_at) {
                $pausedDiff = (int) abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($ticket->last_paused_at)));
                $ticket->increment('paused_duration_seconds', $pausedDiff);
                $ticket->update(['last_paused_at' => null]);
            }

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'status' => 'CANCEL',
                'action' => 'CANCEL',
                'notes' => $notes ?: 'Laporan dibatalkan.',
            ]);

            $this->sendTicketStatusNotification($ticket, 'CANCEL', $notes);

            return redirect()->back()->with('success', 'Laporan berhasil dibatalkan.');
        }

        return redirect()->back()->with('error', 'Aksi penyelesaian tidak valid.');
    }

    /**
     * Technician resumes work on a pending ticket.
     */
    public function resume(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.supportingUnit');
        $this->authorizeTicket($ticket, 'execute');

        if ($ticket->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Status tiket tidak valid untuk dilanjutkan.');
        }

        // Calculate paused duration
        $pausedDiff = 0;
        if ($ticket->last_paused_at) {
            $pausedDiff = (int) abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($ticket->last_paused_at)));
        }

        $currentPaused = (int) ($ticket->paused_duration_seconds ?? 0);
        $ticket->update([
            'status' => 'IN_PROGRESS',
            'last_paused_at' => null,
            'paused_duration_seconds' => $currentPaused + $pausedDiff,
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'status' => 'IN_PROGRESS',
            'action' => 'RESUMED',
            'duration_seconds' => $pausedDiff,
            'notes' => 'Pekerjaan dilanjutkan kembali.',
        ]);

        $this->sendTicketStatusNotification($ticket, 'RESUMED');

        return redirect()->back()->with('success', 'Pekerjaan dilanjutkan kembali.');
    }

    /**
     * Send real-time status update notification via Reverb & database to Pelapor, Ka Ruangan, Ka Unit, and Admin.
     */
    protected function sendTicketStatusNotification(ServiceTicket $ticket, string $status, ?string $notes = null): void
    {
        $ticket->load(['reporter', 'room', 'category.supportingUnit']);
        $supportingUnitId = $ticket->category?->supporting_unit_id;
        $reporterId = $ticket->reporter_id;
        $actorId = \Illuminate\Support\Facades\Auth::id();

        $recipients = User::where('is_active', 1)
            ->where('id', '!=', $actorId) // Don't send notification to the user who performed the action
            ->where(function ($query) use ($status, $supportingUnitId, $reporterId) {
                $query->where('id', $reporterId) // Pelapor
                    ->orWhere('role_id', Role::ADMINISTRATOR); // Administrator

                // Ka Instalasi receives updates for status changes
                if ($status !== 'ASSIGNED' && $supportingUnitId) {
                    $query->orWhere(function ($q) use ($supportingUnitId) {
                        $q->where('role_id', Role::KEPALA_INSTALASI)->where('supporting_unit_id', $supportingUnitId); // Ka Instalasi
                    });
                }
            })
            ->get();

        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, new TicketStatusUpdatedNotification($ticket, $status, $notes));
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim notifikasi status tiket: ' . $e->getMessage());
            }
        }

        try {
            \App\Events\TicketRealtimeUpdated::dispatch($ticket, 'status_changed');
        } catch (\Throwable $e) {
            Log::error('Gagal broadcast TicketRealtimeUpdated: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete ticket (Admin only).
     */
    public function destroy(Request $request, ServiceTicket $ticket)
    {
        $user = $request->user();
        if ((int) $user->role_id !== 1) {
            abort(403, 'Hanya Administrator yang memiliki wewenang untuk menghapus laporan.');
        }

        TicketHistory::create([
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
