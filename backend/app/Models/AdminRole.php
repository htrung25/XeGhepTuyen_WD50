<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_super',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_super' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Vai trò super bỏ qua mọi kiểm tra; còn lại xét danh sách quyền. */
    public function hasPermission(string $key): bool
    {
        return $this->is_super || in_array($key, $this->permissions ?? [], true);
    }
}
