<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    public function findById(string $id): ?Booking;
    public function findByCode(string $code): ?Booking;
    public function findByQrToken(string $token): ?Booking;
    public function findByUser(string $userId, array $filters = []): LengthAwarePaginator;
    public function findByTrip(string $tripId, array $filters = []): LengthAwarePaginator;
    public function findByOperator(string $operatorId, array $filters = []): LengthAwarePaginator;
    public function findAllForAdmin(array $filters = []): LengthAwarePaginator;
    public function create(array $data): Booking;
    public function update(string $id, array $data): bool;
    public function updateStatus(string $id, string $status): bool;
    public function countPendingByUser(string $userId): int;
}
