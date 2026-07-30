<?php

namespace App\Notifications;

use App\Channels\WaGatewayChannel;
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

        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        // WhatsApp ONLY for Technician (role_id === 6)
        if ($notifiable instanceof User && (int) $notifiable->role_id === 6 && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $isEmergency = ($this->ticket->priority === 'EMERGENCY');
        return [
            'type'      => 'ticket',
            'priority'  => $isEmergency ? 'EMERGENCY' : 'HIGH',
            'title'     => $isEmergency ? '🚨 PENUGASAN DARURAT (EMERGENCY) INSTAN' : 'Tugas Baru Diterima',
            'message'   => $isEmergency 
                ? "Laporan Darurat #{$this->ticket->ticket_number} OTOMATIS DITUGASKAN KEPADA ANDA! Segera luncur ke lokasi!"
                : "Anda telah ditugaskan untuk menangani laporan #{$this->ticket->ticket_number}.",
            'ticket_id' => $this->ticket->id,
            'route'     => route('reports-management.show', ['ticket' => $this->ticket->uuid]),
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
        $isEmergency = ($this->ticket->priority === 'EMERGENCY');

        $header = $isEmergency 
            ? "🚨 *PENUGASAN DARURAT (CODE RED)*" 
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
        $isEmergency = ($this->ticket->priority === 'EMERGENCY');

        if ($isEmergency) {
            return "🚨 *PENUGASAN DARURAT (EMERGENCY / CODE RED)*\n\n"
                 . "Laporan Darurat *#{$this->ticket->ticket_number}* telah dibuat!\n\n"
                 . "• *Ruangan:* {$roomName}\n"
                 . "• *Kategori:* {$categoryName}\n"
                 . "• *Deskripsi Kendala:* {$this->ticket->problem_description}\n\n"
                 . "⚠️ *SELURUH TEKNISI PIKET LANGSUNG DITUGASKAN. SEGERA MENUJU LOKASI!*\n\n"
                 . "Lihat Tiket:\n{$link}";
        }

        $priorityText = match($this->ticket->priority) {
            'URGENT' => '🚨 *URGENT (Mendesak)*',
            'HIGH'   => '⚡ *TINGGI (Segera)*',
            default  => '📋 *BIASA (Normal)*',
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
