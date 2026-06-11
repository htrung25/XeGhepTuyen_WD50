<?php

namespace App\Services;

use App\Jobs\SendSmsNotificationJob;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OperatorAccountService
{
    /**
     * Admin đặt lại mật khẩu nhà xe: sinh mật khẩu tạm mới, cập nhật tài khoản,
     * gửi SMS (best-effort) và TRẢ VỀ mật khẩu tạm (plaintext) để admin chuyển
     * trực tiếp cho nhà xe khi SMS không tới (ESMS chưa cấu hình / queue chưa chạy).
     */
    public function resetPassword(Operator $operator): string
    {
        $user = $operator->user;

        $tempPassword = $this->generateTempPassword();
        $user->update(['password' => $tempPassword]);   // cast 'hashed' tự băm

        $this->sendCredentialsSms($user, $tempPassword, $operator->company_name);

        return $tempPassword;
    }

    /**
     * Sinh mật khẩu tạm 8 ký tự, dễ đọc qua SMS (2 chữ in hoa + 6 chữ số).
     */
    public function generateTempPassword(): string
    {
        return Str::upper(Str::random(2)).random_int(100000, 999999);
    }

    /**
     * Gửi SMS mật khẩu mới cho nhà xe (async, fire-and-forget — không chặn việc reset).
     */
    private function sendCredentialsSms(User $user, string $password, string $companyName): void
    {
        try {
            $loginUrl = rtrim((string) config('app.url'), '/').'/operator/login';
            $message = "[XeGhep] Mật khẩu đăng nhập nhà xe \"{$companyName}\" đã được đặt lại. "
                ."Đăng nhập tại {$loginUrl} — SĐT: {$user->phone}, Mật khẩu mới: {$password}. "
                .'Vui lòng đổi mật khẩu sau khi đăng nhập.';

            SendSmsNotificationJob::dispatch($user->phone, $message)->onQueue('notifications');
        } catch (\Throwable $e) {
            Log::error('Reset operator password SMS dispatch failed', [
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
