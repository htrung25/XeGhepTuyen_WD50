<?php

namespace App\Repositories\Contracts;

use App\Models\PartnerApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PartnerApplicationRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(string $id): ?PartnerApplication;

    public function create(array $data): PartnerApplication;
}
