<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportingUnit extends Model
{
    public $timestamps = false;
    protected $fillable = ['type', 'name', 'slug', 'description', 'status'];

    public function issueCategories()
    {
        return $this->hasMany(IssueCategory::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
