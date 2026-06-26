<?php

namespace Tests\Browser\Customer;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use Tests\Browser\Pages\Customer\LoginPage;
use Tests\Browser\Pages\Customer\RegisterPage;

class AuthTest extends DuskTestCase
{
    // ── TEST 1-1 ─────────────────────────────────────────────────────────────

    public function test_customer_can_view_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->pause(1500)
                    ->assertSee('Đăng nhập')
                    ->assertPresent('input[type="tel"]')
                    ->assertPresent('input[type="password"]')
                    ->assertPresent('button[type="submit"]')
                    ->assertSee('Đăng ký');
        });
    }

    // ── TEST 1-2 ─────────────────────────────────────────────────────────────

    public function test_customer_can_register_with_valid_phone(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                    ->register('Nguyễn Test', '0912345999', 'Test@123456');

            // Registration auto-logs in and redirects to /home
            $browser->waitForLocation('/home', 5)
                    ->assertPathIs('/home')
                    ->assertSee('XeGhep');
        });
    }

    // ── TEST 1-3 ─────────────────────────────────────────────────────────────

    public function test_customer_cannot_register_with_existing_phone(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                    ->register('Tên Trùng', '0900000003', 'Test@123456');

            $browser->pause(1500)
                    ->assertSee('Số điện thoại đã được đăng ký');
        });
    }

    // ── TEST 1-4 ─────────────────────────────────────────────────────────────

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new LoginPage;
            $browser->visit($page);
            $page->loginAs($browser, '0900000003', 'Test@123456');

            $browser->assertPathIs('/home')
                    ->assertSee('Test'); // full_name = 'Khách Hàng Test', layout shows last word
        });
    }

    // ── TEST 1-5 ─────────────────────────────────────────────────────────────

    public function test_customer_cannot_login_with_wrong_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->pause(1000)
                    ->type('input[type="tel"]', '0900000003')
                    ->type('input[type="password"]', 'SaiMatKhau')
                    ->click('button[type="submit"]')
                    ->pause(1500)
                    ->assertSee('Số điện thoại hoặc mật khẩu không đúng')
                    ->assertPathIs('/login');
        });
    }

    // ── TEST 1-6 ─────────────────────────────────────────────────────────────

    public function test_customer_can_logout(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new LoginPage;
            $browser->visit($page);
            $page->loginAs($browser, '0900000003', 'Test@123456');

            $browser->assertPathIs('/home')
                    ->assertSee('Test'); // user name visible

            $browser->press('Đăng xuất')
                    ->pause(1500)
                    ->assertDontSee('Test')       // user name gone
                    ->assertSee('Đăng nhập');     // login link visible again
        });
    }

    // ── TEST 1-7 ─────────────────────────────────────────────────────────────

    public function test_protected_routes_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/booking/checkout')
                    ->pause(1000)
                    ->assertPathIs('/login');
        });
    }
}
