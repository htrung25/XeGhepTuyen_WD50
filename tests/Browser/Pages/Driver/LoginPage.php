<?php

namespace Tests\Browser\Pages\Driver;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class LoginPage extends Page
{
    public function url(): string
    {
        return '/driver/login';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@phone'    => 'input[type="tel"]',
            '@password' => 'input[type="password"]',
            '@submit'   => 'button.bg-green-600',
            '@error'    => '.text-red-700',
        ];
    }

    public function loginAs(Browser $browser, string $phone, string $password): void
    {
        $browser->type('@phone', $phone)
                ->type('@password', $password)
                ->click('@submit')
                ->waitForLocation('/driver/dashboard');
    }
}
