<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'page_permissions'];

    protected function casts(): array
    {
        return [
            'page_permissions' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
