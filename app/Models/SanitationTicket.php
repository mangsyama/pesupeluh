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
        'started_at',
        'started_by',
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
        'started_by' => 'integer',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_formatted',
        'created_at_relative',
        'started_at_formatted',
        'resolved_at_formatted',
        'location_point',
        'location_building',
        'location_floor',
        'room_name',
        'category_name',
        'starter_name',
        'resolver_name',
        'evidence_before_list',
        'evidence_after_list',
    ];

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
            if (empty($ticket->ticket_number)) {
                $dateStr = now()->format('Ymd');
                $prefix = "PS-{$dateStr}-";
                $existingNumbers = self::where('ticket_number', 'LIKE', "{$prefix}%")
                    ->pluck('ticket_number');

                $maxSeq = 0;
                foreach ($existingNumbers as $num) {
                    if (preg_match('/-(\d+)$/', (string) $num, $matches)) {
                        $seq = (int) $matches[1];
                        if ($seq > $maxSeq) {
                            $maxSeq = $seq;
                        }
                    }
                }

                $ticket->ticket_number = sprintf('%s%03d', $prefix, $maxSeq + 1);
            }
        });
    }

    public function toilet()
    {
        return $this->belongsTo(SanitationToilet::class, 'toilet_id');
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getStarterNameAttribute(): ?string
    {
        return $this->starter?->name;
    }

    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->format('d M Y - H:i') : null;
    }

    public function getStartedAtFormattedAttribute(): ?string
    {
        if ($this->started_at) {
            return $this->started_at->format('d M Y - H:i');
        }
        $status = strtoupper((string) $this->status);
        if (in_array($status, ['DIKERJAKAN', 'IN_PROGRESS', 'SELESAI', 'RESOLVED', 'CLOSED'], true)) {
            $fallback = $this->updated_at ?? $this->resolved_at ?? $this->created_at;
            return $fallback ? $fallback->format('d M Y - H:i') : null;
        }
        return null;
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

    /**
     * Parse stored media column (single path string or JSON array string) into a clean array of URLs.
     */
    public static function parseMediaList(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $trimmed = trim($raw);
        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => is_string($item) && trim($item) !== ''));
            }
        }

        return [$trimmed];
    }

    public function getEvidenceBeforeAttribute($value): ?string
    {
        $list = self::parseMediaList($value);
        return $list[0] ?? null;
    }

    public function getEvidenceAfterAttribute($value): ?string
    {
        $list = self::parseMediaList($value);
        return $list[0] ?? null;
    }

    public function getEvidenceBeforeListAttribute(): array
    {
        return self::parseMediaList($this->attributes['evidence_before'] ?? null);
    }

    public function getEvidenceAfterListAttribute(): array
    {
        return self::parseMediaList($this->attributes['evidence_after'] ?? null);
    }
}
