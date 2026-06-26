<?php

namespace Tests\Browser\Pages\Customer;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class RegisterPage extends Page
{
    public function url(): string
    {
        return '/register';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@name'     => 'input[type="text"]',
            '@phone'    => 'input[type="tel"]',
            '@password' => 'input[placeholder="Tối thiểu 6 ký tự"]',
            '@confirm'  => 'input[placeholder="Nhập lại mật khẩu"]',
            '@submit'   => 'button[type="submit"]',
            '@error'    => '.text-red-700',
            '@err-phone'=> 'p.text-red-500',
        ];
    }

    public function register(Browser $browser, string $name, string $phone, string $password): void
    {
        $browser->type('@name', $name)
                ->type('@phone', $phone)
                ->type('@password', $password)
                ->type('@confirm', $password)
                ->click('@submit');
    }
}
