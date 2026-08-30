<?php

namespace App\Notifications;

use App\Channels\WaGatewayChannel;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class NewTicketReportedNotification extends Notification implements ShouldQueue, ShouldBroadcastNow
{
    use Queueable;

    public function __construct(public ServiceTicket $ticket)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];

        if (!($notifiable instanceof User) || $notifiable->system_notify_enabled !== false) {
            $channels = ['database', 'broadcast'];
        }

        // Send to Telegram if bot token is configured and the user has a telegram_chat_id
        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        // WhatsApp for Disposisi & Admin roles (if wa_notify_enabled)
        if ($notifiable instanceof User && $notifiable->wa_notify_enabled !== false && ($notifiable->canDisposisi() || $notifiable->isAdmin()) && $notifiable->phone_number) {
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
        $ticketPriority = strtoupper($this->ticket->priority ?? 'ROUTINE');
        $isUrgent = ($ticketPriority === 'URGENT');

        if ($roleId === 6) { // Teknisi (Auto-dispatched on Off Hours)
            return [
                'title' => $isUrgent ? '🔴 PENUGASAN TIKET URGENT' : 'Tugas Baru Diterima (Disposisi Otomatis)',
                'message' => "Anda menerima penugasan laporan #{$ticketNum} di {$roomName} (" . ($isUrgent ? 'Status URGENT' : 'Luar Jam Kerja') . ").",
                'priority' => $isUrgent ? 'URGENT' : 'HIGH',
                'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid], false),
            ];
        }

        if ($roleId === 7) { // Kepala Ruangan
            return [
                'title' => $isUrgent ? '🔴 Laporan Urgent Diajukan Staf' : 'Laporan Diajukan Staf',
                'message' => "Staf {$reporterName} di ruangan {$roomName} telah mengajukan laporan #{$ticketNum} ke unit {$unitName}.",
                'priority' => $isUrgent ? 'URGENT' : 'NORMAL',
                'route' => route('reports.show', ['ticket' => $this->ticket->uuid], false),
            ];
        }

        // Kepala Unit & Admin
        return [
            'title' => $isUrgent ? '🔴 Laporan Urgent Baru Masuk' : 'Laporan Baru Masuk',
            'message' => "Laporan baru #{$ticketNum} telah dibuat dan membutuhkan validasi Anda.",
            'priority' => $isUrgent ? 'URGENT' : 'NORMAL',
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid], false),
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
        $fullUrl = url($details['route']);

        return TelegramMessage::create()
            ->to($chatId)
            ->content("⚠️ *{$details['title']}*\n\n{$details['message']}\n\n*Pelapor:* {$reporterName}\n*Ruangan:* {$roomName}\n*Kategori:* {$categoryName}\n*Deskripsi:* {$this->ticket->problem_description}")
            ->button('Lihat Tiket', $fullUrl);
    }

    public function toWaGateway($notifiable): ?string
    {
        $details = $this->getNotificationDetails($notifiable);
        $reporterName = $this->ticket->reporter?->name ?? 'Pelapor';
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';
        $fullUrl = url($details['route']);

        return "⚠️ *{$details['title']}*\n\n"
             . "{$details['message']}\n\n"
             . "• *Pelapor:* {$reporterName}\n"
             . "• *Ruangan:* {$roomName}\n"
             . "• *Kategori:* {$categoryName}\n"
             . "• *Deskripsi:* {$this->ticket->problem_description}\n\n"
             . "Lihat Tiket:\n{$fullUrl}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
