<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'birth_date',
        'password',
        'role',
        'admin_role_id',
        'avatar_url',
        'zalo_user_id',
        'fcm_token',
        'is_verified',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'role'           => UserRole::class,
            'is_verified'    => 'boolean',
            'is_active'      => 'boolean',
            'last_login_at'  => 'datetime',
            'birth_date'     => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }

    public function operator(): HasOne
    {
        return $this->hasOne(Operator::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isDriver(): bool
    {
        return $this->role === UserRole::Driver;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::Operator;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Admin có vai trò super (bỏ qua mọi kiểm tra quyền). */
    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->adminRole?->is_super;
    }

    /** Kiểm tra admin hiện tại có quyền theo key (AdminPermission). */
    public function hasPermission(string $key): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        return (bool) $this->adminRole?->hasPermission($key);
    }

    /**
     * Danh sách key quyền hiệu lực (để trả về cho FE gate menu/nút).
     * Super admin nhận toàn bộ catalog.
     */
    public function permissionKeys(): array
    {
        if (! $this->isAdmin()) {
            return [];
        }

        if ($this->adminRole?->is_super) {
            return \App\Enums\AdminPermission::values();
        }

        return $this->adminRole?->permissions ?? [];
    }
}
