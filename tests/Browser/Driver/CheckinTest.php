<?php

namespace Tests\Browser\Driver;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SeatMap;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class CheckinTest extends DuskTestCase
{
    // ── Helper: tạo booking với qr_token cho trip ─────────────────────────────

    private function createBookingWithQrToken(int $tripIndex = 2): Booking
    {
        $trip     = $this->getTodayTrip($tripIndex);
        $customer = User::where('phone', '0900000003')->firstOrFail();
        $seat     = SeatMap::where('trip_id', $trip->id)
                           ->where('status', 'available')
                           ->first();

        $booking = Booking::create([
            'user_id'         => $customer->id,
            'trip_id'         => $trip->id,
            'booking_code'    => 'HNHP' . today()->format('ymd') . '555',
            'contact_name'    => 'Nguyễn Checkin Test',
            'contact_phone'   => '0902222222',
            'passenger_count' => 1,
            'pickup_stop_id'  => $trip->route->stops()->where('is_pickup', true)->value('id'),
            'dropoff_stop_id' => $trip->route->stops()->where('is_dropoff', true)->value('id'),
            'subtotal'        => $trip->price,
            'discount_amount' => 0,
            'final_amount'    => $trip->price,
            'booking_status'  => BookingStatus::Confirmed,
            'payment_status'  => BookingPaymentStatus::Paid,
            'qr_token'        => Str::uuid()->toString(), // unique QR token
            'expires_at'      => now()->addHours(2),
        ]);

        if ($seat) {
            $seat->update(['status' => 'booked']);
            $booking->passengers()->create([
                'full_name'   => 'Nguyễn Checkin Test',
                'phone'       => '0902222222',
                'seat_map_id' => $seat->id,
                'is_primary'  => true,
            ]);
        }

        return $booking;
    }

    // ── TEST 3-1 ─────────────────────────────────────────────────────────────

    public function test_driver_checkin_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $trip = $this->getTodayTrip(2);

            $browser->visit("/driver/checkin/{$trip->id}")
                    ->pause(2000)
                    ->assertPathIs("/driver/checkin/{$trip->id}")
                    ->assertSee('Quét QR check-in')
                    ->assertSee('Hoặc nhập mã thủ công')
                    ->assertPresent('input[placeholder="Nhập mã QR token..."]');
        });
    }

    // ── TEST 3-2 ─────────────────────────────────────────────────────────────

    public function test_driver_can_checkin_passenger_manually(): void
    {
        $this->browse(function (Browser $browser) {
            $booking = $this->createBookingWithQrToken();
            $trip    = $booking->trip;

            $this->loginAsDriver($browser);

            $browser->visit("/driver/checkin/{$trip->id}")
                    ->pause(2000);

            // Type QR token into manual input
            $browser->type('input[placeholder="Nhập mã QR token..."]', $booking->qr_token)
                    ->press('Xác nhận')
                    ->pause(2500);

            // Success result shown
            $browser->assertSee('Check-in thành công!')
                    ->assertSee('Nguyễn Checkin Test');

            // DB: booking_status updated to checked_in
            $this->assertDatabaseHas('bookings', [
                'id'             => $booking->id,
                'booking_status' => 'checked_in',
            ]);
        });
    }

    // ── TEST 3-3 ─────────────────────────────────────────────────────────────

    public function test_invalid_qr_token_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $trip = $this->getTodayTrip(2);

            $browser->visit("/driver/checkin/{$trip->id}")
                    ->pause(2000);

            $browser->type('input[placeholder="Nhập mã QR token..."]', 'INVALID_TOKEN_123')
                    ->press('Xác nhận')
                    ->pause(2000)
                    ->assertSee('Mã QR không hợp lệ');
        });
    }

    // ── TEST 3-4 ─────────────────────────────────────────────────────────────

    public function test_already_checked_in_passenger_shows_warning(): void
    {
        $this->browse(function (Browser $browser) {
            $booking = $this->createBookingWithQrToken();
            $trip    = $booking->trip;

            $this->loginAsDriver($browser);

            $browser->visit("/driver/checkin/{$trip->id}")
                    ->pause(2000);

            // First check-in — success
            $browser->type('input[placeholder="Nhập mã QR token..."]', $booking->qr_token)
                    ->press('Xác nhận')
                    ->pause(2500)
                    ->assertSee('Check-in thành công!');

            // Click "Quét tiếp" to reset scanner
            $browser->press('Quét tiếp')
                    ->pause(500);

            // Second check-in with same token — booking is now checked_in status
            $browser->type('input[placeholder="Nhập mã QR token..."]', $booking->qr_token)
                    ->press('Xác nhận')
                    ->pause(2000)
                    ->assertSee('Đã check-in'); // BookingStatus::CheckedIn label
        });
    }
}
