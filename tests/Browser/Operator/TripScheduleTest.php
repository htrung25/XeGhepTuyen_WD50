<?php

namespace Tests\Browser\Operator;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Vehicle;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class TripScheduleTest extends DuskTestCase
{
    // ── TEST 3-1 ─────────────────────────────────────────────────────────────

    public function test_operator_can_view_trip_schedule(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/trips')
                    ->pause(2500)
                    ->assertPathIs('/operator/trips')
                    ->assertSee('Lịch chạy')
                    ->assertSee('Lịch tuần này')
                    ->assertSee('Tạo chuyến mới')
                    ->assertSee('06:')   // seeded 06:00 trip appears in calendar today column
                    ->assertSee('10:')   // seeded 10:00 trip
                    ->assertSee('14:'); // seeded 14:00 trip
        });
    }

    // ── TEST 3-2 ─────────────────────────────────────────────────────────────

    public function test_create_trip_validation_fails_without_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/trips')
                    ->pause(2500);

            // Click "Tạo chuyến" without filling any field
            $browser->press('Tạo chuyến')
                    ->pause(600)
                    ->assertSee('Vui lòng điền đầy đủ thông tin bắt buộc');
        });
    }

    // ── TEST 3-3 ─────────────────────────────────────────────────────────────

    public function test_operator_can_create_single_trip_successfully(): void
    {
        $route   = Route::first();
        $vehicle = Vehicle::first();
        $driver  = Driver::first();

        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($route, $vehicle, $driver, $tomorrow) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/trips')
                    ->pause(2500);

            // Set select values via JS (Vue v-model on select)
            $routeId   = $route->id;
            $vehicleId = $vehicle->id;
            $driverId  = $driver->id;

            $browser->script(
                "var s = document.querySelectorAll('select');" .
                "s[0].value = '{$routeId}'; s[0].dispatchEvent(new Event('change', {bubbles:true}));" .
                "s[1].value = '{$vehicleId}'; s[1].dispatchEvent(new Event('change', {bubbles:true}));" .
                "s[2].value = '{$driverId}'; s[2].dispatchEvent(new Event('change', {bubbles:true}));"
            );

            // Set datetime-local input
            $browser->type('input[type="datetime-local"]', "{$tomorrow}T08:00")
                    ->pause(300);

            // Click "Tạo chuyến"
            $browser->press('Tạo chuyến')
                    ->pause(3000);

            // Success message appears
            $browser->assertSee('Tạo chuyến thành công!');
        });
    }

    // ── TEST 3-4 ─────────────────────────────────────────────────────────────

    public function test_bulk_create_toggle_shows_time_slots_and_date_range(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/trips')
                    ->pause(2500);

            // "Tạo lịch hàng loạt" is collapsed by default — click to expand
            $browser->press('Tạo lịch hàng loạt')
                    ->pause(500);

            // Time slot grid appears
            $browser->assertSee('Chọn giờ xuất phát')
                    ->assertSee('06:00')
                    ->assertSee('14:00')
                    ->assertSee('Các ngày trong tuần')
                    ->assertPresent('input[type="date"]')  // Từ ngày / Đến ngày inputs
                    ->assertSee('Tạo hàng loạt');
        });
    }

    // ── TEST 3-5 ─────────────────────────────────────────────────────────────

    public function test_bulk_create_validation_fails_without_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsOperator($browser);

            $browser->visit('/operator/trips')
                    ->pause(2500);

            // Expand bulk create panel
            $browser->press('Tạo lịch hàng loạt')
                    ->pause(500);

            // Try to submit without filling route/vehicle/driver or date range
            $browser->press('Tạo hàng loạt')
                    ->pause(600)
                    ->assertSee('Vui lòng chọn đầy đủ tuyến, xe, tài xế, khoảng ngày và ít nhất 1 giờ');
        });
    }

    // ── TEST 3-6 ─────────────────────────────────────────────────────────────

    public function test_operator_can_view_trip_manifest(): void
    {
        $trip = $this->getTodayTrip(0); // first trip from seeder (06:00)

        $this->browse(function (Browser $browser) use ($trip) {
            $this->loginAsOperator($browser);

            $browser->visit("/operator/trips/{$trip->id}/manifest")
                    ->pause(2500)
                    ->assertPathIs("/operator/trips/{$trip->id}/manifest")
                    ->assertSee('Chuyến')
                    ->assertSee('Hà Nội')
                    ->assertSee('Hải Phòng')
                    ->assertSee('Nguyễn Văn Tài')   // driver name from seeder
                    ->assertSee('30A-12345')          // vehicle plate from seeder
                    ->assertSee('In manifest')
                    ->assertSee('Xuất Excel');
        });
    }
}
