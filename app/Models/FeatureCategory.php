<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeatureCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['feature_id', 'name', 'description'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function unitFeature()
    {
        return $this->belongsTo(UnitFeature::class, 'feature_id');
    }
}
