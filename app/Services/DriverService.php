<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DriverService
{
    /**
     * Nhà xe thêm tài xế → tạo tài khoản (User role=driver) + hồ sơ Driver status=pending.
     *
     * Tài khoản tạo với mật khẩu ngẫu nhiên CHƯA bàn giao (tài xế chưa đăng nhập được
     * khi chưa duyệt). Chỉ gửi SMS báo "đang chờ duyệt". Mật khẩu thật sự được cấp khi
     * admin DUYỆT (approveAndIssueCredentials).
     *
     * @param  array<string,mixed>  $data  Đã validate ở controller, gồm cả *_url ảnh (nếu có).
     */
    public function createByOperator(array $data, Operator $operator): Driver
    {
        $driver = DB::transaction(function () use ($data, $operator) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $this->generateTempPassword(),   // ngẫu nhiên, chưa bàn giao
                'role' => UserRole::Driver,
                'is_verified' => true,
                'is_active' => true,
            ]);

            return Driver::create([
                'user_id' => $user->id,
                'operator_id' => $operator->id,
                'license_number' => $data['license_number'],
                'license_class' => $data['license_class'],
                'license_expiry' => $data['license_expiry'],
                'id_card_number' => $data['id_card_number'],
                'id_card_front_url' => $data['id_card_front_url'] ?? null,
                'id_card_back_url' => $data['id_card_back_url'] ?? null,
                'license_front_url' => $data['license_front_url'] ?? null,
                'status' => DriverStatus::Pending,
            ]);
        }, attempts: 3);

        $this->sendPendingSms($driver->user, $operator->company_name);

        return $driver;
    }

    /**
     * Admin duyệt tài xế → kích hoạt (verified) + cấp mật khẩu đăng nhập mới + gửi SMS.
     *
     * @return string Mật khẩu tạm (plaintext) để admin bàn giao dự phòng khi SMS không tới.
     */
    public function approveAndIssueCredentials(Driver $driver): string
    {
        $tempPassword = $this->generateTempPassword();

        DB::transaction(function () use ($driver, $tempPassword) {
            $driver->update(['status' => DriverStatus::Verified, 'verified_at' => now()]);
            $driver->user->update(['password' => $tempPassword, 'is_active' => true]);
        });

        $this->sendCredentialsSms($driver->user, $tempPassword, $driver->operator?->company_name, approved: true);

        return $tempPassword;
    }

    /**
     * Cấp lại mật khẩu cho tài xế (operator hoặc admin) + gửi SMS.
     *
     * @return string Mật khẩu tạm mới (plaintext) để bàn giao cho tài xế.
     */
    public function resetPassword(Driver $driver): string
    {
        $tempPassword = $this->generateTempPassword();
        $driver->user->update(['password' => $tempPassword]);

        $this->sendCredentialsSms($driver->user, $tempPassword, $driver->operator?->company_name, approved: false);

        return $tempPassword;
    }

    /**
     * Sinh mật khẩu tạm 8 ký tự, dễ đọc qua SMS (2 chữ in hoa + 6 chữ số).
     */
    private function generateTempPassword(): string
    {
        return Str::upper(Str::random(2)).random_int(100000, 999999);
    }

    /**
     * SMS báo đã nhận hồ sơ, đang chờ admin duyệt (KHÔNG kèm mật khẩu).
     */
    private function sendPendingSms(User $user, ?string $companyName): void
    {
        try {
            $company = $companyName ?? 'nhà xe';
            $message = "[XeGhep] Đã nhận hồ sơ tài xế của bạn tại {$company}. "
                .'Tài khoản sẽ được kích hoạt và gửi mật khẩu sau khi admin duyệt GPLX.';

            SendSmsNotificationJob::dispatch($user->phone, $message)->onQueue('notifications');
        } catch (\Throwable $e) {
            Log::error('Driver pending SMS dispatch failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * SMS cấp thông tin đăng nhập cho tài xế (async, fire-and-forget).
     */
    private function sendCredentialsSms(User $user, string $password, ?string $companyName, bool $approved): void
    {
        try {
            $loginUrl = rtrim((string) config('app.url'), '/').'/driver/login';
            $intro = $approved
                ? 'Tài khoản tài xế của bạn đã được duyệt.'
                : 'Mật khẩu đăng nhập tài xế của bạn vừa được cấp lại.';

            $message = "[XeGhep] {$intro} "
                ."Đăng nhập tại {$loginUrl} — SĐT: {$user->phone}, Mật khẩu: {$password}. "
                .'Vui lòng đổi mật khẩu sau khi đăng nhập.';

            SendSmsNotificationJob::dispatch($user->phone, $message)->onQueue('notifications');
        } catch (\Throwable $e) {
            Log::error('Driver credentials SMS dispatch failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
