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
        return $this->belongsTo(IssueCategory::class, 'category_id');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    public function histories()
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id')->orderBy('id', 'asc');
    }

    /**
     * Accessor & Mutator for UUID to ensure it is always lowercase.
     */
    protected function uuid(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? strtolower($value) : null,
            set: fn (?string $value) => $value ? strtolower($value) : null,
        );
    }

    /**
     * Correct timezone on dates read from the database.
     *
     * SQL Server stores WITA time values but labels them as +00:00 (UTC).
     * shiftTimezone re-labels the timezone WITHOUT changing the time digits,
     * so that Carbon comparisons (e.g. diffInSeconds with now()) are accurate.
     */
    protected function asDateTime($value)
    {
        $date = parent::asDateTime($value);
        if ($date && in_array($date->timezone->getName(), ['UTC', '+00:00'])) {
            return $date->shiftTimezone(config('app.timezone', 'Asia/Makassar'));
        }
        return $date;
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        return \Illuminate\Support\Carbon::instance($date)->setTimezone($tz)->format('Y-m-d\TH:i:sP');
    }

    /**
     * Check if this ticket is forwarded from SIPUAS.
     */
    public function isSipuas(): bool
    {
        $desc = $this->problem_description ?? '';
        return str_contains($desc, '[DISPOSISI ADUAN PUBLIK SIPUAS]')
            || str_contains($desc, 'SIPUAS')
            || str_starts_with($this->reporter?->username ?? '', 'sipuas_');
    }

    /**
     * Get clean problem description (extracts only complaint text from SIPUAS payload).
     */
    public function getCleanDescription(): string
    {
        $desc = $this->problem_description ?? '';
        if (!$this->isSipuas()) {
            return $desc;
        }

        if (preg_match('/---\s*URAIAN KELUHAN FASILITAS\s*---/i', $desc)) {
            $parts = preg_split('/---\s*URAIAN KELUHAN FASILITAS\s*---/i', $desc, 2);
            $afterUraian = $parts[1] ?? '';
            $subParts = preg_split('/---\s*CATATAN VERIFIKATOR(?:\s*\([^)]*\))?\s*---/i', $afterUraian, 2);
            return trim($subParts[0] ?? '');
        }

        if (preg_match('/---\s*CATATAN VERIFIKATOR(?:\s*\([^)]*\))?\s*---/i', $desc)) {
            $parts = preg_split('/---\s*CATATAN VERIFIKATOR(?:\s*\([^)]*\))?\s*---/i', $desc, 2);
            return trim($parts[0] ?? '');
        }

        return $desc;
    }

    /**
     * Get display reporter name (citizen name for SIPUAS, or reporter->name).
     */
    public function getDisplayReporterName(): string
    {
        if (!$this->isSipuas()) {
            return $this->reporter?->name ?? '-';
        }

        $desc = $this->problem_description ?? '';
        if (preg_match('/Pelapor:\s*(.*?)(?:\s+Diteruskan|\s+---|[\r\n]|$)/i', $desc, $matches)) {
            $raw = trim($matches[1]);
            $clean = preg_replace('/\(HP:\s*[^)]+\)/i', '', $raw);
            $clean = preg_replace('/\(Publik via SIPUAS\)/i', '', $clean);
            $clean = trim($clean);
            if (!empty($clean)) {
                return $clean . ' (Masyarakat)';
            }
        }

        return ($this->reporter?->name ?? 'Masyarakat') . ' (Masyarakat)';
    }

    /**
     * Get display reporter phone number.
     */
    public function getDisplayReporterPhone(): string
    {
        if (!$this->isSipuas()) {
            return $this->reporter?->phone_number ?? '-';
        }

        $desc = $this->problem_description ?? '';
        if (preg_match('/Pelapor:\s*(.*?)(?:\s+Diteruskan|\s+---|[\r\n]|$)/i', $desc, $matches)) {
            $raw = trim($matches[1]);
            if (preg_match('/\(HP:\s*([^)]+)\)/i', $raw, $hpMatch)) {
                return trim($hpMatch[1]);
            }
        }

        if (preg_match('/\(HP:\s*([^)]+)\)/i', $desc, $matches)) {
            return trim($matches[1]);
        }

        return $this->reporter?->phone_number ?? '-';
    }

    public function getCleanDescriptionAttribute(): string
    {
        return $this->getCleanDescription();
    }

    public function getDisplayReporterNameAttribute(): string
    {
        return $this->getDisplayReporterName();
    }

    public function getDisplayReporterPhoneAttribute(): string
    {
        return $this->getDisplayReporterPhone();
    }
}
