<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketHistory;
use App\Models\User;
use App\Models\UnitWorkingHour;

class AutoDispoCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-auto-dispo {--timeout=30 : Timeout in minutes for SLA dispo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis disposisi tiket yang menggantung > SLA timeout atau masuk di luar jam kerja operasional.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $this->info("Memeriksa tiket PENDING_VALIDATION yang dibuat sebelum {$cutoffTime->toDateTimeString()}...");

        $pendingTickets = ServiceTicket::where('status', 'PENDING_VALIDATION')
            ->where('created_at', '<=', $cutoffTime)
            ->with(['category.supportingUnit'])
            ->get();

        $count = 0;

        foreach ($pendingTickets as $ticket) {
            $supportingUnitId = $ticket->category?->supporting_unit_id;
            if (!$supportingUnitId) continue;

            // Find on-duty technicians for this unit
            $onDutyTechnicians = User::where('role_id', 6) // TECHNICIAN
                ->where('supporting_unit_id', $supportingUnitId)
                ->where('is_active', 1)
                ->where('is_on_duty', 1)
                ->get();

            if ($onDutyTechnicians->isEmpty()) {
                $onDutyTechnicians = User::where('role_id', 6)
                    ->where('supporting_unit_id', $supportingUnitId)
                    ->where('is_active', 1)
                    ->get();
            }

            if ($onDutyTechnicians->isEmpty()) {
                $this->warn("Tiket #{$ticket->ticket_number}: Tidak ada teknisi aktif pada unit ID {$supportingUnitId}.");
                continue;
            }

            // Update ticket status
            $ticket->update([
                'status' => 'ASSIGNED',
                'priority' => $ticket->priority ?? 'URGENT',
                'validated_at' => now(),
            ]);

            // Assign technicians
            foreach ($onDutyTechnicians as $tech) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'technician_id' => $tech->id,
                    'assigned_by' => null, // System auto
                    'assigned_at' => now(),
                ]);
            }

            // Log history
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1, // Admin / System
                'status' => 'ASSIGNED',
                'action' => 'AUTO_DISPATCH_TIMEOUT',
                'notes' => "SISTEM AUTO-DISPOSISI: Tiket melebihi batas waktu SLA disposisi ({$timeoutMinutes} menit). Otomatis dialihkan ke teknisi piket.",
            ]);

            $count++;
            $this->info("Tiket #{$ticket->ticket_number} berhasil didisposisikan otomatis ke " . $onDutyTechnicians->count() . " teknisi.");
        }

        $this->info("Proses selasai. Total {$count} tiket didisposisikan otomatis.");

        return Command::SUCCESS;
    }
}
