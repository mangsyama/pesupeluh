<?php

namespace App\Events;

use App\Models\ServiceTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticketId;
    public $ticketNumber;
    public $status;
    public $actionType;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ServiceTicket $ticket, string $actionType = 'updated', ?string $customMessage = null)
    {
        $this->ticketId = $ticket->id;
        $this->ticketNumber = $ticket->ticket_number;
        $this->status = $ticket->status;
        $this->actionType = $actionType; // 'created', 'updated', 'assigned', 'status_changed'
        
        $actionLabels = [
            'created' => 'dibuat',
            'assigned' => 'didisposisi/ditugaskan',
            'status_changed' => 'diperbarui statusnya',
            'updated' => 'diperbarui',
        ];

        $label = $actionLabels[$actionType] ?? 'diperbarui';
        $this->message = $customMessage ?? "Laporan #{$ticket->ticket_number} telah {$label}.";
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('tickets'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'TicketRealtimeUpdated';
    }
}
