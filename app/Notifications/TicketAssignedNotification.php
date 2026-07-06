<?php

namespace App\Notifications;

use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class TicketAssignedNotification extends Notification
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

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'ticket',
            'title' => 'Tugas Baru Diterima',
            'message' => "Anda telah ditugaskan untuk menangani laporan #{$this->ticket->ticket_number}.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
            'priority' => $this->ticket->priority ?? 'ROUTINE',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'ticket',
            'title' => 'Tugas Baru Diterima',
            'message' => "Anda telah ditugaskan untuk menangani laporan #{$this->ticket->ticket_number}.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
            'priority' => $this->ticket->priority ?? 'ROUTINE',
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;

        if (!$chatId) {
            return null;
        }

        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';
        $priority = $this->ticket->priority ?? 'ROUTINE';
        $isUrgent = strtoupper($priority) === 'URGENT';

        $header = $isUrgent 
            ? "🚨🔴 *[DARURAT - SEGERA RESPON]* 🔴🚨" 
            : "🛠️ *Penugasan Laporan Baru (Rutin)*";

        $priorityText = $isUrgent ? "🚨 URGENT (DARURAT)" : "🟢 ROUTINE (RUTIN)";

        $message = TelegramMessage::create()
            ->to($chatId)
            ->content("{$header}\n\nAnda telah ditugaskan untuk menangani tiket *#{$this->ticket->ticket_number}*.\n\n*Prioritas:* {$priorityText}\n*Ruangan:* {$roomName}\n*Kategori:* {$categoryName}\n*Deskripsi Masalah:* {$this->ticket->problem_description}")
            ->button('Lihat Detail Pekerjaan', route('reports-management.show', ['ticket' => $this->ticket->uuid]));

        if ($isUrgent && config('services.telegram_urgent.token')) {
            $message->token(config('services.telegram_urgent.token'));
        }

        return $message;
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
