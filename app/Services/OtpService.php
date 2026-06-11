<?php

namespace App\Services;

use App\Exceptions\InvalidOtpException;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    private const OTP_TTL       = 300;  // 5 phút
    private const OTP_RATE_TTL  = 3600; // 1 giờ
    private const OTP_MAX_TRIES = 5;

    public function send(string $phone): string
    {
        $countKey = "otp_count:{$phone}";

        $count = (int) Cache::get($countKey, 0);
        if ($count >= self::OTP_MAX_TRIES) {
            throw new InvalidOtpException('Vui lòng thử lại sau 1 giờ');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("otp:{$phone}", $otp, self::OTP_TTL);
        Cache::put($countKey, $count + 1, self::OTP_RATE_TTL);

        return $otp;
    }

    public function verify(string $phone, string $otp): bool
    {
        $stored = Cache::get("otp:{$phone}");

        if (!$stored || $stored !== $otp) {
            throw new InvalidOtpException('Mã OTP không chính xác hoặc đã hết hạn');
        }

        Cache::forget("otp:{$phone}");

        return true;
    }

    public function getRemainingAttempts(string $phone): int
    {
        $count = (int) Cache::get("otp_count:{$phone}", 0);
        return max(0, self::OTP_MAX_TRIES - $count);
    }
}
