<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['supporting_unit_id', 'name', 'description'];

    public function supportingUnit()
    {
        return $this->belongsTo(SupportingUnit::class);
    }

    public function serviceTickets()
    {
        return $this->hasMany(ServiceTicket::class, 'category_id');
    }
}
