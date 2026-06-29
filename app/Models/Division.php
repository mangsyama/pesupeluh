<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'description'];

    public function supportingUnits()
    {
        return $this->hasMany(SupportingUnit::class);
    }
}
