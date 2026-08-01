<?php

namespace App\Services;

use App\DTOs\OtpVerificationProofDTO;
use App\Exceptions\InvalidOtpException;
use App\Models\OtpVerification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_TTL = 300;  // 5 phút

    private const OTP_RATE_TTL = 3600; // 1 giờ

    private const OTP_MAX_SENDS = 5;

    private const OTP_MAX_VERIFY_ATTEMPTS = 5;

    private const PROOF_TTL = 600; // 10 phút để hoàn tất form đăng ký

    public function send(string $phone): string
    {
        $countKey = $this->cacheKey('otp_send_count', $phone);

        $count = $this->incrementCounter($countKey, self::OTP_RATE_TTL);
        if ($count > self::OTP_MAX_SENDS) {
            throw new InvalidOtpException('Vui lòng thử lại sau 1 giờ');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->cacheKey('otp', $phone), $this->hashOtp($otp), self::OTP_TTL);
        Cache::forget($this->cacheKey('otp_verify_count', $phone));

        return $otp;
    }

    public function verify(string $phone, string $otp): OtpVerificationProofDTO
    {
        $otpKey = $this->cacheKey('otp', $phone);
        $verifyCountKey = $this->cacheKey('otp_verify_count', $phone);
        $attempt = $this->incrementCounter($verifyCountKey, self::OTP_TTL);
        $storedHash = Cache::get($otpKey);

        if ($attempt > self::OTP_MAX_VERIFY_ATTEMPTS) {
            Cache::forget($otpKey);

            throw new InvalidOtpException('Bạn đã nhập sai OTP quá số lần cho phép');
        }

        if (! is_string($storedHash) || ! hash_equals($storedHash, $this->hashOtp($otp))) {
            if ($attempt === self::OTP_MAX_VERIFY_ATTEMPTS) {
                Cache::forget($otpKey);
            }

            throw new InvalidOtpException('Mã OTP không chính xác hoặc đã hết hạn');
        }

        Cache::forget($otpKey);
        Cache::forget($verifyCountKey);

        $plainToken = Str::random(64);
        $expiresAt = CarbonImmutable::now()->addSeconds(self::PROOF_TTL);

        DB::transaction(function () use ($phone, $plainToken, $expiresAt): void {
            OtpVerification::query()
                ->where('phone', $phone)
                ->where('purpose', OtpVerification::PURPOSE_REGISTER)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            OtpVerification::create([
                'phone' => $phone,
                'purpose' => OtpVerification::PURPOSE_REGISTER,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
            ]);
        });

        return new OtpVerificationProofDTO($plainToken, $expiresAt);
    }

    public function getRemainingAttempts(string $phone): int
    {
        $count = (int) Cache::get($this->cacheKey('otp_send_count', $phone), 0);

        return max(0, self::OTP_MAX_SENDS - $count);
    }

    private function incrementCounter(string $key, int $ttl): int
    {
        if (Cache::add($key, 1, $ttl)) {
            return 1;
        }

        return (int) Cache::increment($key);
    }

    private function cacheKey(string $namespace, string $phone): string
    {
        return $namespace.':'.hash('sha256', $phone);
    }

    private function hashOtp(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }
}
