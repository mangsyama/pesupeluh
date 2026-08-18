<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketHistory;
use App\Models\User;
use App\Services\UnitWorkingHourService;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class AutoDispoCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-auto-dispo {--timeout=5 : Timeout in minutes for SLA auto-disposition} {--seconds= : Timeout in seconds for fast testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis disposisi tiket PENDING_VALIDATION > timeout (default 5 menit) di jam kerja ke teknisi dengan penugasan tersedikit.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $secondsOpt = $this->option('seconds');
        if ($secondsOpt !== null && (int)$secondsOpt > 0) {
            $timeoutSeconds = (int) $secondsOpt;
            $cutoffTime = now()->subSeconds($timeoutSeconds);
            $timeUnitLabel = "{$timeoutSeconds} detik";
        } else {
            $timeoutMinutes = (int) $this->option('timeout');
            $cutoffTime = now()->subMinutes($timeoutMinutes);
            $timeUnitLabel = "{$timeoutMinutes} menit";
        }

        $this->info("Memeriksa tiket PENDING_VALIDATION yang terlewat > {$timeUnitLabel} (sebelum {$cutoffTime->toDateTimeString()})...");

        $pendingTickets = ServiceTicket::where('status', 'PENDING_VALIDATION')
            ->where('created_at', '<=', $cutoffTime)
            ->with(['category.supportingUnit', 'room'])
            ->get();


        $count = 0;
        // Track workload additions within this execution batch for load balancing
        $batchWorkloadAdditions = [];

        foreach ($pendingTickets as $ticket) {
            $supportingUnitId = $ticket->category?->supporting_unit_id;
            $isOperational = UnitWorkingHourService::isOperationalHours($supportingUnitId);

            // Query active technicians
            $techniciansQuery = User::where('role_id', \App\Models\Role::TEKNISI) // TEKNISI
                ->where('is_active', 1)
                ->withCount(['assignments as active_tickets_count' => function ($query) {
                    $query->whereHas('ticket', function ($q) {
                        $q->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'PENDING']);
                    });
                }]);

            if ($supportingUnitId) {
                $unitTechs = (clone $techniciansQuery)->where('supporting_unit_id', $supportingUnitId)->get();
                $technicians = $unitTechs->isNotEmpty() ? $unitTechs : $techniciansQuery->get();
            } else {
                $technicians = $techniciansQuery->get();
            }

            if ($technicians->isEmpty()) {
                $this->warn("Tiket #{$ticket->ticket_number}: Tidak ada teknisi aktif di sistem.");
                continue;
            }

            // Prioritize on-duty technicians if available
            $onDutyTechs = $technicians->where('is_on_duty', 1);
            $candidatePool = $onDutyTechs->isNotEmpty() ? $onDutyTechs : $technicians;

            // Update ticket status to ASSIGNED (validated_by is null = Auto System)
            $ticket->update([
                'status'       => 'ASSIGNED',
                'priority'     => $ticket->priority ?? 'ROUTINE',
                'validated_at' => now(),
                'validated_by' => null,
            ]);

            $assignedTechs = collect();

            if (!$isOperational) {
                // Outside operational hours: assign ALL candidate technicians
                foreach ($candidatePool as $tech) {
                    TicketAssignment::create([
                        'ticket_id'     => $ticket->id,
                        'technician_id' => $tech->id,
                        'assigned_by'   => null, // System auto
                        'assigned_at'   => now(),
                    ]);
                    $assignedTechs->push($tech);
                    $batchWorkloadAdditions[$tech->id] = ($batchWorkloadAdditions[$tech->id] ?? 0) + 1;
                }
            } else {
                // Operational hours: select single technician with lowest workload
                $selectedTech = $candidatePool->sort(function ($a, $b) use ($supportingUnitId, $batchWorkloadAdditions) {
                    $workloadA = $a->active_tickets_count + ($batchWorkloadAdditions[$a->id] ?? 0);
                    $workloadB = $b->active_tickets_count + ($batchWorkloadAdditions[$b->id] ?? 0);

                    if ($workloadA !== $workloadB) {
                        return $workloadA <=> $workloadB; // Lowest active workload first
                    }

                    // Tie-breaker: matching supporting unit first
                    $matchA = ($supportingUnitId && (int)$a->supporting_unit_id === (int)$supportingUnitId) ? 0 : 1;
                    $matchB = ($supportingUnitId && (int)$b->supporting_unit_id === (int)$supportingUnitId) ? 0 : 1;

                    return $matchA <=> $matchB;
                })->first();

                if ($selectedTech) {
                    TicketAssignment::create([
                        'ticket_id'     => $ticket->id,
                        'technician_id' => $selectedTech->id,
                        'assigned_by'   => null,
                        'assigned_at'   => now(),
                    ]);
                    $assignedTechs->push($selectedTech);
                    $batchWorkloadAdditions[$selectedTech->id] = ($batchWorkloadAdditions[$selectedTech->id] ?? 0) + 1;
                }
            }

            if ($assignedTechs->isEmpty()) {
                $this->warn("Tiket #{$ticket->ticket_number}: Gagal memilih teknisi.");
                continue;
            }

            $reasonNote = $isOperational
                ? "Laporan terlewat {$timeUnitLabel} pada jam kerja operasional tanpa disposisi petugas."
                : "Laporan belum didisposisikan di luar jam kerja operasional.";

            $techNames = $assignedTechs->pluck('name')->join(', ');

            // Log ticket history
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id'   => 1, // Admin / System
                'status'    => 'ASSIGNED',
                'action'    => 'AUTO_DISPATCH_SYSTEM',
                'notes'     => "⚡ DISPOSISI OTOMATIS OLEH SISTEM: {$reasonNote} Otomatis dialihkan ke teknisi: {$techNames}.",
            ]);

            // Notify assigned technician
            try {
                $ticket->load(['room', 'category']);
                Notification::send($assignedTechs, new TicketAssignedNotification($ticket));
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim notifikasi auto-dispo ke Teknisi ({$techNames}): " . $e->getMessage());
            }

            // Also notify Unit Head (Ka Unit) & Admin about this auto-disposition
            try {
                $firstTech = $assignedTechs->first();
                $unitHeadsAndAdmins = User::where('is_active', 1)
                    ->where(function ($query) use ($supportingUnitId) {
                        $query->where('role_id', \App\Models\Role::ADMINISTRATOR)
                              ->orWhere('role_id', \App\Models\Role::KEPALA_BIDANG)
                              ->orWhere(function ($q) use ($supportingUnitId) {
                                  $q->whereIn('role_id', [
                                      \App\Models\Role::KEPALA_SEKSI,
                                      \App\Models\Role::KEPALA_INSTALASI,
                                      \App\Models\Role::SEKRETARIS_INSTALASI,
                                  ])->where(function ($q2) use ($supportingUnitId) {
                                      $q2->where('supporting_unit_id', $supportingUnitId)
                                         ->orWhereNull('supporting_unit_id');
                                  });
                              });
                    })
                    ->get();

                if ($unitHeadsAndAdmins->isNotEmpty() && $firstTech) {
                    Notification::send($unitHeadsAndAdmins, new \App\Notifications\TicketAutoDispatchedNotification($ticket, $firstTech));
                }
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim notifikasi auto-dispo ke Ka.Unit/Admin: " . $e->getMessage());
            }

            // Also notify Reporter about the ticket status update (ASSIGNED)
            try {
                $ticket->load(['reporter']);
                if ($ticket->reporter) {
                    Notification::send($ticket->reporter, new \App\Notifications\TicketStatusUpdatedNotification($ticket, 'ASSIGNED', $reasonNote));
                }
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim notifikasi status penugasan ke Pelapor: " . $e->getMessage());
            }

            try {
                \App\Events\TicketRealtimeUpdated::dispatch($ticket, 'status_changed');
            } catch (\Throwable $e) {
                Log::error('Gagal broadcast TicketRealtimeUpdated di auto-dispo command: ' . $e->getMessage());
            }

            $count++;
            $this->info("Tiket #{$ticket->ticket_number} berhasil didisposisikan otomatis ke teknisi {$techNames}.");
        }

        $this->info("Proses selesai. Total {$count} tiket didisposisikan otomatis oleh sistem.");

        return Command::SUCCESS;
    }
}

