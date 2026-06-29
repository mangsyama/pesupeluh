<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TicketController extends Controller
{
    /**
     * Authorize action based on user role and ticket details.
     */
    private function authorizeTicket(ServiceTicket $ticket, string $action = 'view')
    {
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

        $supportingUnitId = (int) $ticket->category->unitFeature->supporting_unit_id;

        if ($action === 'view') {
            // Director, Division Head, Section Head have read-only access to all tickets
            if (in_array($roleId, [2, 3, 4])) {
                return true;
            }

            // Unit Head can view if same unit
            if ($roleId === 5 && (int) $user->supporting_unit_id === $supportingUnitId) {
                return true;
            }

            // Technician can view if assigned
            if ($roleId === 6 && $ticket->assignments()->where('technician_id', $userId)->exists()) {
                return true;
            }

            // Room Head can view if same room
            if ($roleId === 7 && (int) $user->room_id === (int) $ticket->room_id) {
                return true;
            }

            // Reporter can view if they submitted it
            if ($roleId === 8 && $userId === (int) $ticket->reporter_id) {
                return true;
            }
        } elseif ($action === 'assign') {
            // Unit Head of same unit can validate/assign
            if ($roleId === 5 && (int) $user->supporting_unit_id === $supportingUnitId) {
                return true;
            }
        } elseif ($action === 'execute') {
            // Assigned Technician can execute actions
            if ($roleId === 6 && $ticket->assignments()->where('technician_id', $userId)->exists()) {
                return true;
            }
        }

        abort(403, 'Unauthorized action.');
    }

    /**
     * Display ticket details and actions.
     */
    public function show(ServiceTicket $ticket)
    {
        $ticket->load([
            'reporter:id,name,nip',
            'validator:id,name,nip',
            'room:id,name,location_floor',
            'category.unitFeature.supportingUnit.division',
            'assignments.technician:id,name,nip',
            'attachments.user:id,name',
        ]);

        $this->authorizeTicket($ticket, 'view');

        $user = \Illuminate\Support\Facades\Auth::user();
        $roleId = (int) $user->role_id;
        $supportingUnitId = (int) $ticket->category->unitFeature->supporting_unit_id;
        
        $technicians = [];
        // Unit Head or Admin can see list of technicians to assign
        if (($roleId === 5 && (int) $user->supporting_unit_id === $supportingUnitId) || $roleId === 1) {
            $technicians = User::where('role_id', 6) // TECHNICIAN
                ->where('supporting_unit_id', $supportingUnitId)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'nip']);
        }

        return Inertia::render('Ticket/Show', [
            'ticket' => $ticket,
            'technicians' => $technicians,
        ]);
    }

    /**
     * Validate ticket and assign it to technicians.
     */
    public function assign(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.unitFeature');
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

        return redirect()->back()->with('success', 'Tiket pelaporan berhasil divalidasi dan ditugaskan.');
    }

    /**
     * Technician arrives at location and records response time.
     */
    public function respond(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.unitFeature');
        $this->authorizeTicket($ticket, 'execute');

        if ($ticket->status !== 'ASSIGNED') {
            return redirect()->back()->with('error', 'Status tiket tidak valid untuk aksi ini.');
        }

        $ticket->update([
            'responded_at' => now(),
            'status' => 'IN_PROGRESS',
        ]);

        return redirect()->back()->with('success', 'Waktu respon tercatat. Status tiket diubah menjadi Dikerjakan.');
    }

    /**
     * Technician completes, pends, or cancels the ticket.
     */
    public function resolve(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.unitFeature');
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
                $pausedDiff = now()->diffInSeconds($ticket->last_paused_at);
                $ticket->increment('paused_duration_seconds', $pausedDiff);
                $ticket->update(['last_paused_at' => null]);
            }

            // Save resolution images
            $attachments = $request->input('attachments', []);
            if (is_array($attachments) && count($attachments) > 0) {
                Storage::disk('public')->makeDirectory('ticket_attachments');

                foreach ($attachments as $dataUrl) {
                    if (!is_string($dataUrl) || !str_starts_with($dataUrl, 'data:')) continue;

                    $parts = explode(";base64,", $dataUrl);
                    if (count($parts) !== 2) continue;

                    $fileContent = base64_decode($parts[1]);
                    $mimeHeader = $parts[0];

                    $ext = 'bin';
                    if (str_contains($mimeHeader, 'image/')) {
                        $ext = str_replace('data:image/', '', $mimeHeader) ?: 'jpeg';
                    } elseif (str_contains($mimeHeader, 'video/')) {
                        $ext = str_replace('data:video/', '', $mimeHeader) ?: 'mp4';
                    }

                    $filename = 'ticket_res_' . uniqid() . '.' . $ext;
                    Storage::disk('public')->put('ticket_attachments/' . $filename, $fileContent);
                    $filePath = '/storage/ticket_attachments/' . $filename;

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $filePath,
                        'uploaded_by' => $request->user()->id,
                        'uploaded_at' => now(),
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Laporan berhasil diselesaikan.');
        } elseif ($status === 'PENDING') {
            $ticket->update([
                'status' => 'PENDING',
                'pending_reason' => $notes,
                'last_paused_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Laporan berhasil ditangguhkan.');
        } elseif ($status === 'CANCEL') {
            $ticket->update([
                'status' => 'CANCEL',
                'resolved_at' => now(),
                'completion_notes' => $notes,
            ]);

            // Clear any active pause
            if ($ticket->last_paused_at) {
                $pausedDiff = now()->diffInSeconds($ticket->last_paused_at);
                $ticket->increment('paused_duration_seconds', $pausedDiff);
                $ticket->update(['last_paused_at' => null]);
            }

            return redirect()->back()->with('success', 'Laporan berhasil dibatalkan.');
        }

        return redirect()->back()->with('error', 'Aksi penyelesaian tidak valid.');
    }

    /**
     * Technician resumes work on a pending ticket.
     */
    public function resume(Request $request, ServiceTicket $ticket)
    {
        $ticket->load('category.unitFeature');
        $this->authorizeTicket($ticket, 'execute');

        if ($ticket->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Status tiket tidak valid untuk dilanjutkan.');
        }

        // Calculate paused duration
        $pausedDiff = 0;
        if ($ticket->last_paused_at) {
            $pausedDiff = now()->diffInSeconds($ticket->last_paused_at);
        }

        $ticket->update([
            'status' => 'IN_PROGRESS',
            'last_paused_at' => null,
            'paused_duration_seconds' => $ticket->paused_duration_seconds + $pausedDiff,
        ]);

        return redirect()->back()->with('success', 'Pekerjaan dilanjutkan kembali.');
    }
}
