<?php

namespace Tests\Browser\Pages\Operator;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class DashboardPage extends Page
{
    public function url(): string
    {
        return '/operator/dashboard';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
                ->assertSee('Tổng quan');
    }

    public function elements(): array
    {
        return [
            '@sidebar'      => '.sidebar, nav[data-testid="sidebar"]',
            '@routes-link'  => 'a[href*="/operator/routes"]',
            '@trips-link'   => 'a[href*="/operator/trips"]',
            '@revenue-link' => 'a[href*="/operator/revenue"]',
        ];
    }
}
