<?php

namespace App\Repositories;

use App\Models\PartnerApplication;
use App\Repositories\Contracts\PartnerApplicationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PartnerApplicationRepository implements PartnerApplicationRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return PartnerApplication::query()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(function ($sub) use ($search) {
                $sub->where('company_name', 'LIKE', "%{$search}%")
                    ->orWhere('representative_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('tax_code', 'LIKE', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage);
    }

    public function find(string $id): ?PartnerApplication
    {
        return PartnerApplication::find($id);
    }

    public function create(array $data): PartnerApplication
    {
        return PartnerApplication::create($data);
    }
}
