<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class LoginPage extends Page
{
    public function url(): string
    {
        return '/login';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@phone'         => 'input[type="tel"]',
            '@password'      => 'input[type="password"]',
            '@submit'        => 'button[type="submit"]',
            '@error'         => '.text-red-700',
            '@register-link' => 'a[href="/register"]',
        ];
    }

    public function loginAs(Browser $browser, string $phone, string $password): void
    {
        $browser->type('@phone', $phone)
                ->type('@password', $password)
                ->click('@submit')
                ->waitForLocation('/home');
    }
}
