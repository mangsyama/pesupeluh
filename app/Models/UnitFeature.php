<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitFeature extends Model
{
    public $timestamps = false;
    protected $fillable = ['supporting_unit_id', 'name'];

    public function supportingUnit()
    {
        return $this->belongsTo(SupportingUnit::class);
    }

    public function featureCategories()
    {
        return $this->hasMany(FeatureCategory::class, 'feature_id');
    }
}
