<?php

namespace Tests\Browser;

use App\Enums\BookingStatus;
use App\Enums\BookingPaymentStatus;
use App\Models\Booking;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\DuskTestSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DuskTestSeeder::class);
    }

    // ── Auth helpers ─────────────────────────────────────────────────────────

    /**
     * Inject Sanctum token into localStorage so the Vue SPA treats the
     * browser session as authenticated — avoids going through the login form
     * in every test that doesn't specifically test auth.
     */
    protected function loginAsCustomer(Browser $browser, string $phone = '0900000003'): void
    {
        $user  = User::where('phone', $phone)->firstOrFail();
        $token = $user->createToken('dusk')->plainTextToken;

        $userJson = addslashes(json_encode([
            'id'         => $user->id,
            'full_name'  => $user->full_name,
            'phone'      => $user->phone,
            'email'      => $user->email,
            'avatar_url' => null,
        ]));

        $browser->visit('/')
                ->script([
                    "localStorage.setItem('customer_token', '{$token}')",
                    "localStorage.setItem('customer_user', '{$userJson}')",
                ]);

        $browser->visit('/home')->pause(1500);
    }

    protected function loginAsDriver(Browser $browser, string $phone = '0900000002'): void
    {
        // Use the real login form — localStorage injection is unreliable for driver
        // because the 401 interceptor in client.ts fires a redirect that races with
        // the script() injection.  The form-based approach is proven stable.
        $page = new \Tests\Browser\Pages\Driver\LoginPage;
        $browser->visit($page);
        $page->loginAs($browser, $phone, 'Test@123456');
        $browser->waitFor('aside.bg-gray-900', 8)->pause(500);
    }

    protected function loginAsOperator(Browser $browser, string $phone = '0900000001'): void
    {
        $user     = User::where('phone', $phone)->firstOrFail();
        $operator = $user->operator;
        $token    = $user->createToken('dusk-operator')->plainTextToken;

        $userJson = addslashes(json_encode([
            'id'         => $user->id,
            'full_name'  => $user->full_name,
            'phone'      => $user->phone,
            'email'      => $user->email,
            'avatar_url' => null,
        ]));

        $operatorJson = addslashes(json_encode([
            'id'              => $operator?->id ?? '',
            'company_name'    => $operator?->company_name ?? 'Nhà xe Bắc Hà',
            'status'          => 'verified',
            'commission_rate' => (float) ($operator?->commission_rate ?? 10),
        ]));

        $browser->visit('/operator')
                ->script([
                    "localStorage.setItem('operator_token', '{$token}')",
                    "localStorage.setItem('operator_user', '{$userJson}')",
                    "localStorage.setItem('operator_info', '{$operatorJson}')",
                ]);

        $browser->visit('/operator/dashboard')->pause(1500);
    }

    protected function loginAsAdmin(Browser $browser, string $email = 'admin@xeghep.vn'): void
    {
        $user  = User::where('email', $email)->firstOrFail();
        $token = $user->createToken('dusk-admin')->plainTextToken;

        $userJson = addslashes(json_encode([
            'id'         => $user->id,
            'full_name'  => $user->full_name,
            'phone'      => $user->phone,
            'email'      => $user->email,
            'avatar_url' => null,
        ]));

        // Dismiss any leftover JS alert from a previous failed test, then clear
        // the stale token so the SPA redirects to /admin/login (no API calls),
        // preventing a 401 race that would wipe the newly injected token.
        // Both calls may throw if the browser hasn't loaded a real page yet.
        try { $browser->acceptDialog(); } catch (\Exception $e) {}
        try { $browser->script(["localStorage.removeItem('admin_token')"]); } catch (\Exception $e) {}
        $browser->visit('/admin')
                ->script([
                    "localStorage.setItem('admin_token', '{$token}')",
                    "localStorage.setItem('admin_user', '{$userJson}')",
                ]);

        $browser->visit('/admin/dashboard')->pause(2000);
    }

    // ── Search helpers ────────────────────────────────────────────────────────

    /**
     * Navigate through the Home page search form to populate store.searchParams,
     * then wait for the Results page to load.
     */
    protected function searchTripsToday(Browser $browser): void
    {
        // Trips are seeded for tomorrow — set the date input programmatically
        // so Vue's reactive state picks it up (type() corrupts date values).
        $tomorrow = now()->addDay()->toDateString();
        $browser->visit('/home')->pause(1500);
        // script() trả về array → KHÔNG chain tiếp; gọi tách riêng
        $browser->script([
            "var inp = document.querySelector('input[type=\"date\"]');"
            . "inp.value = '{$tomorrow}';"
            . "inp.dispatchEvent(new Event('input', {bubbles:true}));",
        ]);
        $browser->pause(300)
                ->press('Tìm chuyến')
                ->waitForLocation('/search')
                ->pause(2000);
    }

    /**
     * Full flow: login → search → click first trip → arrive at seat picker.
     * Returns the trip model that was navigated to.
     */
    protected function navigateToSeatPicker(Browser $browser): Trip
    {
        $trip = Trip::where('tracking_code', 'like', 'HNHP%')
                    ->whereDate('depart_at', today()->addDay())
                    ->where('status', 'scheduled')
                    ->first();

        $browser->visit("/trips/{$trip->id}/seats")
                ->pause(2500);

        return $trip;
    }

    // ── DB helpers ────────────────────────────────────────────────────────────

    protected function getTodayTrip(int $index = 0): Trip
    {
        return Trip::where('tracking_code', 'like', 'HNHP%')
                   ->whereDate('depart_at', today()->addDay())
                   ->orderBy('depart_at')
                   ->skip($index)
                   ->firstOrFail();
    }

    protected function markSeatBooked(string $tripTrackingCode, string $seatCode): void
    {
        $trip = Trip::where('tracking_code', $tripTrackingCode)->firstOrFail();
        SeatMap::where('trip_id', $trip->id)
               ->where('seat_code', $seatCode)
               ->update(['status' => 'booked']);
    }

    protected function createConfirmedBooking(): Booking
    {
        $customer = User::where('phone', '0900000003')->firstOrFail();
        $trip     = $this->getTodayTrip(1); // use 2nd trip so it doesn't conflict with seat tests
        $seat     = SeatMap::where('trip_id', $trip->id)
                           ->where('status', 'available')
                           ->first();

        $booking = Booking::create([
            'user_id'         => $customer->id,
            'trip_id'         => $trip->id,
            'booking_code'    => 'HNHP' . today()->addDay()->format('ymd') . '999',
            'contact_name'    => $customer->full_name,
            'contact_phone'   => $customer->phone,
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
                'full_name'   => $customer->full_name,
                'phone'       => $customer->phone,
                'seat_map_id' => $seat->id,
                'is_primary'  => true,
            ]);
        }

        return $booking;
    }
}
