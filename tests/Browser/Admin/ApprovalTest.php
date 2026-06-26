<?php

namespace Tests\Browser\Admin;

use App\Enums\DriverStatus;
use App\Enums\OperatorStatus;
use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class ApprovalTest extends DuskTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPendingOperator(): Operator
    {
        $user = User::firstOrCreate(
            ['phone' => '0933333333'],
            [
                'full_name'   => 'Nhà xe Thử Nghiệm',
                'email'       => 'test.operator.approval@xeghep.vn',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Operator,
                'is_verified' => false,
                'is_active'   => true,
            ]
        );

        return Operator::firstOrCreate(
            ['user_id' => $user->id],
            [
                'user_id'          => $user->id,
                'company_name'     => 'Nhà xe Thử Nghiệm',
                'business_license' => 'BL-TEST999',
                'commission_rate'  => 10,
                'status'           => OperatorStatus::Pending,
            ]
        );
    }

    private function createPendingDriver(): Driver
    {
        $operator = Operator::first();

        $user = User::firstOrCreate(
            ['phone' => '0944444444'],
            [
                'full_name'   => 'Tài Xế Chờ Duyệt Test',
                'email'       => 'driver.approval@xeghep.vn',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Driver,
                'is_verified' => false,
                'is_active'   => true,
            ]
        );

        return Driver::firstOrCreate(
            ['user_id' => $user->id],
            [
                'operator_id'    => $operator->id,
                'user_id'        => $user->id,
                'license_number' => 'B2-TESTAPPRV',
                'license_class'  => 'B2',
                'license_expiry' => now()->addYears(2),
                'id_card_number' => '001199TESTAPV',
                'status'         => DriverStatus::Pending,
            ]
        );
    }

    // ── TEST 2-1 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_pending_operators(): void
    {
        $this->createPendingOperator();

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/operators')
                    ->pause(2500)
                    ->assertPathIs('/admin/operators')
                    ->assertSee('Nhà xe chờ duyệt')
                    ->assertSee('Chờ duyệt')  // tab
                    ->assertSee('Đã duyệt')   // tab
                    ->assertSee('Nhà xe Thử Nghiệm')
                    ->assertSee('Duyệt')
                    ->assertSee('Từ chối');
        });
    }

    // ── TEST 2-2 ─────────────────────────────────────────────────────────────

    public function test_admin_can_approve_operator(): void
    {
        $operator = $this->createPendingOperator();

        $this->browse(function (Browser $browser) use ($operator) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/operators')
                    ->pause(2500);

            // Click "Duyệt" on the pending operator
            $browser->press('Duyệt')
                    ->pause(600);

            // Approve modal appears
            $browser->assertSee('Xác nhận duyệt nhà xe')
                    ->assertSee('Nhà xe Thử Nghiệm')
                    ->assertPresent('input[type="number"]'); // commission rate input

            // Commission rate default is 10 — keep it
            $browser->press('Xác nhận duyệt')
                    ->pause(2500);

            // Modal closes, list reloads — operator now shows "Đã duyệt"
            $browser->assertDontSee('Xác nhận duyệt nhà xe')
                    ->assertSee('Đã duyệt');

            // DB assertion
            $this->assertDatabaseHas('operators', [
                'id'     => $operator->id,
                'status' => 'verified',
            ]);
        });
    }

    // ── TEST 2-3 ─────────────────────────────────────────────────────────────

    public function test_admin_can_reject_operator_with_reason(): void
    {
        $operator = $this->createPendingOperator();

        $this->browse(function (Browser $browser) use ($operator) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/operators')
                    ->pause(2500);

            // Click "Từ chối" on the pending operator
            $browser->press('Từ chối')
                    ->pause(600);

            // Reject modal appears
            $browser->assertSee('Từ chối / Đình chỉ nhà xe')
                    ->assertPresent('textarea[placeholder="Nhập lý do từ chối..."]');

            // Enter rejection reason
            $browser->type('textarea[placeholder="Nhập lý do từ chối..."]', 'Giấy phép kinh doanh không hợp lệ')
                    ->press('Xác nhận')
                    ->pause(2500);

            // Modal closes, list reloads — operator status changed
            $browser->assertDontSee('Từ chối / Đình chỉ nhà xe');
        });
    }

    // ── TEST 2-4 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_pending_drivers(): void
    {
        $this->createPendingDriver();

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/drivers')
                    ->pause(2500)
                    ->assertPathIs('/admin/drivers')
                    ->assertSee('Xét duyệt tài xế')
                    ->assertSee('Chờ duyệt')          // tab
                    ->assertSee('Đã duyệt')            // tab
                    ->assertSee('Tài Xế Chờ Duyệt Test')
                    ->assertSee('CHỨNG MINH NHÂN DÂN')
                    ->assertSee('GIẤY PHÉP LÁI XE')
                    ->assertSee('Duyệt tài xế');
        });
    }

    // ── TEST 2-5 ─────────────────────────────────────────────────────────────

    public function test_admin_can_approve_driver(): void
    {
        $driver = $this->createPendingDriver();

        $this->browse(function (Browser $browser) use ($driver) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/drivers')
                    ->pause(2500);

            // Click "Duyệt tài xế" — triggers native confirm() dialog
            $browser->press('Duyệt tài xế')
                    ->pause(400);

            // Native confirm dialog: "Xác nhận duyệt tài xế Tài Xế Chờ Duyệt Test?"
            $browser->acceptDialog()
                    ->pause(2500);

            // List reloads — driver now shows "Đã duyệt" badge
            $browser->assertSee('Đã duyệt');

            $this->assertDatabaseHas('drivers', [
                'id'     => $driver->id,
                'status' => 'verified',
            ]);
        });
    }

    // ── TEST 2-6 ─────────────────────────────────────────────────────────────

    public function test_admin_can_suspend_verified_driver(): void
    {
        // Use the seeded verified driver (0900000002)
        $driver = \App\Models\User::where('phone', '0900000002')->firstOrFail()->driver;

        $this->browse(function (Browser $browser) use ($driver) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/drivers')
                    ->pause(2500);

            // Switch to "Đã duyệt" tab to find verified driver
            $browser->press('Đã duyệt')
                    ->pause(1500)
                    ->assertSee('Nguyễn Văn Tài');  // seeded verified driver

            // Click the "Đình chỉ" action button on the driver card.
            // Using CSS selector to avoid hitting the "Đình chỉ" tab button
            // which appears earlier in the DOM and has the same text.
            $browser->click('button.border-red-300')
                    ->pause(500);

            // Reject modal opens for suspension
            $browser->assertSee('Từ chối tài xế')
                    ->type('textarea[placeholder="Nhập lý do từ chối..."]', 'Vi phạm quy định lái xe')
                    ->press('Xác nhận từ chối')
                    ->pause(2500);

            // Status updated to suspended
            $browser->assertDontSee('Từ chối tài xế');

            $this->assertDatabaseHas('drivers', [
                'id'     => $driver->id,
                'status' => 'suspended',
            ]);
        });
    }
}
