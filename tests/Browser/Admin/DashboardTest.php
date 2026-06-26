<?php

namespace Tests\Browser\Admin;

use App\Models\Trip;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    // ── TEST 3-1 ─────────────────────────────────────────────────────────────

    public function test_admin_dashboard_shows_kpi_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->assertPathIs('/admin/dashboard')
                    ->pause(3000)  // wait for API responses
                    ->assertSee('Tổng quan hệ thống')
                    ->assertSee('BOOKING HÔM NAY')
                    ->assertSee('DOANH THU HÔM NAY')
                    ->assertSee('XE ĐANG CHẠY')
                    ->assertSee('USER MỚI HÔM NAY')
                    ->assertSee('Nhà xe chờ duyệt')
                    ->assertSee('Tài xế chờ duyệt');
        });
    }

    // ── TEST 3-2 ─────────────────────────────────────────────────────────────

    public function test_admin_dashboard_shows_map_section(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->pause(3000);

            // Map section present (placeholder or actual map)
            $browser->assertSee('Theo dõi xe trực tiếp')
                    ->assertSee('Google Maps')
                    ->assertSee('Mở bản đồ')
                    ->assertSee('Booking gần đây');
        });
    }

    // ── TEST 3-3 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_all_trips(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/trips')
                    ->pause(2500)
                    ->assertPathIs('/admin/trips')
                    ->assertSee('Quản lý chuyến đi')
                    ->assertSee('Tất cả')
                    ->assertSee('Đã lên lịch')
                    ->assertSee('Đang chạy')
                    ->assertSee('Hoàn thành')
                    ->assertSee('Đã huỷ')
                    ->assertSee('MÃ / TUYẾN')
                    ->assertSee('GIỜ CHẠY')
                    ->assertSee('NHÀ XE');

            // Seeded trips appear (3 trips today)
            $browser->assertSee('HNHP')
                    ->assertSee('Hà Nội')
                    ->assertSee('Hải Phòng');
        });
    }

    // ── TEST 3-4 ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_live_trips(): void
    {
        // Mark one seeded trip as in_progress
        $trip = $this->getTodayTrip(0);
        $trip->update(['status' => 'in_progress']);

        $this->browse(function (Browser $browser) use ($trip) {
            $this->loginAsAdmin($browser);

            $browser->visit('/admin/trips/live')
                    ->pause(2500)
                    ->assertPathIs('/admin/trips/live');

            // Live map page loads — shows live content
            $browser->assertSee('Theo dõi chuyến đi trực tiếp')
                    ->assertDontSee('500 Internal Server Error');
        });
    }
}
