<?php

namespace Tests\Browser\Operator;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class RouteManagementTest extends DuskTestCase
{
    // ── TEST 2-1 ─────────────────────────────────────────────────────────────

    public function test_operator_can_view_route_list(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500)
                    ->assertPathIs('/operator/routes')
                    ->assertSee('Quản lý tuyến đường')
                    ->assertSee('TÊN TUYẾN')
                    ->assertSee('ĐIỂM ĐI')
                    ->assertSee('ĐIỂM ĐẾN')
                    ->assertSee('KHOẢNG CÁCH')
                    ->assertSee('TRẠNG THÁI')
                    ->assertSee('Hà Nội')    // seeded route origin
                    ->assertSee('Hải Phòng') // seeded route destination
                    ->assertSee('Thêm tuyến mới');
        });
    }

    // ── TEST 2-2 ─────────────────────────────────────────────────────────────

    public function test_operator_can_open_create_route_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500);

            // Click "Thêm tuyến mới" button to open modal
            $browser->press('Thêm tuyến mới')
                    ->pause(600);

            // Modal appears with form fields
            $browser->assertSee('Thêm tuyến đường mới')
                    ->assertPresent('input[placeholder="VD: HNHP"]')  // route_code
                    ->assertSee('Thêm điểm dừng')
                    ->assertSee('Lưu tuyến đường');
        });
    }

    // ── TEST 2-3 ─────────────────────────────────────────────────────────────

    public function test_create_route_validation_fails_without_route_code(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500);

            // Open modal — route_code is blank, 1 stop auto-added
            $browser->press('Thêm tuyến mới')
                    ->pause(600);

            // Try to save without filling route_code or enough stops
            $browser->press('Lưu tuyến đường')
                    ->pause(600)
                    ->assertSee('Vui lòng nhập mã tuyến và ít nhất 2 điểm dừng');
        });
    }

    // ── TEST 2-4 ─────────────────────────────────────────────────────────────

    public function test_create_route_validation_fails_with_only_one_stop(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500);

            // Open modal — it pre-adds exactly 1 stop
            $browser->press('Thêm tuyến mới')
                    ->pause(600);

            // Fill route_code but keep only 1 stop (the auto-added one)
            $browser->type('input[placeholder="VD: HNHP"]', 'TEST-01')
                    ->press('Lưu tuyến đường')
                    ->pause(600)
                    ->assertSee('Vui lòng nhập mã tuyến và ít nhất 2 điểm dừng');
        });
    }

    // ── TEST 2-5 ─────────────────────────────────────────────────────────────

    public function test_operator_can_create_route_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500);

            // Open modal
            $browser->press('Thêm tuyến mới')
                    ->pause(600);

            // Fill route_code
            $browser->type('input[placeholder="VD: HNHP"]', 'HN-HUE-01');

            // Fill the pre-added first stop
            $browser->type('input[placeholder="Tên điểm dừng"]:first-of-type', 'Bến xe Hà Nội')
                    ->type('input[placeholder="Địa chỉ"]:first-of-type', 'Hà Nội');

            // Add a second stop
            $browser->press('Thêm điểm dừng')
                    ->pause(300);

            $browser->type('input[placeholder="Tên điểm dừng"]:last-of-type', 'Bến xe Huế')
                    ->type('input[placeholder="Địa chỉ"]:last-of-type', 'Huế');

            // Save
            $browser->press('Lưu tuyến đường')
                    ->pause(2500);

            // Modal closed and route list reloaded — new route appears
            $browser->assertDontSee('Thêm tuyến đường mới') // modal closed
                    ->assertSee('HN-HUE-01');               // new route in list

            $this->assertDatabaseHas('routes', ['route_code' => 'HN-HUE-01']);
        });
    }

    // ── TEST 2-6 ─────────────────────────────────────────────────────────────

    public function test_delete_route_with_trips_triggers_native_dialogs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/routes')
                    ->pause(2500);

            // Click "Xóa" on the seeded route (which has 3 trips seeded today)
            $browser->press('Xóa')
                    ->pause(300);

            // Native confirm dialog — accept it
            $browser->acceptDialog()
                    ->pause(2000);

            // The server returns an error (no DELETE endpoint or constraint)
            // Vue's deleteRoute calls alert(error) — accept the alert if present
            try {
                $browser->waitForDialog(4)
                        ->acceptDialog();
            } catch (\Exception) {
                // Alert may not appear; the important thing is the route still exists
            }

            // Seeded route must still exist
            $this->assertDatabaseHas('routes', ['origin_city' => 'Hà Nội', 'dest_city' => 'Hải Phòng']);
        });
    }
}
