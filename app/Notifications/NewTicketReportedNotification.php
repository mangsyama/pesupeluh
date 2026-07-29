<?php

namespace App\Notifications;

use App\Channels\WaGatewayChannel;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class NewTicketReportedNotification extends Notification
{
    use Queueable;

    public function __construct(public ServiceTicket $ticket)
    {
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Send to Telegram if bot token is configured and the user has a telegram_chat_id
        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        // WhatsApp ONLY for Kepala Unit (role_id === 5)
        if ($notifiable instanceof User && (int) $notifiable->role_id === 5 && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    private function getNotificationDetails($notifiable): array
    {
        $roleId = (int) ($notifiable->role_id ?? 0);
        $ticketNum = $this->ticket->ticket_number;
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $reporterName = $this->ticket->reporter?->name ?? 'Staf';
        $unitName = $this->ticket->category?->supportingUnit?->name ?? 'IPSRS';
        $isEmergency = ($this->ticket->priority === 'EMERGENCY') || (bool) ($this->ticket->is_emergency ?? false);

        if ($isEmergency) {
            if ($roleId === 6) { // Teknisi
                return [
                    'title' => '🚨 PENUGASAN DARURAT (EMERGENCY) INSTAN',
                    'message' => "Laporan Darurat #{$ticketNum} di {$roomName} OTOMATIS DITUGASKAN KEPADA ANDA! Segera menuju ke lokasi!",
                    'priority' => 'EMERGENCY',
                    'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
                ];
            }
            if ($roleId === 5) { // Kepala Unit
                return [
                    'title' => '🚨 INFORMASI PENUGASAN DARURAT (AUTO-DISPATCH)',
                    'message' => "Laporan Darurat #{$ticketNum} di {$roomName} telah OTOMATIS DIDISPOSISI ke Teknisi On-Duty.",
                    'priority' => 'EMERGENCY',
                    'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
                ];
            }
            if ($roleId === 7) { // Kepala Ruangan
                return [
                    'title' => '🚨 LAPORAN DARURAT DI RUANGAN ANDA',
                    'message' => "Laporan Darurat #{$ticketNum} di {$roomName} telah diajukan staf dan LANGSUNG DIDISPOSISIKAN ke Teknisi.",
                    'priority' => 'EMERGENCY',
                    'route' => route('reports.show', ['ticket' => $this->ticket->uuid]),
                ];
            }
            // Admin & default
            return [
                'title' => '🚨 LAPORAN DARURAT (EMERGENCY) MASUK',
                'message' => "Laporan Darurat #{$ticketNum} di {$roomName} diajukan dan langsung diteruskan ke Teknisi.",
                'priority' => 'EMERGENCY',
                'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
            ];
        }

        if ($roleId === 6) { // Teknisi Non-Emergency (Auto-dispatched on Off Hours)
            $ticketPriority = strtoupper($this->ticket->priority ?? 'ROUTINE');
            $isUrgent = ($ticketPriority === 'URGENT');
            return [
                'title' => $isUrgent ? '⚠️ PENUGASAN TIKET URGENT' : 'Tugas Baru Diterima (Disposisi Otomatis)',
                'message' => "Anda menerima penugasan laporan #{$ticketNum} di {$roomName} (" . ($isUrgent ? 'Status URGENT' : 'Luar Jam Kerja') . ").",
                'priority' => $isUrgent ? 'URGENT' : 'HIGH',
                'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
            ];
        }

        if ($roleId === 7) { // Kepala Ruangan
            return [
                'title' => 'Laporan Diajukan Staf',
                'message' => "Staf {$reporterName} di ruangan {$roomName} telah mengajukan laporan #{$ticketNum} ke unit {$unitName}.",
                'priority' => 'NORMAL',
                'route' => route('reports.show', ['ticket' => $this->ticket->uuid]),
            ];
        }

        // Kepala Unit & Admin
        return [
            'title' => 'Laporan Baru Masuk',
            'message' => "Laporan baru #{$ticketNum} telah dibuat dan membutuhkan validasi Anda.",
            'priority' => 'NORMAL',
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
        ];
    }

    public function toDatabase($notifiable): array
    {
        $details = $this->getNotificationDetails($notifiable);
        return [
            'type' => 'ticket',
            'title' => $details['title'],
            'message' => $details['message'],
            'priority' => $details['priority'],
            'ticket_id' => $this->ticket->id,
            'route' => $details['route'],
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $details = $this->getNotificationDetails($notifiable);
        return new BroadcastMessage([
            'type' => 'ticket',
            'title' => $details['title'],
            'message' => $details['message'],
            'priority' => $details['priority'],
            'ticket_id' => $this->ticket->id,
            'route' => $details['route'],
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;
        if (!$chatId) return null;

        $details = $this->getNotificationDetails($notifiable);
        $reporterName = $this->ticket->reporter?->name ?? 'Reporter';
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';

        return TelegramMessage::create()
            ->to($chatId)
            ->content("⚠️ *{$details['title']}*\n\n{$details['message']}\n\n*Pelapor:* {$reporterName}\n*Ruangan:* {$roomName}\n*Kategori:* {$categoryName}\n*Deskripsi:* {$this->ticket->problem_description}")
            ->button('Lihat Tiket', $details['route']);
    }

    public function toWaGateway($notifiable): ?string
    {
        $details = $this->getNotificationDetails($notifiable);
        $reporterName = $this->ticket->reporter?->name ?? 'Pelapor';
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';

        return "⚠️ *{$details['title']}*\n\n"
             . "{$details['message']}\n\n"
             . "• *Pelapor:* {$reporterName}\n"
             . "• *Ruangan:* {$roomName}\n"
             . "• *Kategori:* {$categoryName}\n"
             . "• *Deskripsi:* {$this->ticket->problem_description}\n\n"
             . "Lihat Tiket:\n{$details['route']}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
