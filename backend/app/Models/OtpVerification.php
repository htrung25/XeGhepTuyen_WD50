<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasUuids, MassPrunable;

    public const PURPOSE_REGISTER = 'customer_register';

    protected $fillable = [
        'phone',
        'purpose',
        'token_hash',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::query()->where(function (Builder $query): void {
            $query->where('expires_at', '<', now()->subDay())
                ->orWhere('consumed_at', '<', now()->subDay());
        });
    }
}
