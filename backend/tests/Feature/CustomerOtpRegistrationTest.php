<?php

use App\Exceptions\InvalidOtpException;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

it('logs the complete otp message immediately when local otp logging is enabled', function (): void {
    config()->set('services.otp.log_message', true);
    Queue::fake();
    Log::spy();

    $this->postJson('/api/customer/auth/send-otp', [
        'phone' => '0901234099',
    ])->assertOk();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $event, array $context): bool => $event === '[OTP][LOCAL] Nội dung tin nhắn'
            && $context['phone'] === '0901234099'
            && preg_match('/Mã OTP của bạn là: \d{6}/', $context['message']) === 1);
});

it('requires a verified one-time proof before customer registration', function (): void {
    $this->postJson('/api/customer/auth/register', [
        'full_name' => 'Khách chưa xác thực',
        'phone' => '0901234001',
        'password' => 'Customer@123',
        'password_confirmation' => 'Customer@123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('verification_token');

    $this->assertDatabaseMissing('users', ['phone' => '0901234001']);
});

it('issues a proof after otp verification and consumes it during registration', function (): void {
    $otpService = app(OtpService::class);
    $otp = $otpService->send('0901234002');

    $verification = $this->postJson('/api/customer/auth/verify-otp', [
        'phone' => '0901234002',
        'otp' => $otp,
    ])->assertOk()
        ->assertJsonStructure(['data' => ['verification_token', 'expires_at']]);

    $token = $verification->json('data.verification_token');

    $this->postJson('/api/customer/auth/register', [
        'full_name' => 'Khách đã xác thực',
        'phone' => '0901234002',
        'password' => 'Customer@123',
        'password_confirmation' => 'Customer@123',
        'verification_token' => $token,
    ])->assertCreated()
        ->assertJsonPath('data.user.is_verified', true);

    expect(User::where('phone', '0901234002')->exists())->toBeTrue();
    expect(OtpVerification::query()->whereNotNull('consumed_at')->exists())->toBeTrue();
});

it('rejects a proof issued for another phone', function (): void {
    $otpService = app(OtpService::class);
    $otp = $otpService->send('0901234003');
    $proof = $otpService->verify('0901234003', $otp);

    $this->postJson('/api/customer/auth/register', [
        'full_name' => 'Sai số điện thoại',
        'phone' => '0901234004',
        'password' => 'Customer@123',
        'password_confirmation' => 'Customer@123',
        'verification_token' => $proof->plainToken,
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'Phiên xác thực OTP không hợp lệ hoặc đã hết hạn');

    $this->assertDatabaseMissing('users', ['phone' => '0901234004']);
});

it('rejects expired and already consumed registration proofs', function (): void {
    $expiredToken = str_repeat('a', 64);
    OtpVerification::create([
        'phone' => '0901234005',
        'purpose' => OtpVerification::PURPOSE_REGISTER,
        'token_hash' => hash('sha256', $expiredToken),
        'expires_at' => now()->subSecond(),
    ]);

    $payload = [
        'full_name' => 'Khách hết hạn',
        'phone' => '0901234005',
        'password' => 'Customer@123',
        'password_confirmation' => 'Customer@123',
        'verification_token' => $expiredToken,
    ];

    $this->postJson('/api/customer/auth/register', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Phiên xác thực OTP không hợp lệ hoặc đã hết hạn');

    $consumedToken = str_repeat('b', 64);
    OtpVerification::create([
        'phone' => '0901234006',
        'purpose' => OtpVerification::PURPOSE_REGISTER,
        'token_hash' => hash('sha256', $consumedToken),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => now(),
    ]);

    $payload['phone'] = '0901234006';
    $payload['verification_token'] = $consumedToken;

    $this->postJson('/api/customer/auth/register', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Phiên xác thực OTP không hợp lệ hoặc đã hết hạn');
});

it('limits incorrect otp verification attempts', function (): void {
    $service = app(OtpService::class);
    $correctOtp = $service->send('0901234007');

    foreach (range(1, 5) as $attempt) {
        try {
            $service->verify('0901234007', '000000');
        } catch (Throwable) {
            // Mỗi lần sai đều bị từ chối; lần thứ 5 đồng thời vô hiệu hóa OTP.
        }
    }

    expect(fn () => $service->verify('0901234007', $correctOtp))
        ->toThrow(InvalidOtpException::class);
    expect(Cache::has('otp:'.hash('sha256', '0901234007')))->toBeFalse();
});
