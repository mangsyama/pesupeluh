<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class NewUserRegisteredNotification extends Notification
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

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'user',
            'title' => 'Pendaftaran Baru',
            'message' => "Pengguna {$this->registrant->name} telah mendaftar dan menunggu verifikasi.",
            'user_id' => $this->registrant->id,
            'route' => route('users.approvals'),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'user',
            'title' => 'Pendaftaran Baru',
            'message' => "Pengguna {$this->registrant->name} telah mendaftar dan menunggu verifikasi.",
            'user_id' => $this->registrant->id,
            'route' => route('users.approvals'),
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
            ->button('Lihat Persetujuan', route('users.approvals'));
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

