<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SanitationTicket extends Model
{
    use HasFactory;

    protected $table = 'sanitation_tickets';

    protected $fillable = [
        'uuid',
        'ticket_number',
        'toilet_id',
        'problem_description',
        'urgency',
        'status',
        'reporter_name',
        'reporter_phone',
        'is_anonymous',
        'evidence_before',
        'evidence_after',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_formatted',
        'created_at_relative',
        'resolved_at_formatted',
        'location_point',
        'location_building',
        'location_floor',
        'room_name',
        'category_name',
        'resolver_name',
    ];

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
            if (empty($ticket->ticket_number)) {
                $dateStr = now()->format('Ymd');
                $countToday = self::whereDate('created_at', now()->toDateString())->count();
                $ticket->ticket_number = sprintf('PS-%s-%03d', $dateStr, $countToday + 1);
            }
        });
    }

    public function toilet()
    {
        return $this->belongsTo(SanitationToilet::class, 'toilet_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->format('d M Y - H:i') : null;
    }

    public function getResolvedAtFormattedAttribute(): ?string
    {
        return $this->resolved_at ? $this->resolved_at->format('d M Y - H:i') : null;
    }

    public function getLocationPointAttribute(): ?string
    {
        return $this->toilet?->name;
    }

    public function getLocationBuildingAttribute(): ?string
    {
        return $this->toilet?->building;
    }

    public function getLocationFloorAttribute(): ?string
    {
        return $this->toilet?->floor;
    }

    public function getCategoryNameAttribute(): string
    {
        return 'Aduan Sanitasi & Toilet';
    }

    public function getRoomNameAttribute(): ?string
    {
        return $this->toilet?->name;
    }

    public function getCreatedAtRelativeAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    public function getResolverNameAttribute(): ?string
    {
        return $this->resolver?->name;
    }
}
