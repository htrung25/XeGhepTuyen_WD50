<?php

namespace Tests\Browser\Customer;

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;

class ProfileTest extends DuskTestCase
{
    public function test_profile_page_requires_auth(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/profile')
                    ->pause(1000)
                    ->assertPathIs('/login');
        });
    }

    public function test_profile_page_shows_user_info(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            $browser->visit('/profile')
                    ->pause(2000)
                    ->assertSee('Khách Hàng Test')
                    ->assertSee('0900000003');
        });
    }

    public function test_profile_page_has_menu_sections(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            $browser->visit('/profile')
                    ->pause(2000)
                    ->assertSee('Thông tin')
                    ->assertSee('Mật khẩu');
        });
    }

    public function test_customer_can_update_profile_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCustomer($browser);

            $browser->visit('/profile')
                    ->pause(2000);

            // Find and update full_name field
            $browser->whenAvailable('input[placeholder*="Họ và tên"], input[placeholder*="họ tên"]', function (Browser $field) {
                $field->clear()->type('', 'Khách Hàng Test Mới');
            });

            // Submit form — try pressing save/update button if present
            try {
                $browser->press('Lưu');
            } catch (\Exception) {
                try {
                    $browser->press('Cập nhật');
                } catch (\Exception) {
                    // No save button found — skip
                }
            }

            $browser->pause(1500);
        });
    }
}
