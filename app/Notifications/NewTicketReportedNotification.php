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

class NewTicketReportedNotification extends Notification implements ShouldQueue
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

        // Send to WhatsApp via WA Gateway (Local microservice or Fonnte API)
        if ($notifiable instanceof User && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'ticket',
            'title' => 'Laporan Baru Masuk',
            'message' => "Laporan baru #{$this->ticket->ticket_number} telah dibuat dan membutuhkan validasi Anda.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'ticket',
            'title' => 'Laporan Baru Masuk',
            'message' => "Laporan baru #{$this->ticket->ticket_number} telah dibuat dan membutuhkan validasi Anda.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;

        if (!$chatId) {
            return null;
        }

        $reporterName = $this->ticket->reporter?->name ?? 'Reporter';
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';

        return TelegramMessage::create()
            ->to($chatId)
            ->content("⚠️ *Laporan Baru Masuk*\n\nTiket *#{$this->ticket->ticket_number}* membutuhkan validasi Anda.\n\n*Pelapor:* {$reporterName}\n*Ruangan:* {$roomName}\n*Kategori:* {$categoryName}\n*Deskripsi:* {$this->ticket->problem_description}")
            ->button('Validasi & Tugaskan', route('reports-management.show', ['ticket' => $this->ticket->uuid]));
    }

    public function toWaGateway($notifiable): ?string
    {
        $reporterName = $this->ticket->reporter?->name ?? 'Pelapor';
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';
        $link = route('reports-management.show', ['ticket' => $this->ticket->uuid]);

        return "⚠️ *Laporan Baru Masuk*\n\n"
             . "Tiket *#{$this->ticket->ticket_number}* membutuhkan validasi Anda.\n\n"
             . "• *Pelapor:* {$reporterName}\n"
             . "• *Ruangan:* {$roomName}\n"
             . "• *Kategori:* {$categoryName}\n"
             . "• *Deskripsi:* {$this->ticket->problem_description}\n\n"
             . "Validasi & Tugaskan:\n{$link}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
