<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class VoucherTest extends DuskTestCase
{
    // ── TEST 5-1 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_vouchers(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/vouchers')
                    ->pause(2500)
                    ->assertPathIs('/admin/vouchers')
                    ->assertSee('Mã giảm giá')
                    ->assertSee('Tạo voucher mới')
                    ->assertSee('Voucher đang hoạt động')
                    ->assertSee('Tổng lượt đã dùng')
                    ->assertSee('Tổng voucher')
                    ->assertSee('WELCOME50');  // seeded voucher code
        });
    }

    // ── TEST 5-2 ─────────────────────────────────────────────────────────────

    public function test_admin_can_create_voucher(): void
    {
        $today   = now()->format('Y-m-d');
        $in30    = now()->addDays(30)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($today, $in30) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/vouchers')
                    ->pause(2500);

            // Click "Tạo voucher mới"
            $browser->press('Tạo voucher mới')
                    ->pause(500);

            // Modal appears
            $browser->assertSee('Tạo voucher mới')
                    ->assertPresent('input[placeholder="VD: SALE20"]');

            // Fill the code field
            $browser->type('input[placeholder="VD: SALE20"]', 'TEST100');

            // Select "VNĐ" (fixed discount type)
            $browser->press('VNĐ')
                    ->pause(200);

            // Fill discount value (clear default 10 first, type 100000)
            $browser->clear('input[type="number"]:first-of-type')
                    ->type('input[type="number"]:first-of-type', '100000');

            // Fill date range
            $browser->type('input[type="date"]:first-of-type', $today)
                    ->type('input[type="date"]:last-of-type', $in30);

            // Submit
            $browser->press('Tạo voucher')
                    ->pause(2500);

            // Modal closes, success toast appears, voucher in list
            $browser->assertSee('Tạo voucher thành công')
                    ->assertSee('TEST100');

            $this->assertDatabaseHas('vouchers', ['code' => 'TEST100']);
        });
    }

    // ── TEST 5-3 ─────────────────────────────────────────────────────────────

    public function test_admin_can_deactivate_voucher(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/vouchers')
                    ->pause(2500);

            // The WELCOME50 voucher from seeder is active (green toggle)
            // Click the toggle button to deactivate it
            // Toggle is: button.bg-green-500 (active) or button.bg-gray-200 (inactive)
            $browser->click('button.bg-green-500')
                    ->pause(2000);

            // Toggle is now inactive (bg-gray-200)
            $browser->assertPresent('button.bg-gray-200');

            // Verify DB: voucher is_active = false
            $this->assertDatabaseHas('vouchers', [
                'code'      => 'WELCOME50',
                'is_active' => false,
            ]);
        });
    }

    // ── TEST 5-4 (bonus) ─────────────────────────────────────────────────────

    public function test_admin_can_reactivate_voucher(): void
    {
        // First deactivate WELCOME50 in DB
        \App\Models\Voucher::where('code', 'WELCOME50')->update(['is_active' => false]);

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/vouchers')
                    ->pause(2500);

            // Toggle is now gray (inactive) — click to reactivate
            $browser->click('button.bg-gray-200')
                    ->pause(2000);

            // Toggle turns green
            $browser->assertPresent('button.bg-green-500');

            $this->assertDatabaseHas('vouchers', [
                'code'      => 'WELCOME50',
                'is_active' => true,
            ]);
        });
    }
}
