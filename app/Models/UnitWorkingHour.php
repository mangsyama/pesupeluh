<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitWorkingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'supporting_unit_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'auto_disposition_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function supportingUnit()
    {
        return $this->belongsTo(SupportingUnit::class);
    }
}
