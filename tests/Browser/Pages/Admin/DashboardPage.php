<?php

namespace Tests\Browser\Pages\Admin;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class DashboardPage extends Page
{
    public function url(): string
    {
        return '/admin/dashboard';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
                ->assertSee('Tổng quan hệ thống');
    }

    public function elements(): array
    {
        return [
            '@sidebar'          => '.sidebar, nav[data-testid="sidebar"]',
            '@operators-link'   => 'a[href*="/admin/operators"]',
            '@drivers-link'     => 'a[href*="/admin/drivers"]',
            '@finance-link'     => 'a[href*="/admin/finance"]',
        ];
    }
}
