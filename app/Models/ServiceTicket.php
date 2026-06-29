<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTicket extends Model
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'ticket_number',
        'reporter_id',
        'room_id',
        'category_id',
        'problem_description',
        'priority',
        'validated_by',
        'validated_at',
        'status',
        'responded_at',
        'resolved_at',
        'pending_reason',
        'paused_duration_seconds',
        'last_paused_at',
        'completion_notes',
    ];

    protected $casts = [
        'validated_at'  => 'datetime',
        'responded_at'  => 'datetime',
        'resolved_at'   => 'datetime',
        'last_paused_at'=> 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
        'reporter_id'   => 'integer',
        'room_id'       => 'integer',
        'category_id'   => 'integer',
        'validated_by'  => 'integer',
        'paused_duration_seconds' => 'integer',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function category()
    {
        return $this->belongsTo(FeatureCategory::class, 'category_id');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }
}
