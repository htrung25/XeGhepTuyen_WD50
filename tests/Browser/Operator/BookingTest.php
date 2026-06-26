<?php

namespace Tests\Browser\Operator;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SeatMap;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class BookingTest extends DuskTestCase
{
    // ── Helper ────────────────────────────────────────────────────────────────

    private function createTestBooking(): Booking
    {
        $customer = User::where('phone', '0900000003')->firstOrFail();
        $trip     = $this->getTodayTrip(0);
        $seat     = SeatMap::where('trip_id', $trip->id)->where('status', 'available')->first();

        $booking = Booking::create([
            'user_id'         => $customer->id,
            'trip_id'         => $trip->id,
            'booking_code'    => 'HNHP' . today()->format('ymd') . '888',
            'contact_name'    => 'Khách Hàng Test',
            'contact_phone'   => '0900000003',
            'passenger_count' => 1,
            'pickup_stop_id'  => $trip->route->stops()->where('is_pickup', true)->value('id'),
            'dropoff_stop_id' => $trip->route->stops()->where('is_dropoff', true)->value('id'),
            'subtotal'        => $trip->price,
            'discount_amount' => 0,
            'final_amount'    => $trip->price,
            'booking_status'  => BookingStatus::Confirmed,
            'payment_status'  => BookingPaymentStatus::Paid,
            'expires_at'      => now()->addHours(2),
        ]);

        if ($seat) {
            $seat->update(['status' => 'booked']);
            $booking->passengers()->create([
                'full_name'   => 'Khách Hàng Test',
                'phone'       => '0900000003',
                'seat_map_id' => $seat->id,
                'is_primary'  => true,
            ]);
        }

        return $booking;
    }

    // ── TEST 4-1 ─────────────────────────────────────────────────────────────

    public function test_operator_bookings_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/bookings')
                    ->pause(2500)
                    ->assertPathIs('/operator/bookings')
                    ->assertSee('Quản lý đặt chỗ')
                    ->assertSee('Tất cả')
                    ->assertSee('Đã xác nhận')
                    ->assertSee('Chờ TT')
                    ->assertSee('Hoàn thành')
                    ->assertSee('Đã huỷ')
                    ->assertSee('Tìm kiếm');
        });
    }

    // ── TEST 4-2 ─────────────────────────────────────────────────────────────

    public function test_operator_bookings_shows_booking_list(): void
    {
        // Create a confirmed booking so the table has data
        $booking = $this->createTestBooking();

        $this->browse(function (Browser $browser) use ($booking) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/bookings')
                    ->pause(2500);

            // Table headers
            $browser->assertSee('Mã đặt')
                    ->assertSee('Khách hàng')
                    ->assertSee('Chuyến đi')
                    ->assertSee('Tổng tiền')
                    ->assertSee('Trạng thái')
                    ->assertSee('Ngày đặt');

            // Booking data from seeder
            $browser->assertSee('Khách Hàng Test')
                    ->assertSee('Hà Nội')
                    ->assertSee('Đã xác nhận');
        });
    }

    // ── TEST 4-3 ─────────────────────────────────────────────────────────────

    public function test_operator_can_filter_bookings_by_status(): void
    {
        $this->createTestBooking();

        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/bookings')
                    ->pause(2500);

            // Click "Đã xác nhận" tab to filter
            $browser->press('Đã xác nhận')
                    ->pause(2000);

            // Should see confirmed bookings
            $browser->assertPathIs('/operator/bookings')
                    ->assertSee('Khách Hàng Test');

            // Click "Đã huỷ" tab — no bookings in this status
            $browser->press('Đã huỷ')
                    ->pause(2000)
                    ->assertSee('Không có đặt chỗ nào');
        });
    }
}
