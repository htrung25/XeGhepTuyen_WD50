<?php

namespace Tests\Browser\Pages\Admin;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class LoginPage extends Page
{
    public function url(): string
    {
        return '/admin/login';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
                ->assertSee('XeGhep')
                ->assertSee('Admin');
    }

    public function elements(): array
    {
        return [
            '@email'    => 'input[type="email"]',
            '@password' => 'input[type="password"]',
            '@submit'   => 'button[type="submit"]',
            '@error'    => '.text-red-700',
        ];
    }

    public function loginAs(Browser $browser, string $email, string $password): void
    {
        $browser->type('@email', $email)
                ->type('@password', $password)
                ->click('@submit')
                ->waitForLocation('/admin/dashboard', 10);
    }
}
