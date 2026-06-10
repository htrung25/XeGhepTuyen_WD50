<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function findByPhone(string $phone): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(string $id, array $data): bool;
    public function paginate(array $filters = []): LengthAwarePaginator;
}
