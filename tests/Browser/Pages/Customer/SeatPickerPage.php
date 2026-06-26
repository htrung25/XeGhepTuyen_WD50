<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class SeatPickerPage extends Page
{
    public function url(): string
    {
        return '';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathContains('/seats');
    }

    public function elements(): array
    {
        return [
            '@continue-btn' => 'button.bg-blue-600:last-of-type',
            '@summary-text' => 'p.text-sm',
            '@legend'       => 'div.flex.flex-wrap',
        ];
    }

    public function waitForSeats(Browser $browser): void
    {
        $browser->waitFor('button', 5)->pause(1500);
    }

    public function selectSeat(Browser $browser, string $seatCode): void
    {
        $browser->clickLink($seatCode);
    }

    public function clickContinue(Browser $browser): void
    {
        $browser->waitForText('Tiếp tục đặt vé', 5)
                ->click('@continue-btn')
                ->waitForLocation('/booking/checkout');
    }
}
