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
            'priority' => 'nullable|in:ROUTINE,URGENT',
            'attachments' => 'required|array|min:1|max:5',
            'attachments.*' => 'required|string',
        ], [
            'attachments.required' => 'Lampiran foto / video wajib diunggah.',
            'attachments.min' => 'Lampiran foto / video wajib diunggah minimal 1 file.',
        ]);

        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $inputPriority = $validated['priority'] ?? 'ROUTINE';

        // Check category & supporting unit
        $category = \App\Models\IssueCategory::find($validated['category_id']);
        $supportingUnitId = $category?->supporting_unit_id;

        // Determine if currently off-hours using UnitWorkingHourService
        $isOffHours = \App\Services\UnitWorkingHourService::isOffHours($supportingUnitId);

        $initialStatus = $isOffHours ? 'ASSIGNED' : 'PENDING_VALIDATION';

        $ticket = ServiceTicket::create([
            'ticket_number' => $ticketNumber,
            'reporter_id' => $request->user()->id,
            'room_id' => $validated['room_id'],
            'category_id' => $validated['category_id'],
            'problem_description' => $validated['problem_description'],
            'priority' => $inputPriority,
            'status' => $initialStatus,
            'validated_at' => $isOffHours ? now() : null,
            'validated_by' => null,
        ]);

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'status' => $initialStatus,
            'action' => $isOffHours ? 'OFF_HOURS_CREATED' : 'CREATED',
            'notes' => $isOffHours 
                ? '🌙 LAPORAN DIBUAT DI LUAR JAM OPERASIONAL: Sistem mengaktifkan disposisi otomatis.' 
                : 'Laporan berhasil dibuat dan dikirim oleh pelapor.',
        ]);

        // Auto-assign technicians if Off-Hours
        if ($isOffHours) {
            $technicians = \App\Models\User::where('role_id', \App\Models\Role::TEKNISI) // TEKNISI
                ->where('is_active', 1)
                ->withCount(['assignments as active_tickets_count' => function ($query) {
                    $query->whereHas('ticket', function ($q) {
                        $q->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'PENDING']);
                    });
                }])
                ->get();

            $onDutyTechs = $technicians->where('is_on_duty', 1);
            $candidatePool = $onDutyTechs->isNotEmpty() ? $onDutyTechs : $technicians;

            $selectedTech = $candidatePool->sort(function ($a, $b) use ($supportingUnitId) {
                $workloadA = $a->active_tickets_count;
                $workloadB = $b->active_tickets_count;

                if ($workloadA !== $workloadB) {
                    return $workloadA <=> $workloadB; // Lowest active workload first
                }

                // Tie-breaker: matching supporting unit first
                $matchA = ($supportingUnitId && (int)$a->supporting_unit_id === (int)$supportingUnitId) ? 0 : 1;
                $matchB = ($supportingUnitId && (int)$b->supporting_unit_id === (int)$supportingUnitId) ? 0 : 1;

                return $matchA <=> $matchB;
            })->first();

            $onDutyTechnicians = collect();

            if ($selectedTech) {
                \App\Models\TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'technician_id' => $selectedTech->id,
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                ]);
                $onDutyTechnicians->push($selectedTech);
            }

            \App\Models\TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'status' => 'ASSIGNED',
                'action' => 'AUTO_DISPATCH',
                'notes' => 'Disposisi otomatis di luar jam kerja operasional unit penunjang.',
            ]);

            // Immediately send WA & App notification to assigned technicians & reporter for Off-Hours
            if ($onDutyTechnicians->isNotEmpty()) {
                try {
                    $ticket->load(['room', 'category', 'reporter']);
                    \Illuminate\Support\Facades\Notification::send($onDutyTechnicians, new \App\Notifications\TicketAssignedNotification($ticket));
                    if ($ticket->reporter) {
                        \Illuminate\Support\Facades\Notification::send($ticket->reporter, new \App\Notifications\TicketStatusUpdatedNotification($ticket, 'ASSIGNED', 'Disposisi otomatis di luar jam kerja operasional.'));
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal mengirim WA/App penugasan ke teknisi/pelapor: ' . $e->getMessage());
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
                ->where(function ($query) use ($supportingUnitId, $ticket, $isOffHours) {
                    $query->where('role_id', \App\Models\Role::ADMINISTRATOR)
                        ->orWhere('role_id', \App\Models\Role::KEPALA_BIDANG);

                    if ($supportingUnitId) {
                        $query->orWhere(function ($q) use ($supportingUnitId) {
                            $q->whereIn('role_id', [
                                \App\Models\Role::KEPALA_SEKSI,
                                \App\Models\Role::KEPALA_INSTALASI,
                                \App\Models\Role::SEKRETARIS_INSTALASI,
                            ])->where(function ($q2) use ($supportingUnitId) {
                                $q2->where('supporting_unit_id', $supportingUnitId)
                                   ->orWhereNull('supporting_unit_id');
                            });
                        });

                        // Off-hours assigned technician is already notified specifically via TicketAssignedNotification
                    }

                    if ($ticket->room_id) {
                        $query->orWhere(function ($q) use ($ticket) {
                            $q->where('role_id', \App\Models\Role::PJ_RUANGAN)->where('room_id', $ticket->room_id); // PJ Ruangan
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

        try {
            \App\Events\TicketRealtimeUpdated::dispatch($ticket, 'created');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal broadcast TicketRealtimeUpdated: ' . $e->getMessage());
        }

        $successMsg = $isOffHours 
            ? '🌙 Laporan berhasil terbuat di luar jam operasional & otomatis didisposisikan ke teknisi piket.' 
            : 'Tiket pelaporan baru berhasil dibuat.';

        return redirect()->route('reports.history')->with('success', $successMsg);
    }
}
