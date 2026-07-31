<?php

use App\Models\Booking;
use Laravel\Sanctum\Sanctum;

it('cho phép operator xem booking thuộc xe của mình', function () {
    $operator = makeOperatorWithRevenue(1, 0);
    $booking = Booking::whereHas('trip.vehicle', fn ($query) => $query->where('operator_id', $operator->id))->firstOrFail();

    Sanctum::actingAs($operator->user);

    $this->getJson("/api/operator/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $booking->id);
});
