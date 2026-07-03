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

        // Send to Telegram if bot token and chat ID are configured
        if (config('services.telegram.token') && config('services.telegram.chat_id')) {
            // To prevent duplicate messages in the same group chat when notifying multiple admins,
            // we only send via Telegram for the first administrator in the collection.
            if (!($notifiable instanceof User) || $this->shouldSendTelegram($notifiable)) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    protected function shouldSendTelegram(User $notifiable): bool
    {
        static $firstAdminId = null;
        if ($firstAdminId === null) {
            $firstAdminId = User::whereHas('role', fn ($query) => $query->where('name', 'ADMINISTRATOR'))
                ->orderBy('id')
                ->value('id');
        }
        return $notifiable->id === $firstAdminId;
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
        $chatId = $notifiable instanceof User && isset($notifiable->telegram_chat_id)
            ? $notifiable->telegram_chat_id
            : config('services.telegram.chat_id');

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

