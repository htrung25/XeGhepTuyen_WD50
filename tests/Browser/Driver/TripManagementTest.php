<?php

namespace Tests\Browser\Driver;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class TripManagementTest extends DuskTestCase
{
    // ── TEST 2-1 ─────────────────────────────────────────────────────────────

    public function test_driver_dashboard_shows_today_trips(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $browser->assertPathIs('/driver/dashboard')
                    ->assertSee('Chuyến hôm nay')
                    ->assertSee('GIỜ')        // uppercase tracking-wider CSS
                    ->assertSee('TUYẾN')
                    ->assertSee('SỐ KHÁCH')
                    ->assertSee('XE')
                    ->assertSee('TRẠNG THÁI')
                    ->assertSee('06:')         // 06:00 trip from seeder
                    ->assertSee('10:')         // 10:00 trip from seeder
                    ->assertSee('14:');        // 14:00 trip from seeder
        });
    }

    // ── TEST 2-2 ─────────────────────────────────────────────────────────────

    public function test_driver_can_view_trip_detail(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $trip = $this->getTodayTrip(0); // first trip (06:00)

            // Click link from dashboard to trip detail
            $browser->visit("/driver/trips/{$trip->id}")
                    ->pause(2500)
                    ->assertPathIs("/driver/trips/{$trip->id}")
                    ->assertSee('Hà Nội')
                    ->assertSee('Hải Phòng')
                    ->assertSee('30A-12345');  // vehicle plate from seeder
        });
    }

    // ── TEST 2-3 ─────────────────────────────────────────────────────────────

    public function test_driver_can_start_trip(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $trip = $this->getTodayTrip(0);

            $browser->visit("/driver/trips/{$trip->id}")
                    ->pause(2500);

            // "🚦 Bắt đầu chuyến" button visible for scheduled trip
            $browser->assertSee('Bắt đầu chuyến')
                    ->press('Bắt đầu chuyến')
                    ->pause(600);

            // Confirm modal appears
            $browser->assertSee('Bắt đầu chuyến?')
                    ->assertSee('Xác nhận bắt đầu chuyến');

            // Click confirm "Bắt đầu" inside modal — use last-of-type to pick modal confirm
            $browser->press('Bắt đầu')
                    ->pause(2000);

            // Trip status updated to in_progress
            $browser->assertSee('Đang chạy')
                    ->assertSee('Kết thúc chuyến');

            // Verify DB updated
            $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'in_progress']);
        });
    }

    // ── TEST 2-4 ─────────────────────────────────────────────────────────────

    public function test_driver_can_complete_trip(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $trip = $this->getTodayTrip(1); // use 2nd trip to avoid conflict

            // First start the trip
            $trip->update(['status' => 'in_progress']);

            $browser->visit("/driver/trips/{$trip->id}")
                    ->pause(2500);

            // Click "Kết thúc chuyến"
            $browser->assertSee('Kết thúc chuyến')
                    ->press('Kết thúc chuyến')
                    ->pause(600);

            // Modal appears
            $browser->assertSee('Kết thúc chuyến?')
                    ->press('Kết thúc')
                    ->pause(2000);

            // Redirects to dashboard after completion
            $browser->assertSee('Hoàn thành');

            $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'completed']);
        });
    }

    // ── TEST 2-5 ─────────────────────────────────────────────────────────────

    public function test_trip_manifest_shows_passenger_list(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a confirmed booking for the first trip
            $trip     = $this->getTodayTrip(0);
            $customer = User::where('phone', '0900000003')->firstOrFail();
            $seat     = SeatMap::where('trip_id', $trip->id)
                               ->where('status', 'available')
                               ->first();

            $booking = Booking::create([
                'user_id'         => $customer->id,
                'trip_id'         => $trip->id,
                'booking_code'    => 'HNHP' . today()->format('ymd') . '777',
                'contact_name'    => 'Nguyễn Hành Khách',
                'contact_phone'   => '0901111111',
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
                    'full_name'   => 'Nguyễn Hành Khách',
                    'phone'       => '0901111111',
                    'seat_map_id' => $seat->id,
                    'is_primary'  => true,
                ]);
            }

            $this->loginAsDriver($browser);

            $browser->visit("/driver/trips/{$trip->id}")
                    ->pause(2500)
                    ->assertSee('Nguyễn Hành Khách')  // passenger name
                    ->assertSee('Đón:')               // pickup info label
                    ->assertSee('Mỹ Đình')            // pickup stop from seeder
                    ->assertSee('Trả:');              // dropoff info label
        });
    }
}
