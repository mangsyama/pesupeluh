<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportingUnit extends Model
{
    public $timestamps = false;
    protected $fillable = ['division_id', 'name', 'description', 'status'];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function unitFeatures()
    {
        return $this->hasMany(UnitFeature::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
