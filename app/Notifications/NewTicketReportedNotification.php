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

        if ($roleId === 7) { // Kepala Ruangan (Spectator)
            return [
                'title' => 'Laporan Diajukan Staf',
                'message' => "Staf {$reporterName} di ruangan {$roomName} telah mengajukan laporan #{$ticketNum} ke unit {$unitName}.",
                'route' => route('reports.show', ['ticket' => $this->ticket->uuid]),
            ];
        }

        // Kepala Unit & Admin
        return [
            'title' => 'Laporan Baru Masuk',
            'message' => "Laporan baru #{$ticketNum} telah dibuat dan membutuhkan validasi Anda.",
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
