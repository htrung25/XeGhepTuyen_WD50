<?php

namespace Tests\Browser\Operator;

use App\Enums\OperatorStatus;
use App\Enums\UserRole;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use Tests\Browser\Pages\Operator\LoginPage;

class AuthTest extends DuskTestCase
{
    // ── TEST 1-1 ─────────────────────────────────────────────────────────────

    public function test_operator_login_page_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator/login')
                    ->assertSee('Đăng nhập dành cho Nhà xe')
                    ->assertPresent('input[type="tel"]')
                    ->assertPresent('input[type="password"]')
                    ->assertPresent('button[type="submit"]');
        });
    }

    // ── TEST 1-2 ─────────────────────────────────────────────────────────────

    public function test_operator_can_login_with_verified_account(): void
    {
        $this->browse(function (Browser $browser) {
            $page = new LoginPage;
            $browser->visit($page);
            $page->loginAs($browser, '0900000001', 'Test@123456');

            $browser->assertPathIs('/operator/dashboard')
                    ->assertSee('Tổng quan');
        });
    }

    // ── TEST 1-3 ─────────────────────────────────────────────────────────────

    public function test_unverified_operator_cannot_login(): void
    {
        // Create a pending operator account
        $pendingUser = User::firstOrCreate(
            ['phone' => '0922222222'],
            [
                'full_name'   => 'Nhà Xe Chờ Duyệt',
                'email'       => 'pending.operator@test.vn',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Operator,
                'is_verified' => false,
                'is_active'   => true,
            ]
        );

        Operator::firstOrCreate(
            ['user_id' => $pendingUser->id],
            [
                'user_id'          => $pendingUser->id,
                'company_name'     => 'Nhà Xe Mới Tạo',
                'business_license' => 'BL-PENDING01',
                'commission_rate'  => 10,
                'status'           => OperatorStatus::Pending,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->visit('/operator/login')
                    ->type('input[type="tel"]', '0922222222')
                    ->type('input[type="password"]', 'Test@123456')
                    ->click('button[type="submit"]')
                    ->pause(1500)
                    ->assertSee('Tài khoản nhà xe chưa được kích hoạt')
                    ->assertPathIs('/operator/login');
        });
    }

    // ── TEST 1-4 ─────────────────────────────────────────────────────────────

    public function test_operator_dashboard_shows_kpi_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->assertPathIs('/operator/dashboard')
                    ->assertSee('DOANH THU HÔM NAY')
                    ->assertSee('CHUYẾN HÔM NAY')
                    ->assertSee('SỐ KHÁCH HÔM NAY')
                    ->assertSee('Tổng quan');
        });
    }
}
