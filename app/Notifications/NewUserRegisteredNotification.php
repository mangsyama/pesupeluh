<?php

namespace App\Notifications;

use App\Channels\WaGatewayChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
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

        if (config('services.telegram.token') && $notifiable instanceof User && $notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        // WhatsApp ONLY for Admin
        if ($notifiable instanceof User && $notifiable->isAdmin() && $notifiable->phone_number) {
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
            'route' => route('users.approvals.show', $this->registrant->uuid ?? $this->registrant->id, false),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'user',
            'title' => 'Pendaftaran Baru',
            'message' => "Pengguna {$this->registrant->name} telah mendaftar dan menunggu verifikasi.",
            'user_id' => $this->registrant->id,
            'route' => route('users.approvals.show', $this->registrant->uuid ?? $this->registrant->id, false),
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;
        if (!$chatId) return null;

        return TelegramMessage::create()
            ->to($chatId)
            ->content("👤 *Pendaftaran User Baru*\n\nPengguna *{$this->registrant->name}* ({$this->registrant->email}) telah mendaftar dan membutuhkan verifikasi Anda.")
            ->button('Verifikasi User', route('users.approvals.show', $this->registrant->uuid ?? $this->registrant->id));
    }

    public function toWaGateway($notifiable): ?string
    {
        $link = route('users.approvals.show', $this->registrant->uuid ?? $this->registrant->id);
        $nip = $this->registrant->nip ?? '-';
        $phone = $this->registrant->phone_number ?? '-';

        return "👤 *Pendaftaran User Baru*\n\n"
             . "Halo Admin, ada pendaftar baru yang membutuhkan verifikasi Anda:\n\n"
             . "• *Nama:* {$this->registrant->name}\n"
             . "• *Email:* {$this->registrant->email}\n"
             . "• *NIP:* {$nip}\n"
             . "• *No. HP:* {$phone}\n\n"
             . "Silakan lakukan verifikasi melalui sistem:\n{$link}";
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
