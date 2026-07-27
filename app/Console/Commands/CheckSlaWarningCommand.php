<?php

namespace App\Console\Commands;

use App\Models\ServiceTicket;
use App\Models\User;
use App\Notifications\TicketSlaWarningNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckSlaWarningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa tiket yang belum divalidasi selama > 15 menit dan kirim notifikasi warning validasi ke Kepala Unit & Admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limitTime = now()->subMinutes(15);

        $unvalidatedTickets = ServiceTicket::with(['category.supportingUnit'])
            ->where('status', 'PENDING_VALIDATION')
            ->whereNull('validated_at')
            ->where('created_at', '<=', $limitTime)
            ->get();

        $count = 0;
        foreach ($unvalidatedTickets as $ticket) {
            // Check if warning notification was already sent to avoid duplicate alerts
            $alreadyNotified = DB::table('notifications')
                ->where('data', 'LIKE', '%"ticket_id":' . $ticket->id . '%')
                ->where(function ($q) {
                    $q->where('data', 'LIKE', '%Peringatan Validasi%')
                      ->orWhere('data', 'LIKE', '%PERINGATAN SLA%');
                })
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $supportingUnitId = $ticket->category?->supporting_unit_id;

            $recipients = User::where('is_active', 1)
                ->where(function ($query) use ($supportingUnitId) {
                    $query->where('role_id', 1); // Admin

                    if ($supportingUnitId) {
                        $query->orWhere(function ($q) use ($supportingUnitId) {
                            $q->where('role_id', 5)->where('supporting_unit_id', $supportingUnitId); // Ka Unit
                        });
                    }
                })
                ->get();

            if ($recipients->isNotEmpty()) {
                try {
                    Notification::send($recipients, new TicketSlaWarningNotification($ticket));
                    $count++;
                } catch (\Throwable $e) {
                    Log::error("Gagal mengirim peringatan validasi 15 menit untuk tiket #{$ticket->ticket_number}: " . $e->getMessage());
                }
            }
        }

        $this->info("Berhasil memeriksa warning validasi laporan. Notifikasi terkirim untuk {$count} tiket.");
        return Command::SUCCESS;
    }
}
