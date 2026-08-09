<?php

use App\Jobs\Booking\ExpireLockedSeatsJob;
use App\Jobs\Notification\SendSmsNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('routes booking cleanup and sms notifications to independent queues', function () {
    expect((new ExpireLockedSeatsJob)->queue)->toBe('high')
        ->and((new SendSmsNotificationJob('0901234567', 'OTP: 123456'))->queue)
        ->toBe('notifications');
});

it('does not spam the info log when no locked seat expires', function () {
    Log::spy();

    (new ExpireLockedSeatsJob)->handle();

    Log::shouldNotHaveReceived('info');
});

it('writes the complete sms to the development log when esms is not configured', function () {
    config()->set('services.esms.api_key');
    config()->set('services.esms.secret_key');
    Log::spy();

    (new SendSmsNotificationJob('0901234567', 'OTP: 123456'))->handle();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('[SMS][DEV] Nội dung tin nhắn đầy đủ', [
            'phone' => '0901234567',
            'message' => 'OTP: 123456',
        ]);
});

it('keeps legacy job classes loadable while old redis payloads are draining', function () {
    expect(new App\Jobs\ExpireLockedSeatsJob)
        ->toBeInstanceOf(ExpireLockedSeatsJob::class)
        ->and(new App\Jobs\SendSmsNotificationJob('0901234567', 'OTP: 123456'))
        ->toBeInstanceOf(SendSmsNotificationJob::class);
});
