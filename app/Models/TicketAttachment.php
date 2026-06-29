<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'uploaded_by' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
