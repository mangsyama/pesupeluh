<?php

namespace App\Http\Controllers;

use App\Models\SupportingUnit;
use App\Models\Room;
use App\Models\ServiceTicket;
use App\Services\SecureFileUpload;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function showUnit(string $unitName)
    {
        $supportingUnit = SupportingUnit::where('slug', strtolower($unitName))
            ->orWhereRaw('LOWER(name) = ?', [strtolower($unitName)])
            ->firstOrFail();

        if ($supportingUnit->status !== 'ACTIVE') {
            return redirect()->route('services.index')->with('error', 'Unit penunjang ini sedang dalam tahap pengembangan.');
        }

        return Inertia::render('Service/Show', [
            'unit' => $supportingUnit->load(['issueCategories']),
            'rooms' => Room::orderBy('name', 'asc')->get()
        ]);
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'category_id' => 'required|exists:issue_categories,id',
            'problem_description' => 'required|string|min:5',
            'priority' => 'nullable|in:ROUTINE,URGENT,EMERGENCY',
            'attachments' => 'required|array|min:1|max:5',
            'attachments.*' => 'required|string',
        ], [
            'attachments.required' => 'Lampiran foto / video wajib diunggah.',
            'attachments.min' => 'Lampiran foto / video wajib diunggah minimal 1 file.',
        ]);

        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $inputPriority = $validated['priority'] ?? 'ROUTINE';
        $isEmergency = ($inputPriority === 'EMERGENCY');

        // Check category & supporting unit
        $category = \App\Models\IssueCategory::find($validated['category_id']);
        $supportingUnitId = $category?->supporting_unit_id;

        // Determine if currently off-hours using UnitWorkingHourService
        $isOffHours = !$isEmergency && \App\Services\UnitWorkingHourService::isOffHours($supportingUnitId);


        $initialStatus = ($isEmergency || $isOffHours) ? 'ASSIGNED' : 'PENDING_VALIDATION';
        $finalPriority = $isEmergency ? 'EMERGENCY' : $inputPriority;

        $ticket = ServiceTicket::create([
            'ticket_number' => $ticketNumber,
            'reporter_id' => $request->user()->id,
            'room_id' => $validated['room_id'],
            'category_id' => $validated['category_id'],
            'problem_description' => $validated['problem_description'],
            'priority' => $finalPriority,
            'status' => $initialStatus,
            'validated_at' => ($isEmergency || $isOffHours) ? now() : null,
            'validated_by' => $isEmergency ? $request->user()->id : null,
        ]);

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'status' => $initialStatus,
            'action' => $isEmergency ? 'EMERGENCY_CREATED' : ($isOffHours ? 'OFF_HOURS_CREATED' : 'CREATED'),
            'notes' => $isEmergency 
                ? '🚨 LAPORAN DARURAT (CODE RED): Dibuat oleh pelapor, sistem otomatis memotong alur disposisi.' 
                : ($isOffHours 
                    ? '🌙 LAPORAN DIBUAT DI LUAR JAM OPERASIONAL: Sistem mengaktifkan disposisi otomatis.' 
                    : 'Laporan berhasil dibuat dan dikirim oleh pelapor.'),
        ]);

        // Auto-assign technicians if Emergency or Off-Hours
        if ($isEmergency || $isOffHours) {
            $onDutyTechnicians = \App\Models\User::where('role_id', 6) // TECHNICIAN
                ->where('supporting_unit_id', $supportingUnitId)
                ->where('is_active', 1)
                ->where('is_on_duty', 1)
                ->get();

            if ($onDutyTechnicians->isEmpty()) {
                $onDutyTechnicians = \App\Models\User::where('role_id', 6)
                    ->where('supporting_unit_id', $supportingUnitId)
                    ->where('is_active', 1)
                    ->get();
            }

            foreach ($onDutyTechnicians as $tech) {
                \App\Models\TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'technician_id' => $tech->id,
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                ]);
            }

            \App\Models\TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'status' => 'ASSIGNED',
                'action' => $isEmergency ? 'EMERGENCY_DISPATCH' : 'AUTO_DISPATCH',
                'notes' => $isEmergency 
                    ? 'Penanganan darurat! Tiket otomatis disebar ke seluruh teknisi piket.' 
                    : 'Disposisi otomatis di luar jam kerja operasional unit penunjang.',
            ]);

            // Immediately send WA & App notification to assigned technicians for Emergency / Off-Hours
            if ($onDutyTechnicians->isNotEmpty()) {
                try {
                    $ticket->load(['room', 'category']);
                    \Illuminate\Support\Facades\Notification::send($onDutyTechnicians, new \App\Notifications\TicketAssignedNotification($ticket));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal mengirim WA penugasan ke teknisi piket: ' . $e->getMessage());
                }
            }
        }


        $attachments = $request->input('attachments', []);
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $dataUrl) {
                $filePath = SecureFileUpload::saveBase64($dataUrl, 'ticket_attachments', 'ticket_');
                if ($filePath) {
                    \App\Models\TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $filePath,
                        'uploaded_by' => $request->user()->id,
                        'uploaded_at' => now(),
                    ]);
                }
            }
        }

        // Notify Unit Heads & Technicians & Admins
        $ticket->load(['reporter', 'room', 'category.supportingUnit']);

        if ($supportingUnitId || $ticket->room_id) {
            $recipients = \App\Models\User::where('is_active', 1)
                ->where('id', '!=', $ticket->reporter_id)
                ->where(function ($query) use ($supportingUnitId, $ticket, $isEmergency, $isOffHours) {
                    if ($supportingUnitId) {
                        $query->where(function ($q) use ($supportingUnitId) {
                            $q->where('role_id', 5)->where('supporting_unit_id', $supportingUnitId);
                        });

                        // If Emergency or Off-Hours, also notify Technicians directly!
                        if ($isEmergency || $isOffHours) {
                            $query->orWhere(function ($q) use ($supportingUnitId) {
                                $q->where('role_id', 6)->where('supporting_unit_id', $supportingUnitId);
                            });
                        }
                    }
                    $query->orWhere('role_id', 1); // Administrator
                    if ($ticket->room_id) {
                        $query->orWhere(function ($q) use ($ticket) {
                            $q->where('role_id', 7)->where('room_id', $ticket->room_id); // Kepala Ruangan
                        });
                    }
                })
                ->get();

            if ($recipients->isNotEmpty()) {
                try {
                    \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\NewTicketReportedNotification($ticket));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi tiket baru: ' . $e->getMessage());
                }
            }
        }

        $successMsg = $isEmergency 
            ? '🚨 Laporan DARURAT berhasil terkirim dan langsung diteruskan ke seluruh teknisi piket!' 
            : ($isOffHours 
                ? '🌙 Laporan berhasil terbuat di luar jam operasional & otomatis didisposisikan ke teknisi piket.' 
                : 'Tiket pelaporan baru berhasil dibuat.');

        return redirect()->route('reports.history')->with('success', $successMsg);
    }
}
