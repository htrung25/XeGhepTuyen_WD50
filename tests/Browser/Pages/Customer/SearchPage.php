<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class SearchPage extends Page
{
    public function url(): string
    {
        return '/search';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@morning-filter'  => 'input[type="checkbox"][value="morning"]',
            '@afternoon-filter'=> 'input[type="checkbox"][value="afternoon"]',
            '@sort-price'      => 'input[type="radio"][value="price_asc"]',
            '@sort-depart'     => 'input[type="radio"][value="depart_asc"]',
            '@reset-filter'    => 'button.text-gray-600',
            '@trip-count'      => '.text-gray-900.font-semibold',
            '@empty-state'     => 'h3',
        ];
    }

    public function waitForTrips(Browser $browser): void
    {
        $browser->pause(2000);
    }

    public function selectFirstTrip(Browser $browser): void
    {
        $browser->waitFor('button', 5)
                ->click('button.bg-blue-600')
                ->pause(500);
    }
}
