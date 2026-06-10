<?php

namespace App\Repositories\Contracts;

use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function findById(string $id): ?Trip;
    public function findByTrackingCode(string $code): ?Trip;
    public function search(array $filters): Collection;
    public function findManyForSearch(array $ids): array;
    public function findAll(array $filters = []): Collection;
    public function findByDriver(string $driverId, array $filters = []): Collection;
    public function findByOperator(string $operatorId, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Trip;
    public function update(string $id, array $data): bool;
    public function getActiveTripsWithLocation(): Collection;
    public function findInProgress(): Collection;
}
