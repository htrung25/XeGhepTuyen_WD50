<?php

namespace Tests\Browser\Pages\Driver;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class TripPage extends Page
{
    public function url(): string
    {
        return '/driver/dashboard';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathContains('/driver');
    }

    public function elements(): array
    {
        return [
            '@trip-table'   => 'table',
            '@start-btn'    => 'button.bg-blue-600',
            '@complete-btn' => 'button.bg-red-600',
            '@confirm-btn'  => 'button.bg-blue-600',
            '@modal'        => 'div[class*="fixed inset-0"]',
        ];
    }
}
