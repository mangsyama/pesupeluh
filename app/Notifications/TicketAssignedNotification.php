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

class TicketAssignedNotification extends Notification implements ShouldQueue
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

        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        // WhatsApp ONLY for Technician (if wa_notify_enabled)
        if ($notifiable instanceof User && $notifiable->wa_notify_enabled !== false && $notifiable->isTechnician() && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $isUrgent = ($this->ticket->priority === 'URGENT');
        return [
            'type'      => 'ticket',
            'priority'  => $isUrgent ? 'URGENT' : 'HIGH',
            'title'     => $isUrgent ? '🔴 PENUGASAN TIKET URGENT' : 'Tugas Baru Diterima',
            'message'   => "Anda telah ditugaskan untuk menangani laporan #{$this->ticket->ticket_number}.",
            'ticket_id' => $this->ticket->id,
            'route'     => route('reports-management.show', ['ticket' => $this->ticket->uuid], false),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;
        if (!$chatId) return null;

        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';
        $isUrgent = ($this->ticket->priority === 'URGENT');

        $header = $isUrgent 
            ? "🔴 *PENUGASAN TIKET URGENT*" 
            : "🛠️ *Tugas Baru Diterima*";

        return TelegramMessage::create()
            ->to($chatId)
            ->content("{$header}\n\nAnda telah ditugaskan menangani tiket *#{$this->ticket->ticket_number}*.\n\n*Ruangan:* {$roomName}\n*Kategori:* {$categoryName}\n*Deskripsi:* {$this->ticket->problem_description}")
            ->button('Lihat Pengerjaan Tiket', route('reports-management.show', ['ticket' => $this->ticket->uuid]));
    }

    public function toWaGateway($notifiable): ?string
    {
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $categoryName = $this->ticket->category?->name ?? 'Kategori';
        $link = route('reports-management.show', ['ticket' => $this->ticket->uuid]);

        $priorityText = match($this->ticket->priority) {
            'URGENT' => '🔴 *URGENT (Mendesak)*',
            default  => '🟢 *RUTIN (Standar)*',
        };

        return "🛠️ *Tugas Baru Diterima*\n\n"
             . "Anda telah ditugaskan untuk menangani laporan *#{$this->ticket->ticket_number}*.\n\n"
             . "• *Prioritas:* {$priorityText}\n"
             . "• *Ruangan:* {$roomName}\n"
             . "• *Kategori:* {$categoryName}\n"
             . "• *Deskripsi Kendala:* {$this->ticket->problem_description}\n\n"
             . "Lihat & Kerjakan Tiket:\n{$link}";
    }


    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
