<?php

namespace Tests\Browser\Customer;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class PaymentTest extends DuskTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Full flow: login → seat picker → select A1 → checkout fill → arrive at payment.
     */
    private function goToPaymentPage(Browser $browser): void
    {
        $this->loginAsCustomer($browser);
        $this->navigateToSeatPicker($browser);

        $browser->waitFor('button', 5)
                ->press('A1')
                ->pause(800)
                ->waitFor('button.bg-blue-600')
                ->click('button.bg-blue-600:last-of-type')
                ->waitForLocation('/booking/checkout')
                ->pause(2000);

        $browser->type('input[placeholder="Nguyễn Văn A"]', 'Nguyễn Test')
                ->type('input[placeholder="0901234567"]', '0901234567');

        // Select first available pickup stop
        $browser->whenAvailable('select:first-of-type', function (Browser $s) {
            $s->script('arguments[0].selectedIndex = 1');
        });

        // Select first available dropoff stop
        $browser->whenAvailable('select:last-of-type', function (Browser $s) {
            $s->script('arguments[0].selectedIndex = 1');
        });

        $browser->press('Chọn thanh toán')
                ->waitForLocation('/booking/payment')
                ->pause(2000);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PHẦN 1 — TRANG THANH TOÁN
    // ══════════════════════════════════════════════════════════════════════════

    // ── TEST 4-1 ─────────────────────────────────────────────────────────────

    public function test_payment_page_shows_correct_amount(): void
    {
        $this->browse(function (Browser $browser) {
            $this->goToPaymentPage($browser);

            // Assert path is payment page
            $browser->assertPathIs('/booking/payment')
                    ->assertSee('150')      // 150,000đ amount
                    ->assertPresent('span'); // countdown timer present
        });
    }

    // ── TEST 4-2 ─────────────────────────────────────────────────────────────

    public function test_payment_page_shows_all_methods(): void
    {
        $this->browse(function (Browser $browser) {
            $this->goToPaymentPage($browser);

            $browser->assertSee('MoMo')
                    ->assertSee('VNPay')
                    ->assertSee('Ví XeGhep')
                    ->assertSee('Tiền mặt');
        });
    }

    // ── TEST 4-3 ─────────────────────────────────────────────────────────────

    public function test_cash_payment_creates_confirmed_booking(): void
    {
        $this->browse(function (Browser $browser) {
            $this->goToPaymentPage($browser);

            // Select cash payment method
            $browser->radio('input[name="payment"]', 'cash')
                    ->pause(400)
                    ->press('Xác nhận & Thanh toán')
                    ->pause(3000);

            // Should redirect to confirmation page
            $browser->assertPathContains('/confirmation')
                    ->assertSee('HNHP')          // booking code format
                    ->assertPresent('img');       // QR code image
        });
    }

    // ── TEST 4-4 ─────────────────────────────────────────────────────────────

    public function test_expired_booking_redirects_to_home(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            // Visiting payment page without booking in store redirects to home
            $browser->visit('/booking/payment')
                    ->pause(1500)
                    ->assertPathIs('/home');
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PHẦN 2 — QUẢN LÝ VÉ (BOOKING HISTORY)
    // ══════════════════════════════════════════════════════════════════════════

    // ── TEST 5-1 ─────────────────────────────────────────────────────────────

    public function test_customer_can_view_booking_history(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            $browser->visit('/bookings')
                    ->pause(2000)
                    ->assertSee('Sắp đi')
                    ->assertSee('Đã đi')
                    ->assertSee('Đã hủy');
        });
    }

    // ── TEST 5-2 ─────────────────────────────────────────────────────────────

    public function test_customer_can_view_ticket_qr(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a confirmed booking in DB
            $booking = $this->createConfirmedBooking();

            $this->loginAsCustomer($browser);

            $browser->visit('/bookings')
                    ->pause(2500)
                    ->assertSee($booking->booking_code)
                    ->press('Xem vé QR')
                    ->pause(1000);

            // QR modal or page shows booking code
            $browser->assertSee($booking->booking_code);
        });
    }

    // ── TEST 5-3 ─────────────────────────────────────────────────────────────

    public function test_customer_can_cancel_booking_with_refund_info(): void
    {
        $this->browse(function (Browser $browser) {
            $booking = $this->createConfirmedBooking();

            $this->loginAsCustomer($browser);

            $browser->visit('/bookings')
                    ->pause(2500)
                    ->assertSee($booking->booking_code)
                    ->press('Hủy vé')
                    ->pause(1000);

            // Cancel confirmation modal appears
            $browser->assertSee('Xác nhận hủy vé')
                    ->assertSee('Tiền hoàn'); // refund policy mention

            // Click the red "Hủy vé" confirm button inside the modal
            $browser->click('.bg-red-600')
                    ->pause(2500)
                    ->assertSee('Đã hủy');
        });
    }

    // ── TEST 5-4 ─────────────────────────────────────────────────────────────

    public function test_customer_cannot_cancel_completed_booking(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            $browser->visit('/bookings')
                    ->pause(2000)
                    ->press('Đã đi')
                    ->pause(1500);

            // On the "Đã đi" tab, no "Hủy vé" button should appear
            $browser->assertDontSee('Hủy vé');
        });
    }
}
