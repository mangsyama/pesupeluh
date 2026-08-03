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
            if (!$supportingUnitId) {
                $this->warn("Tiket #{$ticket->ticket_number}: Tidak memiliki unit penunjang.");
                continue;
            }

            // Query active technicians for this supporting unit with active task counts
            $technicians = User::where('role_id', \App\Models\Role::TEKNISI) // TEKNISI
                ->where('supporting_unit_id', $supportingUnitId)
                ->where('is_active', 1)
                ->withCount(['assignments as active_tickets_count' => function ($query) {
                    $query->whereHas('ticket', function ($q) {
                        $q->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'PENDING']);
                    });
                }])
                ->get();

            if ($technicians->isEmpty()) {
                $this->warn("Tiket #{$ticket->ticket_number}: Tidak ada teknisi aktif pada unit ID {$supportingUnitId}.");
                continue;
            }

            // Prioritize on-duty technicians if available
            $onDutyTechs = $technicians->where('is_on_duty', 1);
            $candidatePool = $onDutyTechs->isNotEmpty() ? $onDutyTechs : $technicians;

            // Sort technicians by current active workload + in-memory batch additions (fewest first)
            $selectedTech = $candidatePool->sortBy(function ($tech) use ($batchWorkloadAdditions) {
                $added = $batchWorkloadAdditions[$tech->id] ?? 0;
                return $tech->active_tickets_count + $added;
            })->first();

            if (!$selectedTech) {
                $this->warn("Tiket #{$ticket->ticket_number}: Gagal memilih teknisi.");
                continue;
            }

            $currentWorkload = $selectedTech->active_tickets_count + ($batchWorkloadAdditions[$selectedTech->id] ?? 0);

            // Update ticket status to ASSIGNED (validated_by is null = Auto System)
            $ticket->update([
                'status'       => 'ASSIGNED',
                'priority'     => $ticket->priority ?? 'ROUTINE',
                'validated_at' => now(),
                'validated_by' => null,
            ]);

            // Assign the selected technician with lowest workload
            TicketAssignment::create([
                'ticket_id'     => $ticket->id,
                'technician_id' => $selectedTech->id,
                'assigned_by'   => null, // System auto
                'assigned_at'   => now(),
            ]);

            // Track workload addition in batch
            $batchWorkloadAdditions[$selectedTech->id] = ($batchWorkloadAdditions[$selectedTech->id] ?? 0) + 1;

            $isOperational = UnitWorkingHourService::isOperationalHours($supportingUnitId);
            $reasonNote = $isOperational
                ? "Laporan terlewat {$timeUnitLabel} pada jam kerja operasional tanpa disposisi petugas."
                : "Laporan belum didisposisikan di luar jam kerja operasional.";


            // Log ticket history
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id'   => 1, // Admin / System
                'status'    => 'ASSIGNED',
                'action'    => 'AUTO_DISPATCH_SYSTEM',
                'notes'     => "⚡ DISPOSISI OTOMATIS OLEH SISTEM: {$reasonNote} Otomatis dialihkan ke teknisi {$selectedTech->name} (Penugasan Aktif: {$currentWorkload} tiket).",
            ]);

            // Notify assigned technician
            try {
                $ticket->load(['room', 'category']);
                Notification::send($selectedTech, new TicketAssignedNotification($ticket));
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim notifikasi auto-dispo ke Teknisi {$selectedTech->name}: " . $e->getMessage());
            }

            // Also notify Unit Head (Ka Unit) & Admin about this auto-disposition
            try {
                $unitHeadsAndAdmins = User::where('is_active', 1)
                    ->where(function ($query) use ($supportingUnitId) {
                        $query->where('role_id', 1) // Administrator
                              ->orWhere(function ($q) use ($supportingUnitId) {
                                  $q->where('role_id', 5)->where('supporting_unit_id', $supportingUnitId); // Ka Unit
                              });
                    })
                    ->get();

                if ($unitHeadsAndAdmins->isNotEmpty()) {
                    Notification::send($unitHeadsAndAdmins, new \App\Notifications\TicketAutoDispatchedNotification($ticket, $selectedTech));
                }
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim notifikasi auto-dispo ke Ka.Unit/Admin: " . $e->getMessage());
            }


            $count++;
            $this->info("Tiket #{$ticket->ticket_number} berhasil didisposisikan otomatis ke teknisi {$selectedTech->name} (Penugasan: {$currentWorkload} tiket).");
        }

        $this->info("Proses selesai. Total {$count} tiket didisposisikan otomatis oleh sistem.");

        return Command::SUCCESS;
    }
}

