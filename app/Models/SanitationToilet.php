<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanitationToilet extends Model
{
    use HasFactory;

    protected $table = 'sanitation_toilets';

    protected $fillable = [
        'code',
        'name',
        'building',
        'floor',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['target_url'];

    protected static function booted()
    {
        static::creating(function ($toilet) {
            if (empty($toilet->code)) {
                // Strip prefix "Gedung" or "Building" to get actual letter/name (e.g. "Gedung A" -> "A", "Gedung B" -> "B")
                $bName = trim(preg_replace('/^(gedung|building)\s+/i', '', trim($toilet->building ?: 'A')));
                $bCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $bName ?: 'A'), 0, 2));
                if (empty($bCode)) {
                    $bCode = 'A';
                }

                $fCode = '1';
                if (preg_match('/(\d+)/', $toilet->floor ?: '', $m)) {
                    $fCode = $m[1];
                } elseif (preg_match('/basement/i', $toilet->floor ?: '')) {
                    $fCode = 'B';
                }

                $basePrefix = sprintf('QR-TLT-%s%s', $bCode, $fCode);
                $seq = 1;
                do {
                    $candidate = sprintf('%s-%02d', $basePrefix, $seq);
                    $seq++;
                } while (self::where('code', $candidate)->exists());

                $toilet->code = $candidate;
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(SanitationTicket::class, 'toilet_id');
    }

    public function inspections()
    {
        return $this->hasMany(SanitationInspection::class, 'toilet_id');
    }

    public function getTargetUrlAttribute(): string
    {
        return url('/services/kesling/aduan-sanitasi?toilet=' . $this->code);
    }
}
