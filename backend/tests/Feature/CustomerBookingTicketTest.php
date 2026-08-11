<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\SeatMap;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function makeCustomerTicketBooking(): Booking
{
    $operator = makeOperatorWithRevenue(1, 0);
    $booking = Booking::query()->whereHas(
        'trip.vehicle',
        fn ($query) => $query->where('operator_id', $operator->id)
    )->firstOrFail();
    $booking->update([
        'qr_token' => Str::random(32),
        'pickup_address' => 'Bến xe Mỹ Đình, Hà Nội',
        'dropoff_address' => 'Bến xe Niệm Nghĩa, Hải Phòng',
    ]);

    $seat = SeatMap::create([
        'trip_id' => $booking->trip_id,
        'seat_code' => 'A1',
        'seat_type' => 'standard',
        'price' => 150000,
        'status' => 'booked',
    ]);
    BookingPassenger::create([
        'booking_id' => $booking->id,
        'seat_map_id' => $seat->id,
        'full_name' => 'Khách Test',
        'phone' => '0397570630',
        'is_primary' => true,
    ]);

    return $booking->fresh();
}

it('trả đúng contract vé để trang xác nhận hiển thị tuyến xe tài xế và ghế', function () {
    $booking = makeCustomerTicketBooking();
    Sanctum::actingAs($booking->user, ['*'], 'sanctum');
    Sanctum::actingAs($booking->user, ['*'], 'customer');

    $this->getJson("/api/customer/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.can_access_ticket', true)
        ->assertJsonPath('data.can_continue_payment', false)
        ->assertJsonPath('data.trip.route', 'Hà Nội → Hải Phòng')
        ->assertJsonPath('data.trip.vehicle.plate', $booking->trip->vehicle->plate_number)
        ->assertJsonPath('data.trip.driver_name', $booking->trip->driver->user->full_name)
        ->assertJsonPath('data.passengers.0.seat_code', 'A1');
});

it('cho chủ vé tải PDF chứa vé điện tử', function () {
    $booking = makeCustomerTicketBooking();
    Sanctum::actingAs($booking->user, ['*'], 'sanctum');
    Sanctum::actingAs($booking->user, ['*'], 'customer');

    $response = $this->get("/api/customer/bookings/{$booking->id}/ticket.pdf");

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload("ve-{$booking->booking_code}.pdf");
    expect($response->getContent())->toStartWith('%PDF');
});

it('trả QR dạng data URI không phụ thuộc filesystem public của cloud', function () {
    $booking = makeCustomerTicketBooking();
    Sanctum::actingAs($booking->user, ['*'], 'sanctum');
    Sanctum::actingAs($booking->user, ['*'], 'customer');

    $response = $this->getJson("/api/customer/bookings/{$booking->id}/qr")
        ->assertOk();

    expect($response->json('data.qr_code'))
        ->toStartWith('data:image/svg+xml;base64,');
});

it('chặn QR và PDF khi vé online vẫn đang chờ thanh toán', function () {
    $booking = makeCustomerTicketBooking();
    $booking->update([
        'booking_status' => 'pending',
        'payment_status' => 'unpaid',
        'payment_method' => 'momo',
        'expires_at' => now()->addMinutes(10),
    ]);
    Sanctum::actingAs($booking->user, ['*'], 'sanctum');
    Sanctum::actingAs($booking->user, ['*'], 'customer');

    $this->getJson("/api/customer/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.can_access_ticket', false)
        ->assertJsonPath('data.can_continue_payment', true)
        ->assertJsonPath('data.qr_code', null);

    $this->getJson("/api/customer/bookings/{$booking->id}/qr")
        ->assertStatus(409)
        ->assertJsonPath('code', 'PAYMENT_REQUIRED');
    $this->getJson("/api/customer/bookings/{$booking->id}/ticket.pdf")
        ->assertStatus(409)
        ->assertJsonPath('code', 'PAYMENT_REQUIRED');
});

it('cho vé tiền mặt đã xác nhận xem QR dù trạng thái tiền vẫn là unpaid', function () {
    $booking = makeCustomerTicketBooking();
    $booking->update([
        'booking_status' => 'confirmed',
        'payment_status' => 'unpaid',
        'payment_method' => 'cash',
        'expires_at' => null,
    ]);
    Sanctum::actingAs($booking->user, ['*'], 'sanctum');
    Sanctum::actingAs($booking->user, ['*'], 'customer');

    $this->getJson("/api/customer/bookings/{$booking->id}/qr")->assertOk();
});

it('không cho khách tải vé của người khác', function () {
    $booking = makeCustomerTicketBooking();
    $other = User::factory()->create(['role' => UserRoleEnum::Customer]);
    Sanctum::actingAs($other, ['*'], 'sanctum');
    Sanctum::actingAs($other, ['*'], 'customer');

    $this->getJson("/api/customer/bookings/{$booking->id}/ticket.pdf")
        ->assertNotFound()
        ->assertJsonPath('message', 'Vé không tồn tại');
});
