<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with('user:id,full_name,phone,email');

        $query->when(
            $filters['user_id'] ?? null,
            fn ($query, $userId) => $query->where('user_id', $userId)
        );
        $query->when(
            $filters['action'] ?? null,
            fn ($query, $action) => $query->where('action', $action)
        );
        $query->when(
            $filters['model_type'] ?? null,
            fn ($query, $modelType) => $query->where('model_type', $modelType)
        );

        if (! empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                CarbonImmutable::createFromFormat('Y-m-d', $filters['date_from'])
                    ->startOfDay()
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                CarbonImmutable::createFromFormat('Y-m-d', $filters['date_to'])
                    ->endOfDay()
            );
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('action', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('model_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function find(string $id): ?AuditLog
    {
        return AuditLog::query()
            ->with('user:id,full_name,phone,email')
            ->find($id);
    }
}
