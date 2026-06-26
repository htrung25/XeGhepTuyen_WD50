<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class FinanceTest extends DuskTestCase
{
    // ── TEST 4-1 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_finance_overview(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/finance')
                    ->pause(2500)
                    ->assertPathIs('/admin/finance')
                    ->assertSee('Tài chính & Quyết toán')
                    ->assertSee('Tổng quan')           // tab
                    ->assertSee('Giao dịch')           // tab
                    ->assertSee('Quyết toán nhà xe')   // tab
                    ->assertSee('Hoàn tiền')            // tab
                    ->assertSee('Tổng doanh thu nền tảng')
                    ->assertSee('Hoa hồng thu được')
                    ->assertSee('Chờ quyết toán')
                    ->assertSee('Doanh thu theo nhà xe');
        });
    }

    // ── TEST 4-2 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_transactions_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/finance')
                    ->pause(2500);

            // Click "Giao dịch" tab
            $browser->press('Giao dịch')
                    ->pause(1500);

            // Transactions table headers
            $browser->assertSee('Mã GD')
                    ->assertSee('Loại')
                    ->assertSee('Số tiền')
                    ->assertSee('Mã vé')
                    ->assertSee('Khách hàng')
                    ->assertSee('Thời gian');
        });
    }

    // ── TEST 4-3 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_settlement_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/finance')
                    ->pause(2500);

            // Click "Quyết toán nhà xe" tab
            $browser->press('Quyết toán nhà xe')
                    ->pause(1500);

            // Settlement table appears
            $browser->assertSee('Nhà xe')
                    ->assertSee('Kỳ quyết toán')
                    ->assertSee('Doanh thu')
                    ->assertSee('Hoa hồng')
                    ->assertSee('Số tiền CK')
                    ->assertSee('Trạng thái')
                    ->assertSee('Hành động');
        });
    }

    // ── TEST 4-4 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_payout_modal_when_commission_pending(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/finance')
                    ->pause(2500);

            // Click "Quyết toán nhà xe" tab
            $browser->press('Quyết toán nhà xe')
                    ->pause(1500);

            // If there's a pending commission, the "Thanh toán" button appears
            // We only assert the tab loaded correctly (no pending data in test seeder)
            $browser->assertPathIs('/admin/finance');

            // Check either "Thanh toán" button or "Không có dữ liệu quyết toán"
            $hasPayoutButton = $browser->element('button.text-white') !== null
                && str_contains($browser->text('button.text-white') ?? '', 'Thanh toán');

            if ($hasPayoutButton) {
                $browser->press('Thanh toán')
                        ->pause(500)
                        ->assertSee('Xác nhận thanh toán quyết toán')
                        ->assertSee('Xác nhận thanh toán')
                        ->press('Hủy'); // cancel the modal
            } else {
                $browser->assertSee('Không có dữ liệu quyết toán');
            }
        });
    }
}
