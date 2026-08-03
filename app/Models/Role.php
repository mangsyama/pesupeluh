<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const ADMINISTRATOR = 1;
    public const DIREKTUR = 2;
    public const KEPALA_BIDANG = 3;
    public const KEPALA_BAGIAN = 4;
    public const KEPALA_SEKSI = 5;
    public const KEPALA_SUB_BAGIAN = 6;
    public const KEPALA_INSTALASI = 7;
    public const SEKRETARIS_INSTALASI = 8;
    public const PJ_RUANGAN = 9;
    public const TEKNISI = 10;
    public const STAFF = 11;

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
