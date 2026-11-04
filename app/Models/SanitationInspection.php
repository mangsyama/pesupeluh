<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanitationInspection extends Model
{
    use HasFactory;

    protected $table = 'sanitation_inspections';

    protected $fillable = [
        'toilet_id',
        'inspector_id',
        'water_flush_ok',
        'floor_mirror_clean',
        'supplies_ready',
        'air_fragrance_ok',
        'status',
        'photo',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'water_flush_ok' => 'boolean',
        'floor_mirror_clean' => 'boolean',
        'supplies_ready' => 'boolean',
        'air_fragrance_ok' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'location',
        'checked_at',
        'shift',
        'inspector',
        'inspection_date',
    ];

    public function toilet()
    {
        return $this->belongsTo(SanitationToilet::class, 'toilet_id');
    }

    public function inspectorUser()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function getLocationAttribute(): string
    {
        if ($this->toilet) {
            return "{$this->toilet->name} ({$this->toilet->building} - {$this->toilet->floor})";
        }
        return 'Toilet Fasilitas Sanitasi';
    }

    public function getCheckedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y - H:i') : '-';
    }

    public function getInspectorAttribute(): string
    {
        return $this->inspectorUser?->name ?? 'Gede Ariasa (Pengawas Kesling)';
    }

    public function getInspectionDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d') : now()->format('Y-m-d');
    }

    public function getShiftAttribute(): string
    {
        $hour = $this->created_at ? (int) $this->created_at->format('H') : (int) now()->format('H');
        if ($hour >= 7 && $hour < 14) {
            return 'Pagi (07:00 - 14:00)';
        } elseif ($hour >= 14 && $hour < 21) {
            return 'Siang (14:00 - 21:00)';
        }
        return 'Malam (21:00 - 07:00)';
    }
}

