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

class TicketStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ServiceTicket $ticket,
        public string $status,
        public ?string $customNotes = null
    ) {
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

        if ($notifiable instanceof User && $notifiable->wa_notify_enabled !== false && $notifiable->phone_number) {
            $channels[] = WaGatewayChannel::class;
        }

        return $channels;
    }

    private function getNotificationDetails($notifiable): array
    {
        $isManagement = $notifiable instanceof User && ($notifiable->isAdmin() || $notifiable->canDisposisi() || $notifiable->isDirector());
        $targetRoute = $isManagement 
            ? route('reports-management.show', ['ticket' => $this->ticket->uuid], false)
            : route('reports.show', ['ticket' => $this->ticket->uuid], false);

        $ticketNum = $this->ticket->ticket_number;
        $roomName = $this->ticket->room?->name ?? 'Ruangan';

        switch ($this->status) {
            case 'ASSIGNED':
                $title = "Tiket Ditugaskan";
                $message = "Tiket #{$ticketNum} telah divalidasi dan ditugaskan ke teknisi.";
                break;
            case 'ARRIVED':
            case 'IN_PROGRESS':
                $title = "Teknisi Tiba di Lokasi";
                $message = "Teknisi telah tiba di lokasi {$roomName} dan mulai mengerjakan tiket #{$ticketNum}.";
                break;
            case 'PENDING':
            case 'PAUSED':
                $title = "Pekerjaan Ditangguhkan (Pending)";
                $reason = $this->customNotes ?: ($this->ticket->pending_reason ?: 'Menunggu suku cadang/alat');
                $message = "Pekerjaan tiket #{$ticketNum} ditangguhkan. Alasan: \"{$reason}\"";
                break;
            case 'RESUMED':
                $title = "Pekerjaan Dilanjutkan Kembali";
                $message = "Pekerjaan perbaikan tiket #{$ticketNum} di lokasi {$roomName} telah dilanjutkan kembali.";
                break;
            case 'COMPLETED':
                $title = "Tiket Selesai Dikerjakan";
                $message = "Laporan #{$ticketNum} telah selesai dikerjakan oleh teknisi.";
                break;
            case 'CANCEL':
                $title = "Tiket Dibatalkan";
                $reason = $this->customNotes ?: ($this->ticket->completion_notes ?: 'Dibatalkan oleh petugas');
                $message = "Tiket #{$ticketNum} telah dibatalkan. Alasan: \"{$reason}\"";
                break;
            default:
                $title = "Update Status Tiket";
                $message = "Status tiket #{$ticketNum} diubah menjadi {$this->status}.";
                break;
        }

        return [
            'title' => $title,
            'message' => $message,
            'route' => $targetRoute,
        ];
    }

    public function toDatabase($notifiable): array
    {
        $details = $this->getNotificationDetails($notifiable);
        return [
            'type' => 'ticket',
            'title' => $details['title'],
            'message' => $details['message'],
            'ticket_id' => $this->ticket->id,
            'status' => $this->status,
            'route' => $details['route'],
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $details = $this->getNotificationDetails($notifiable);
        return new BroadcastMessage([
            'type' => 'ticket',
            'title' => $details['title'],
            'message' => $details['message'],
            'ticket_id' => $this->ticket->id,
            'status' => $this->status,
            'route' => $details['route'],
        ]);
    }

    public function toTelegram($notifiable)
    {
        $chatId = $notifiable instanceof User ? $notifiable->telegram_chat_id : null;
        if (!$chatId) return null;

        $details = $this->getNotificationDetails($notifiable);
        return TelegramMessage::create()
            ->to($chatId)
            ->content("🔔 *{$details['title']}*\n\n{$details['message']}")
            ->button('Lihat Tiket', $details['route']);
    }

    public function toWaGateway($notifiable): ?string
    {
        $details = $this->getNotificationDetails($notifiable);
        return "🔔 *{$details['title']}*\n\n{$details['message']}\n\nLihat tiket: {$details['route']}";
    }
}
