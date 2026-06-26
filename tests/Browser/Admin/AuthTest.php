<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use Tests\Browser\Pages\Admin\LoginPage;

class AuthTest extends DuskTestCase
{
    // ── TEST 1-1 ─────────────────────────────────────────────────────────────

    public function test_admin_login_page_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->assertSee('XeGhep')
                    ->assertSee('Admin')
                    ->assertSee('Đăng nhập Quản trị viên')
                    ->assertPresent('input[type="email"]')
                    ->assertPresent('input[type="password"]')
                    ->assertPresent('button[type="submit"]');
        });
    }

    // ── TEST 1-2 ─────────────────────────────────────────────────────────────

    public function test_admin_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new LoginPage;
            $browser->visit($page);
            $page->loginAs($browser, 'admin@xeghep.vn', 'Admin@123456');

            $browser->assertPathIs('/admin/dashboard')
                    ->assertSee('Tổng quan hệ thống')
                    ->assertSee('XeGhep Admin');  // sidebar header
        });
    }

    // ── TEST 1-3 ─────────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $this->browse(function (Browser $browser) {
            // No admin token in localStorage — visiting /admin/dashboard redirects to /admin/login
            $browser->visit('/admin/dashboard')
                    ->pause(1000)
                    ->assertPathIs('/admin/login');
        });
    }

    // ── TEST 1-4 ─────────────────────────────────────────────────────────────

    public function test_admin_session_expires_after_logout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->assertPathIs('/admin/dashboard');

            // Logout via sidebar button
            $browser->press('Đăng xuất')
                    ->pause(1500);

            // Now visit dashboard — should redirect to login
            $browser->visit('/admin/dashboard')
                    ->pause(1000)
                    ->assertPathIs('/admin/login');
        });
    }
}
