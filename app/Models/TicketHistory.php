<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'status',
        'action',
        'notes',
        'duration_seconds',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'ticket_id' => 'integer',
        'user_id' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
