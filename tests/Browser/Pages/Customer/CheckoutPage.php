<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class CheckoutPage extends Page
{
    public function url(): string
    {
        return '/booking/checkout';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@passenger-name'  => 'input[placeholder="Nguyễn Văn A"]',
            '@passenger-phone' => 'input[placeholder="0901234567"]',
            '@voucher-input'   => 'input[type="text"]:last-of-type',
            '@apply-voucher'   => 'button.text-blue-600, button.border-blue-600',
            '@submit-btn'      => 'button[type="button"].bg-blue-600',
            '@voucher-msg'     => 'p.text-sm.font-medium',
            '@err-name'        => 'p.text-xs.text-red-500',
            '@err-phone'       => 'p.text-xs.text-red-500',
        ];
    }

    public function fillContactInfo(Browser $browser, string $name, string $phone): void
    {
        $browser->type('@passenger-name', $name)
                ->type('@passenger-phone', $phone);
    }

    public function applyVoucher(Browser $browser, string $code): void
    {
        $browser->type('@voucher-input', $code)
                ->click('@apply-voucher')
                ->pause(1500);
    }

    public function submit(Browser $browser): void
    {
        $browser->click('@submit-btn')
                ->waitForLocation('/booking/payment');
    }
}
