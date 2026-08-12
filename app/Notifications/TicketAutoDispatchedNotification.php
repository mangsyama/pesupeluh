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

class TicketAutoDispatchedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ServiceTicket $ticket,
        public User $technician
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $roomName = $this->ticket->room?->name ?? 'Ruangan';

        return [
            'type'      => 'ticket',
            'priority'  => 'HIGH',
            'title'     => '⚡ Disposisi Otomatis Sistem (5 Menit)',
            'message'   => "Laporan #{$this->ticket->ticket_number} di {$roomName} terlewat 5 menit tanpa disposisi. Sistem telah mengalihkan secara otomatis ke Teknisi {$this->technician->name}.",
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

        return TelegramMessage::create()
            ->to($chatId)
            ->content("⚡ *Disposisi Otomatis Sistem (5 Menit)*\n\nLaporan *#{$this->ticket->ticket_number}* di *{$roomName}* terlewat 5 menit tanpa disposisi petugas.\n\nSistem telah mengalihkan tiket secara otomatis ke *Teknisi {$this->technician->name}* (beban penugasan tersedikit).")
            ->button('Lihat Tiket', route('reports-management.show', ['ticket' => $this->ticket->uuid]));
    }

    public function toWaGateway($notifiable): ?string
    {
        $roomName = $this->ticket->room?->name ?? 'Ruangan';
        $link = route('reports-management.show', ['ticket' => $this->ticket->uuid]);

        return "⚡ *Disposisi Otomatis Sistem*\n\n"
             . "Laporan *#{$this->ticket->ticket_number}* di *{$roomName}* terlewat 5 menit pada jam kerja tanpa disposisi petugas.\n\n"
             . "Sistem telah mengalihkan tiket secara otomatis ke *Teknisi {$this->technician->name}*.\n\n"
             . "Lihat Tiket:\n{$link}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
