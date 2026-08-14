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

    /**
     * Correct timezone on dates read from the database.
     * SQL Server stores WITA time values but labels them as +00:00 (UTC).
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
}
