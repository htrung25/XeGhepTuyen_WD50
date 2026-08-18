<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorHistory extends Model
{
    use HasUuids;

    protected $table = 'operator_history';

    protected $fillable = [
        'operator_id',
        'actor_user_id',
        'category',
        'action',
        'severity',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'metadata',
        'dedupe_key',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function scopeForOperator(Builder $query, string $operatorId): Builder
    {
        return $query->where('operator_id', $operatorId);
    }
}
