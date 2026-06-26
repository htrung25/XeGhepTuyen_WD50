<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class TrackingPage extends Page
{
    public function url(): string
    {
        return '';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathContains('/track');
    }

    public function elements(): array
    {
        return [
            '@map'         => '#map',
            '@driver-info' => '.driver-info, [class*="driver"]',
            '@stops'       => 'div[class*="stop"]',
        ];
    }
}
