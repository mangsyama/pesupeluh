<?php

namespace App\Notifications;

use App\Channels\WaGatewayChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class NewUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $registrant)
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
            'type' => 'user',
            'title' => 'Pendaftaran Baru',
            'message' => "Pengguna {$this->registrant->name} telah mendaftar dan menunggu verifikasi.",
            'user_id' => $this->registrant->id,
            'route' => route('users.approvals.show', $this->registrant->uuid),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'user',
            'title' => 'Pendaftaran Baru',
            'message' => "Pengguna {$this->registrant->name} telah mendaftar dan menunggu verifikasi.",
            'user_id' => $this->registrant->id,
            'route' => route('users.approvals.show', $this->registrant->uuid),
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;

        if (!$chatId) {
            return null;
        }

        return TelegramMessage::create()
            ->to($chatId)
            ->content("🔔 *Pendaftaran Pengguna Baru*\n\nPengguna *{$this->registrant->name}* telah mendaftar dan menunggu verifikasi.\n\n*NIP:* {$this->registrant->nip}\n*Email:* {$this->registrant->email}\n*Username:* {$this->registrant->username}")
            ->button('Lihat Persetujuan', route('users.approvals.show', $this->registrant->uuid));
    }

    public function toWaGateway($notifiable): ?string
    {
        $link = route('users.approvals.show', $this->registrant->uuid);

        return "🔔 *Pendaftaran Pengguna Baru*\n\n"
             . "Pengguna *{$this->registrant->name}* telah mendaftar dan menunggu verifikasi Anda.\n\n"
             . "• *NIP:* " . ($this->registrant->nip ?: '-') . "\n"
             . "• *Email:* {$this->registrant->email}\n"
             . "• *Username:* " . ($this->registrant->username ?: '-') . "\n\n"
             . "Lihat & Proses Persetujuan:\n{$link}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

