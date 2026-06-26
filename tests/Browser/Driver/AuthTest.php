<?php

namespace Tests\Browser\Driver;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use Tests\Browser\Pages\Driver\LoginPage;

class AuthTest extends DuskTestCase
{
    // ── TEST 1-1 ─────────────────────────────────────────────────────────────

    public function test_driver_login_page_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/driver/login')
                    ->assertSee('Đăng nhập Tài xế')
                    ->assertPresent('input[type="tel"]')
                    ->assertPresent('input[type="password"]')
                    ->assertPresent('button.bg-green-600');
        });
    }

    // ── TEST 1-2 ─────────────────────────────────────────────────────────────

    public function test_driver_can_login_with_verified_account(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new LoginPage;
            $browser->visit($page);
            $page->loginAs($browser, '0900000002', 'Test@123456');

            $browser->assertPathIs('/driver/dashboard')
                    ->assertSee('Nguyễn Văn Tài'); // full_name from DuskTestSeeder
        });
    }

    // ── TEST 1-3 ─────────────────────────────────────────────────────────────

    public function test_unverified_driver_cannot_login(): void
    {
        // Create a pending driver account
        $pendingUser = User::firstOrCreate(
            ['phone' => '0911111111'],
            [
                'full_name'   => 'Tài Xế Chờ Duyệt',
                'email'       => 'pending.driver@test.vn',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Driver,
                'is_verified' => false,
                'is_active'   => true,
            ]
        );

        $operator = \App\Models\Operator::first();

        Driver::firstOrCreate(
            ['user_id' => $pendingUser->id],
            [
                'operator_id'    => $operator->id,
                'user_id'        => $pendingUser->id,
                'license_number' => 'B2-PENDING01',
                'license_class'  => 'B2',
                'license_expiry' => now()->addYear(),
                'id_card_number' => '001199PEND01',
                'status'         => DriverStatus::Pending,   // chưa duyệt
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->visit('/driver/login')
                    ->type('input[type="tel"]', '0911111111')
                    ->type('input[type="password"]', 'Test@123456')
                    ->click('button.bg-green-600')
                    ->pause(1500)
                    ->assertSee('Tài khoản chưa được duyệt hoặc đã bị đình chỉ')
                    ->assertPathIs('/driver/login');
        });
    }

    // ── TEST 1-4 ─────────────────────────────────────────────────────────────

    public function test_driver_cannot_access_customer_routes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            // Visiting a customer-only route without customer token → stays on driver
            $browser->visit('/bookings')
                    ->pause(1000);

            // Customer Vue app loads (separate SPA), driver has no customer token
            // Should NOT see driver dashboard content here — it's a different SPA
            // The customer SPA guard redirects to /login (customer login)
            $browser->assertDontSee('Chuyến hôm nay');
        });
    }

    // ── TEST 1-5 ─────────────────────────────────────────────────────────────

    public function test_driver_can_toggle_online_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            // Initial state: offline ('Đã tắt nhận')
            $browser->assertSee('Đã tắt nhận');

            // Click toggle to go online
            $browser->press('Đã tắt nhận')
                    ->pause(1500)
                    ->assertSee('Đang nhận khách');

            // Click again to go offline
            $browser->press('Đang nhận khách')
                    ->pause(1500)
                    ->assertSee('Đã tắt nhận');
        });
    }
}
