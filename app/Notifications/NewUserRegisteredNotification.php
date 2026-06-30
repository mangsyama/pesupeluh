<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public User $registrant)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
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

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
