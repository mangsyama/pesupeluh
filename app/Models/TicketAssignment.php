<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['ticket_id', 'technician_id', 'assigned_at'];

    protected $casts = [
        'ticket_id' => 'integer',
        'technician_id' => 'integer',
        'assigned_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'ticket_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
