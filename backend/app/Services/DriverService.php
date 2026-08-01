<?php

namespace App\Services;

use App\DTOs\DriverTransitionResultDTO;
use App\Enums\DriverStatusEnum;
use App\Enums\UserRoleEnum;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use Closure;
use DomainException;
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
     * @param  array<string,mixed>  $data  Đã validate ở controller, gồm cả *_path ảnh (nếu có).
     */
    public function createByOperator(array $data, Operator $operator): Driver
    {
        $driver = DB::transaction(function () use ($data, $operator) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $this->generateTempPassword(),   // ngẫu nhiên, chưa bàn giao
                'role' => UserRoleEnum::Driver,
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
                'id_card_front_path' => $data['id_card_front_path'] ?? null,
                'id_card_back_path' => $data['id_card_back_path'] ?? null,
                'license_front_path' => $data['license_front_path'] ?? null,
                'status' => DriverStatusEnum::Pending,
            ]);
        }, attempts: 3);

        $this->sendPendingSms($driver->user, $operator->company_name);

        app(AdminNotificationService::class)->notify(
            'drivers.review',
            'Tài xế chờ duyệt',
            "Tài xế \"{$driver->user->full_name}\" (nhà xe {$operator->company_name}) đang chờ duyệt hồ sơ.",
            ['kind' => 'driver_pending', 'link' => '/admin/drivers', 'driver_id' => $driver->id],
        );

        return $driver;
    }

    /**
     * Admin duyệt tài xế → kích hoạt (verified) + cấp mật khẩu đăng nhập mới + gửi SMS.
     *
     * Duyệt hồ sơ và cập nhật tài khoản trong cùng một state transition có khóa.
     */
    public function approveAndIssueCredentials(Driver $driver): DriverTransitionResultDTO
    {
        $tempPassword = $this->generateTempPassword();

        $result = $this->transition(
            driver: $driver,
            from: DriverStatusEnum::Pending,
            to: DriverStatusEnum::Verified,
            attributes: [
                'verified_at' => now(),
                'reject_reason' => null,
            ],
            sideEffect: function (Driver $locked) use ($tempPassword): void {
                $locked->user->update([
                    'password' => $tempPassword,
                    'is_active' => true,
                    'must_change_password' => true,   // bắt buộc đổi MK khi đăng nhập lần đầu
                ]);
            },
        );

        // Log rõ ràng để dev xem mật khẩu tạm khi chưa cấu hình SMS thật.
        Log::info('[DEV] Tài xế được duyệt — thông tin đăng nhập tạm thời', [
            'driver' => $result->driver->user->full_name,
            'phone' => $result->driver->user->phone,
            'temp_password' => $tempPassword,
            'login_url' => rtrim((string) config('app.url'), '/').'/driver/login',
        ]);

        $this->sendCredentialsSms(
            $result->driver->user,
            $tempPassword,
            $result->driver->operator?->company_name,
            approved: true,
        );

        return $result;
    }

    /**
     * Từ chối hồ sơ tài xế đang chờ duyệt.
     */
    public function reject(Driver $driver, string $reason): DriverTransitionResultDTO
    {
        return $this->transition(
            driver: $driver,
            from: DriverStatusEnum::Pending,
            to: DriverStatusEnum::Rejected,
            attributes: [
                'reject_reason' => $reason,
                'verified_at' => null,
            ],
        );
    }

    /**
     * Cấp lại mật khẩu cho tài xế (operator hoặc admin) + gửi SMS.
     *
     * @return string Mật khẩu tạm mới (plaintext) để bàn giao cho tài xế.
     */
    public function resetPassword(Driver $driver): string
    {
        $tempPassword = $this->generateTempPassword();
        $driver->user->update([
            'password' => $tempPassword,
            'must_change_password' => true,   // bắt buộc đổi MK khi đăng nhập lại
        ]);

        // Log rõ ràng để dev xem mật khẩu tạm khi chưa cấu hình SMS thật.
        Log::info('[DEV] Cấp lại mật khẩu tài xế', [
            'driver' => $driver->user->full_name,
            'phone' => $driver->user->phone,
            'temp_password' => $tempPassword,
            'login_url' => rtrim((string) config('app.url'), '/').'/driver/login',
        ]);

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
     * Nguồn duy nhất cho các transition trạng thái hồ sơ duyệt/từ chối.
     * Row lock và kiểm tra trạng thái nằm trong cùng transaction để ngăn TOCTOU.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function transition(
        Driver $driver,
        DriverStatusEnum $from,
        DriverStatusEnum $to,
        array $attributes = [],
        ?Closure $sideEffect = null,
    ): DriverTransitionResultDTO {
        return DB::transaction(function () use ($driver, $from, $to, $attributes, $sideEffect) {
            $locked = Driver::whereKey($driver->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== $from) {
                throw new DomainException('Tài xế này không ở trạng thái chờ duyệt');
            }

            $oldStatus = $locked->status;
            $locked->update([...$attributes, 'status' => $to]);
            $sideEffect?->__invoke($locked);
            $locked->load(['user', 'operator']);

            return new DriverTransitionResultDTO(
                driver: $locked,
                oldStatus: $oldStatus,
                newStatus: $to,
            );
        }, attempts: 3);
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
