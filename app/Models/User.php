<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'face_descriptor', 'role_id', 'room_id', 'supporting_unit_id', 'phone_number', 'telegram_chat_id', 'is_active', 'nip', 'username', 'profile_photo_path', 'approved_by', 'approved_at', 'page_permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'face_descriptor' => 'array',
            'page_permissions' => 'array',
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
            'role_id' => 'integer',
            'room_id' => 'integer',
            'supporting_unit_id' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    protected ?array $memoizedPermissions = null;

    /**
     * Get the effective page permissions for this user.
     * User override takes priority; falls back to role defaults.
     */
    public function getEffectivePermissions(): array
    {
        if ($this->memoizedPermissions !== null) {
            return $this->memoizedPermissions;
        }

        // 1. If user has custom override saved in DB, use that exact array
        if ($this->page_permissions !== null && is_array($this->page_permissions)) {
            return $this->memoizedPermissions = $this->page_permissions;
        }

        // 2. Default Administrator role permissions if no override
        if ((int) $this->role_id === 1) {
            return $this->memoizedPermissions = [
                'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index',
                'service-management.rooms', 'service-management.categories', 'service-management.supporting-units',
                'users.approvals', 'users.index', 'admin.wa-gateway.index', 'admin.qr-code.index', 'settings.index', 'design-system.index',
            ];
        }

        // 3. Fall back to role defaults
        $role = $this->role ?? $this->load('role')->role;
        if ($role && is_array($role->page_permissions)) {
            return $this->memoizedPermissions = $role->page_permissions;
        }

        return $this->memoizedPermissions = [];
    }

    /**
     * Check if user has access to a specific page permission key.
     */
    public function hasPageAccess(string $permissionKey): bool
    {
        return in_array($permissionKey, $this->getEffectivePermissions(), true);
    }

    public function supportingUnit()
    {
        return $this->belongsTo(SupportingUnit::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Accessor & Mutator for UUID to ensure it is always lowercase.
     */
    protected function uuid(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? strtolower($value) : null,
            set: fn (?string $value) => $value ? strtolower($value) : null,
        );
    }

    /**
     * Route notifications for the Telegram channel.
     */
    public function routeNotificationForTelegram()
    {
        return $this->telegram_chat_id;
    }
}

