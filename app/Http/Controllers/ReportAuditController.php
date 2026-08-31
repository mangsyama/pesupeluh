<?php

namespace App\Http\Controllers;

use App\Models\IssueCategory;
use App\Models\Role;
use App\Models\Room;
use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketHistory;
use App\Models\User;
use App\Services\SecureFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReportAuditController extends Controller
{
    const FILTER_SESSION_KEY = 'report_audit_filters';

    /**
     * Display a listing of all reports for audit, including soft-deleted records.
     */
    public function index(Request $request)
    {
        if ($request->filled('search') || $request->filled('status') || $request->has('deleted_only')) {
            $request->session()->put(self::FILTER_SESSION_KEY, [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
            ]);

            return redirect()->route('reports-audit.index');
        }

        $sessionFilters = $request->session()->get(self::FILTER_SESSION_KEY, []);
        $filters = [
            'search' => $request->input('search', $sessionFilters['search'] ?? ''),
            'status' => $request->input('status', $sessionFilters['status'] ?? ''),
        ];

        // Include soft deleted tickets for audit
        $query = ServiceTicket::withTrashed()
            ->select([
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
                'deleted_at',
            ])
            ->with([
                'reporter:id,name,phone_number,nip',
                'room:id,name,building_name,location_floor',
                'category:id,name,supporting_unit_id',
                'category.supportingUnit:id,name',
                'assignments.technician:id,name,nip',
            ])
            ->orderByDesc('created_at');

        // Filter search query
        if ($search = trim($filters['search'])) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('problem_description', 'like', "%{$search}%")
                  ->orWhereHas('reporter', fn($r) => $r->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn($rm) => $rm->where('name', 'like', "%{$search}%")->orWhere('building_name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter status & deleted tabs
        if ($status = $filters['status']) {
            if ($status === 'DELETED') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'ACTIVE') {
                $query->whereNull('deleted_at');
            } elseif (str_contains($status, ',')) {
                $query->whereIn('status', explode(',', $status));
            } else {
                $query->where('status', $status);
            }
        }

        // Stats overview
        $stats = [
            'total_all' => ServiceTicket::withTrashed()->count(),
            'total_active' => ServiceTicket::count(),
            'total_pending' => ServiceTicket::where('status', 'PENDING_VALIDATION')->count(),
            'total_in_progress' => ServiceTicket::whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'PENDING'])->count(),
            'total_completed' => ServiceTicket::where('status', 'COMPLETED')->count(),
            'total_deleted' => ServiceTicket::onlyTrashed()->count(),
        ];

        return Inertia::render('ReportAudit/Index', [
            'tickets' => Inertia::defer(fn() => $query->paginate(15)),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Store filters in session.
     */
    public function storeFilters(Request $request)
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $request->session()->put(self::FILTER_SESSION_KEY, $filters);

        return redirect()->route('reports-audit.index');
    }

    /**
     * Display ticket audit detail & edit interface.
     */
    public function show(Request $request, string $uuid)
    {
        $ticket = ServiceTicket::withTrashed()
            ->where('uuid', $uuid)
            ->with([
                'reporter:id,name,nip,phone_number,email',
                'validator:id,name,nip',
                'room:id,name,building_name,location_floor',
                'category.supportingUnit:id,name',
                'assignments.technician:id,name,nip,supporting_unit_id',
                'attachments.user:id,name',
                'histories.user:id,name',
            ])
            ->firstOrFail();

        return Inertia::render('ReportAudit/Show', [
            'ticket' => $ticket,
            'rooms' => Room::orderBy('name', 'asc')->get(['id', 'name', 'building_name', 'location_floor']),
            'categories' => IssueCategory::with('supportingUnit:id,name')->orderBy('name', 'asc')->get(['id', 'name', 'supporting_unit_id']),
            'technicians' => User::where('role_id', Role::TEKNISI)
                ->where('is_active', 1)
                ->with('supportingUnit:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'nip', 'supporting_unit_id']),
            'users' => User::where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'nip', 'phone_number']),
        ]);
    }

    /**
     * Update all fields of the ticket from audit editor.
     */
    public function update(Request $request, string $uuid)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'reporter_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'category_id' => 'required|exists:issue_categories,id',
            'problem_description' => 'required|string',
            'priority' => 'nullable|string|in:ROUTINE,URGENT,EMERGENCY',
            'status' => 'required|string|in:PENDING_VALIDATION,ASSIGNED,IN_PROGRESS,PENDING,COMPLETED,CANCEL',
            'validated_by' => 'nullable|exists:users,id',
            'validated_at' => 'nullable|date',
            'responded_at' => 'nullable|date',
            'resolved_at' => 'nullable|date',
            'pending_reason' => 'nullable|string',
            'paused_duration_seconds' => 'nullable|integer|min:0',
            'completion_notes' => 'nullable|string',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'created_at' => 'nullable|date',
        ]);

        $actor = $request->user();
        $rawValidatedBy = $request->input('validated_by');
        $validatedBy = (!empty($rawValidatedBy) && $rawValidatedBy !== 'SYSTEM' && $rawValidatedBy !== 'null') ? (int) $rawValidatedBy : null;

        // Update core ticket fields
        $ticketData = [
            'reporter_id' => $validated['reporter_id'],
            'room_id' => $validated['room_id'],
            'category_id' => $validated['category_id'],
            'problem_description' => $validated['problem_description'],
            'priority' => $validated['priority'] ?? 'ROUTINE',
            'status' => $validated['status'],
            'validated_by' => $validatedBy,
            'validated_at' => !empty($validated['validated_at']) ? Carbon::parse($validated['validated_at']) : ($validatedBy ? now() : null),
            'responded_at' => !empty($validated['responded_at']) ? Carbon::parse($validated['responded_at']) : null,
            'resolved_at' => !empty($validated['resolved_at']) ? Carbon::parse($validated['resolved_at']) : null,
            'pending_reason' => $validated['pending_reason'] ?? null,
            'paused_duration_seconds' => (int) ($validated['paused_duration_seconds'] ?? 0),
            'completion_notes' => $validated['completion_notes'] ?? null,
        ];

        if (!empty($validated['created_at'])) {
            $ticketData['created_at'] = Carbon::parse($validated['created_at']);
        }

        $ticket->update($ticketData);

        // Sync Technician Assignments
        if (array_key_exists('technician_ids', $validated)) {
            $ticket->assignments()->delete();
            $techIds = array_filter((array) $validated['technician_ids']);
            foreach ($techIds as $techId) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'technician_id' => $techId,
                    'assigned_at' => $ticketData['validated_at'] ?? now(),
                ]);
            }
        }

        // Record Audit History
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'status' => $ticket->status,
            'action' => 'AUDIT_UPDATED',
            'notes' => 'Data tiket #' . $ticket->ticket_number . ' telah diperbarui melalui Audit Laporan oleh ' . $actor->name . '.',
        ]);

        try {
            \App\Events\TicketRealtimeUpdated::dispatch($ticket, 'updated', 'Data tiket diperbarui melalui Audit');
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed on audit update: ' . $e->getMessage());
        }

        return redirect()->route('reports-audit.show', $ticket->uuid)->with('success', 'Data laporan #' . $ticket->ticket_number . ' berhasil diperbarui via Audit.');
    }

    /**
     * Soft delete ticket from audit.
     */
    public function destroy(Request $request, string $uuid)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $actor = $request->user();

        if (!$ticket->trashed()) {
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'status' => $ticket->status,
                'action' => 'DELETED',
                'notes' => 'Laporan #' . $ticket->ticket_number . ' telah di-soft delete oleh ' . $actor->name . ' via Audit Laporan.',
            ]);

            $ticket->delete();
        }

        return redirect()->back()->with('success', 'Laporan #' . $ticket->ticket_number . ' berhasil dihapus (Soft Delete).');
    }

    /**
     * Restore soft-deleted ticket from audit.
     */
    public function restore(Request $request, string $uuid)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $actor = $request->user();

        if ($ticket->trashed()) {
            $ticket->restore();

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'status' => $ticket->status,
                'action' => 'RESTORED',
                'notes' => 'Laporan #' . $ticket->ticket_number . ' telah dipulihkan (Restore) oleh ' . $actor->name . ' via Audit Laporan.',
            ]);
        }

        return redirect()->back()->with('success', 'Laporan #' . $ticket->ticket_number . ' berhasil dipulihkan (Restore).');
    }

    /**
     * Upload new attachments for a ticket from audit mode.
     */
    public function uploadAttachment(Request $request, string $uuid)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $actor = $request->user();

        $savedCount = 0;

        // Support base64 array
        $base64Attachments = $request->input('attachments', []);
        if (is_array($base64Attachments) && count($base64Attachments) > 0) {
            foreach ($base64Attachments as $dataUrl) {
                if (!empty($dataUrl)) {
                    $filePath = SecureFileUpload::saveBase64($dataUrl, 'ticket_attachments', 'ticket_audit_');
                    if ($filePath) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'file_path' => $filePath,
                            'uploaded_by' => $actor->id,
                            'uploaded_at' => now(),
                        ]);
                        $savedCount++;
                    }
                }
            }
        }

        // Support direct file uploads
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $filePath = SecureFileUpload::saveUploadedFile($file, 'ticket_attachments', 'ticket_audit_');
                    if ($filePath) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'file_path' => $filePath,
                            'uploaded_by' => $actor->id,
                            'uploaded_at' => now(),
                        ]);
                        $savedCount++;
                    }
                }
            }
        }

        if ($savedCount > 0) {
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'status' => $ticket->status,
                'action' => 'AUDIT_ATTACHMENT_ADDED',
                'notes' => 'Menambahkan ' . $savedCount . ' lampiran foto baru via Audit oleh ' . $actor->name . '.',
            ]);

            return redirect()->back()->with('success', 'Berhasil menambahkan ' . $savedCount . ' lampiran foto.');
        }

        return redirect()->back()->with('error', 'Tidak ada foto valid yang berhasil diunggah.');
    }

    /**
     * Replace an existing attachment photo with a new file.
     */
    public function replaceAttachment(Request $request, string $uuid, int $attachmentId)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $attachment = TicketAttachment::where('id', $attachmentId)->where('ticket_id', $ticket->id)->firstOrFail();
        $actor = $request->user();

        $newFilePath = null;

        if ($request->filled('attachment')) {
            $newFilePath = SecureFileUpload::saveBase64($request->input('attachment'), 'ticket_attachments', 'ticket_audit_');
        } elseif ($request->hasFile('file') && $request->file('file')->isValid()) {
            $newFilePath = SecureFileUpload::saveUploadedFile($request->file('file'), 'ticket_attachments', 'ticket_audit_');
        }

        if (!$newFilePath) {
            return redirect()->back()->with('error', 'Gagal memproses file foto pengganti.');
        }

        // Delete old file from public storage disk
        SecureFileUpload::deleteFile($attachment->file_path);

        // Update attachment record
        $attachment->update([
            'file_path' => $newFilePath,
            'uploaded_by' => $actor->id,
            'uploaded_at' => now(),
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'status' => $ticket->status,
            'action' => 'AUDIT_ATTACHMENT_REPLACED',
            'notes' => 'Mengganti lampiran foto #' . $attachmentId . ' via Audit oleh ' . $actor->name . '.',
        ]);

        return redirect()->back()->with('success', 'Lampiran foto berhasil diganti dan disimpan ke sistem.');
    }

    /**
     * Delete an attachment photo from database and storage disk.
     */
    public function deleteAttachment(Request $request, string $uuid, int $attachmentId)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $attachment = TicketAttachment::where('id', $attachmentId)->where('ticket_id', $ticket->id)->firstOrFail();
        $actor = $request->user();

        // Delete from public storage disk
        SecureFileUpload::deleteFile($attachment->file_path);

        // Delete record from DB
        $attachment->delete();

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'status' => $ticket->status,
            'action' => 'AUDIT_ATTACHMENT_DELETED',
            'notes' => 'Menghapus lampiran foto #' . $attachmentId . ' via Audit oleh ' . $actor->name . '.',
        ]);

        return redirect()->back()->with('success', 'Lampiran foto #' . $attachmentId . ' berhasil dihapus dari sistem & penyimpanan.');
    }

    /**
     * Upload or Replace attachment for a specific slot: 'reporter', 'arrival', 'completion'.
     */
    public function updateSlotAttachment(Request $request, string $uuid, string $slotType)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $actor = $request->user();

        $prefix = match($slotType) {
            'arrival' => 'ticket_arr_',
            'completion' => 'ticket_res_',
            default => 'ticket_',
        };

        $assignedTechId = $ticket->assignments()->first()?->technician_id ?? $actor->id;
        $uploaderId = ($slotType === 'reporter') ? $ticket->reporter_id : $assignedTechId;

        $slotName = match($slotType) {
            'arrival' => 'Foto Bukti Hadir Teknisi',
            'completion' => 'Foto Bukti Penyelesaian',
            default => 'Foto Laporan Pelapor',
        };

        $newFilePath = null;
        if ($request->filled('attachment')) {
            $newFilePath = SecureFileUpload::saveBase64($request->input('attachment'), 'ticket_attachments', $prefix);
        } elseif ($request->hasFile('file') && $request->file('file')->isValid()) {
            $newFilePath = SecureFileUpload::saveUploadedFile($request->file('file'), 'ticket_attachments', $prefix);
        }

        if (!$newFilePath) {
            return redirect()->back()->with('error', 'Gagal memproses file foto.');
        }

        // Find existing attachments for this slot and remove them (disk + db)
        $existingAttachments = match($slotType) {
            'arrival' => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'like', '%ticket_arr_%')
                ->get(),
            'completion' => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'like', '%ticket_res_%')
                ->get(),
            default => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'not like', '%ticket_arr_%')
                ->where('file_path', 'not like', '%ticket_res_%')
                ->get(),
        };

        foreach ($existingAttachments as $oldAtt) {
            SecureFileUpload::deleteFile($oldAtt->file_path);
            $oldAtt->delete();
        }

        // Create the single attachment for this slot
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_path' => $newFilePath,
            'uploaded_by' => $uploaderId,
            'uploaded_at' => now(),
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'status' => $ticket->status,
            'action' => 'AUDIT_PHOTO_UPDATED',
            'notes' => 'Memperbarui ' . $slotName . ' via Audit oleh ' . $actor->name . '.',
        ]);

        return redirect()->back()->with('success', $slotName . ' berhasil diperbarui dan disimpan.');
    }

    /**
     * Delete attachment for a specific slot: 'reporter', 'arrival', 'completion'.
     */
    public function deleteSlotAttachment(Request $request, string $uuid, string $slotType)
    {
        $ticket = ServiceTicket::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $actor = $request->user();

        $slotName = match($slotType) {
            'arrival' => 'Foto Bukti Hadir Teknisi',
            'completion' => 'Foto Bukti Penyelesaian',
            default => 'Foto Laporan Pelapor',
        };

        $existingAttachments = match($slotType) {
            'arrival' => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'like', '%ticket_arr_%')
                ->get(),
            'completion' => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'like', '%ticket_res_%')
                ->get(),
            default => TicketAttachment::where('ticket_id', $ticket->id)
                ->where('file_path', 'not like', '%ticket_arr_%')
                ->where('file_path', 'not like', '%ticket_res_%')
                ->get(),
        };

        foreach ($existingAttachments as $att) {
            SecureFileUpload::deleteFile($att->file_path);
            $att->delete();
        }

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'status' => $ticket->status,
            'action' => 'AUDIT_PHOTO_DELETED',
            'notes' => 'Menghapus ' . $slotName . ' via Audit oleh ' . $actor->name . '.',
        ]);

        return redirect()->back()->with('success', $slotName . ' berhasil dihapus dari sistem & penyimpanan.');
    }
}
