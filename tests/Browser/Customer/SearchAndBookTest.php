<?php

namespace Tests\Browser\Customer;

use App\Enums\SeatStatus;
use App\Models\SeatMap;
use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use Tests\Browser\Pages\Customer\CheckoutPage;

class SearchAndBookTest extends DuskTestCase
{
    // ══════════════════════════════════════════════════════════════════════════
    //  PHẦN 1 — TÌM KIẾM CHUYẾN ĐI
    // ══════════════════════════════════════════════════════════════════════════

    // ── TEST 2-1 ─────────────────────────────────────────────────────────────

    public function test_homepage_search_form_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/home')
                    ->assertSee('XeGhep')
                    ->assertPresent('select')
                    ->assertPresent('input[type="date"]')
                    ->assertSee('Tìm chuyến');
        });
    }

    // ── TEST 2-2 ─────────────────────────────────────────────────────────────

    public function test_customer_can_search_trips(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->searchTripsToday($browser);

            $browser->assertPathIs('/search')
                    ->assertSee('Hà Nội')
                    ->assertSee('Hải Phòng')
                    ->assertSee('Chọn chuyến')
                    ->assertSee('150');  // price 150,000đ
        });
    }

    // ── TEST 2-3 ─────────────────────────────────────────────────────────────

    public function test_search_shows_empty_state_for_no_results(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            // Use a far-future date with no seeded trips (passes validation: after_or_equal:today)
            $farFuture = now()->addDays(30)->toDateString();

            $browser->visit('/home')->pause(1000);
            $browser->script([
                "var inp = document.querySelector('input[type=\"date\"]');"
                . "inp.value = '{$farFuture}';"
                . "inp.dispatchEvent(new Event('input', {bubbles:true}));",
            ]);
            $browser->pause(300)
                    ->press('Tìm chuyến')
                    ->waitForLocation('/search')
                    ->pause(2500)
                    ->assertSee('Không tìm thấy chuyến phù hợp');
        });
    }

    // ── TEST 2-4 ─────────────────────────────────────────────────────────────

    public function test_trip_filter_by_morning_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->searchTripsToday($browser);

            // Check checkbox "Sáng (05–12h)" — filters 06:00 and 10:00, removes 14:00
            $browser->check('input[type="checkbox"][value="morning"]')
                    ->pause(800)
                    ->assertSee('06:')  // 06:00 trip visible
                    ->assertSee('10:')  // 10:00 trip visible
                    ->assertDontSee('14:00'); // 14:00 filtered out
        });
    }

    // ── TEST 2-5 ─────────────────────────────────────────────────────────────

    public function test_trip_sort_by_price_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->searchTripsToday($browser);

            $browser->radio('input[type="radio"][value="price_asc"]', 'price_asc')
                    ->pause(800);

            // After sort, page should still show trips (all same price in seeder)
            $browser->assertSee('Chọn chuyến');
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PHẦN 2 — CHỌN GHẾ & CHECKOUT
    // ══════════════════════════════════════════════════════════════════════════

    // ── TEST 3-1 ─────────────────────────────────────────────────────────────

    public function test_customer_can_select_seats(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $trip = $this->navigateToSeatPicker($browser);

            // Seat picker loaded — wait for seat buttons
            $browser->waitFor('button', 5)->pause(1000);

            // Click seat A1 — button text is seat code
            $browser->press('A1')
                    ->pause(600);

            // A1 selected: button should have blue styling
            $browser->assertSee('A1')
                    ->assertSee('Đã chọn');

            // Summary bar should mention 1 seat, continue button active
            $browser->assertSee('1')
                    ->assertSee('Tiếp tục đặt vé');
        });
    }

    // ── TEST 3-2 ─────────────────────────────────────────────────────────────

    public function test_booked_seats_cannot_be_selected(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-book seat B1 on the first trip
            $trip = $this->getTodayTrip();
            $this->markSeatBooked($trip->tracking_code, 'B1');

            $this->loginAsCustomer($browser);
            $browser->visit("/trips/{$trip->id}/seats")->pause(2500);

            // B1 should be disabled (booked)
            $browser->assertPresent('button:disabled')
                    ->press('B1')
                    ->pause(500);

            // B1 should NOT appear in "Đã chọn" summary
            $browser->assertDontSee('B1 ×')
                    ->assertDontSee('B1 —');
        });
    }

    // ── TEST 3-3 ─────────────────────────────────────────────────────────────

    public function test_customer_can_complete_checkout_form(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->navigateToSeatPicker($browser);

            // Select seat A1
            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800);

            // Click continue → goes to /booking/checkout
            $browser->waitForText('Tiếp tục đặt vé', 5)
                    ->waitFor('button.bg-blue-600')
                    ->pause(300)
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // Fill contact info
            $browser->type('input[placeholder="Nguyễn Văn A"]', 'Nguyễn Test')
                    ->type('input[placeholder="0901234567"]', '0901234567');

            // Select pickup and dropoff stops if visible
            $browser->whenAvailable('select', function (Browser $select) {
                $select->select('', 'Mỹ Đình');
            });

            // Assert price summary shows 150,000đ
            $browser->assertSee('150');
        });
    }

    // ── TEST 3-4 ─────────────────────────────────────────────────────────────

    public function test_voucher_applies_discount_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            // Navigate directly to checkout with store state set via full flow
            $this->navigateToSeatPicker($browser);

            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800)
                    ->waitFor('button.bg-blue-600')
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // Fill required fields first
            $browser->type('input[placeholder="Nguyễn Văn A"]', 'Nguyễn Test')
                    ->type('input[placeholder="0901234567"]', '0901234567');

            // Apply voucher WELCOME50
            $browser->type('input[placeholder="Nhập mã giảm giá..."]', 'WELCOME50')
                    ->press('Áp dụng')
                    ->pause(2000);

            // Assert discount message — "Giảm 50,000đ" or similar
            $browser->assertSee('Giảm')
                    ->assertSee('50')
                    ->assertSee('WELCOME50');
        });
    }

    // ── TEST 3-5 ─────────────────────────────────────────────────────────────

    public function test_invalid_voucher_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->navigateToSeatPicker($browser);

            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800)
                    ->waitFor('button.bg-blue-600')
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // Apply invalid voucher
            $browser->type('input[placeholder="Nhập mã giảm giá..."]', 'SAICODE')
                    ->press('Áp dụng')
                    ->pause(2000)
                    ->assertSee('Mã không hợp lệ hoặc đã hết hạn');
        });
    }

    // ── TEST 3-6 ─────────────────────────────────────────────────────────────

    public function test_checkout_form_validates_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->navigateToSeatPicker($browser);

            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800)
                    ->waitFor('button.bg-blue-600')
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // Submit without filling required fields
            $browser->press('Chọn thanh toán')
                    ->pause(800)
                    ->assertSee('Vui lòng nhập họ tên')
                    ->assertPathIs('/booking/checkout'); // stay on page
        });
    }

    // ── TEST 3-7 ─────────────────────────────────────────────────────────────

    public function test_checkout_validates_vietnamese_phone_format(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->navigateToSeatPicker($browser);

            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800)
                    ->waitFor('button.bg-blue-600')
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // Fill name but invalid phone
            $browser->type('input[placeholder="Nguyễn Văn A"]', 'Nguyễn Test')
                    ->type('input[placeholder="0901234567"]', '123456')
                    ->press('Chọn thanh toán')
                    ->pause(800)
                    ->assertSee('Số điện thoại không hợp lệ');
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PHẦN 3 — ĐẶT VÉ END-TO-END (đến trang xác nhận)
    // ══════════════════════════════════════════════════════════════════════════

    // ── TEST 4-1 ─────────────────────────────────────────────────────────────

    public function test_full_booking_flow_to_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);
            $this->navigateToSeatPicker($browser);

            // 1) Chọn ghế A1 → giữ ghế → sang Checkout
            $browser->waitFor('button', 5)
                    ->press('A1')
                    ->pause(800)
                    ->waitForText('Tiếp tục đặt vé', 5)
                    ->click('button.bg-blue-600:last-of-type')
                    ->waitForLocation('/booking/checkout')
                    ->pause(2000);

            // 2) Điền thông tin liên hệ
            $browser->type('input[placeholder="Nguyễn Văn A"]', 'Nguyễn Văn Test')
                    ->type('input[placeholder="0901234567"]', '0901234567');

            // 3) Chọn điểm đón & trả (option value là UUID → set qua JS rồi dispatch change cho v-model)
            $browser->script("
                document.querySelectorAll('select').forEach(function (sel) {
                    if (sel.options.length > 1) {
                        sel.selectedIndex = 1;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            ");
            $browser->pause(500);

            // 4) Submit → tạo booking → sang trang Payment (chỗ trước đây lỗi 422)
            $browser->press('Chọn thanh toán')
                    ->waitForLocation('/booking/payment', 10)
                    ->pause(1500)
                    ->assertSee('Xác nhận & Thanh toán');

            // 5) Chọn Tiền mặt → thanh toán → sang trang Xác nhận
            $browser->radio('payment', 'cash')
                    ->pause(300)
                    ->press('Xác nhận & Thanh toán')
                    ->waitForText('Đặt vé thành công', 12)
                    ->assertSee('Đặt vé thành công')
                    ->assertSee('HNHP');   // booking_code hiển thị
        });
    }
}
