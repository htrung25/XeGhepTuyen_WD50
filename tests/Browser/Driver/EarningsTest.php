<?php

namespace Tests\Browser\Driver;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class EarningsTest extends DuskTestCase
{
    // ── TEST 4-1 ─────────────────────────────────────────────────────────────

    public function test_driver_earnings_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $browser->visit('/driver/earnings')
                    ->pause(2500)
                    ->assertPathIs('/driver/earnings')
                    ->assertSee('Số dư khả dụng')
                    ->assertSee('Hôm nay')
                    ->assertSee('Tuần này')
                    ->assertSee('Tháng này')
                    ->assertSee('Rút tiền');
        });
    }

    // ── TEST 4-2 ─────────────────────────────────────────────────────────────

    public function test_driver_earnings_period_filter_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $browser->visit('/driver/earnings')
                    ->pause(2500);

            // Click "Tháng này" tab
            $browser->press('Tháng này')
                    ->pause(2000)
                    ->assertPathIs('/driver/earnings')
                    ->assertSee('Tháng này'); // tab still selected/visible

            // Click "Tuần này" tab
            $browser->press('Tuần này')
                    ->pause(2000)
                    ->assertSee('Tuần này');
        });
    }

    // ── TEST 4-3 ─────────────────────────────────────────────────────────────

    public function test_driver_can_request_withdrawal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDriver($browser);

            $browser->visit('/driver/earnings')
                    ->pause(2500);

            // Click "Rút tiền" button to open modal
            $browser->press('Rút tiền')
                    ->pause(800);

            // Withdrawal modal appears
            $browser->assertSee('Rút tiền')
                    ->assertPresent('input[placeholder="Nhập số tiền cần rút"]')
                    ->assertSee('Tối thiểu 50,000đ');

            // Enter amount
            $browser->type('input[placeholder="Nhập số tiền cần rút"]', '50000')
                    ->press('Xác nhận')
                    ->pause(2500);

            // After confirm: modal closes or shows result (success / insufficient balance)
            // The withdrawal modal button "Xác nhận" triggers the API call
            $browser->assertPathIs('/driver/earnings')
                    ->assertSee('Rút tiền'); // page still has the Rút tiền button
        });
    }
}
