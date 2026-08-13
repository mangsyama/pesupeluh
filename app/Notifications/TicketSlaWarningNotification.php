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

class TicketSlaWarningNotification extends Notification
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

        if ($notifiable instanceof User && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'ticket',
            'priority' => 'URGENT',
            'title' => 'Peringatan Validasi Laporan',
            'message' => "Laporan #{$this->ticket->ticket_number} belum divalidasi melebihi 15 menit. Mohon segera lakukan validasi & penugasan teknisi.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid], false),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'ticket',
            'priority' => 'URGENT',
            'title' => 'Peringatan Validasi Laporan',
            'message' => "Laporan #{$this->ticket->ticket_number} belum divalidasi melebihi 15 menit. Mohon segera lakukan validasi & penugasan teknisi.",
            'ticket_id' => $this->ticket->id,
            'route' => route('reports-management.show', ['ticket' => $this->ticket->uuid], false),
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;
        if (!$chatId) return null;

        return TelegramMessage::create()
            ->to($chatId)
            ->content("⚠️ *Peringatan Validasi Laporan*\n\nLaporan *#{$this->ticket->ticket_number}* belum divalidasi melebihi 15 menit!\nMohon segera lakukan validasi & penugasan teknisi.")
            ->button('Validasi Tiket Sekarang', route('reports-management.show', ['ticket' => $this->ticket->uuid]));
    }

    public function toWaGateway($notifiable): ?string
    {
        return "⚠️ *Peringatan Validasi Laporan*\n\nLaporan *#{$this->ticket->ticket_number}* belum divalidasi melebihi 15 menit!\nMohon segera lakukan validasi: " . route('reports-management.show', ['ticket' => $this->ticket->uuid]);
    }
}
