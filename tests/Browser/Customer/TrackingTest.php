<?php

namespace Tests\Browser\Customer;

use App\Enums\TripStatus;
use App\Models\Trip;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class TrackingTest extends DuskTestCase
{
    // ── TEST 6-1 ─────────────────────────────────────────────────────────────

    public function test_customer_can_view_tracking_page(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a confirmed booking and set trip to in_progress
            $booking = $this->createConfirmedBooking();
            $booking->trip->update(['status' => TripStatus::InProgress]);

            $this->loginAsCustomer($browser);

            $browser->visit("/bookings/{$booking->id}/track")
                    ->pause(3000)
                    ->assertPathContains('/track');

            // Map container should be present
            $browser->assertPresent('#map')
                    ->assertSee('Hà Nội'); // route info
        });
    }

    // ── TEST 6-2 ─────────────────────────────────────────────────────────────

    public function test_public_tracking_works_without_login(): void
    {
        $this->browse(function (Browser $browser) {
            $trip = $this->getTodayTrip();

            // Public tracking endpoint via trip tracking code
            $browser->visit("/track/{$trip->tracking_code}")
                    ->pause(2000);

            // Should see tracking info without redirect to login
            $browser->assertDontSee('Đăng nhập')
                    ->assertPathContains('/track');
        });
    }
}
