<?php

namespace App\Services;

use App\Enums\OperatorStatusEnum;
use App\Enums\PartnerApplicationStatusEnum;
use App\Enums\UserRoleEnum;
use App\Jobs\Notification\SendSmsNotificationJob;
use App\Models\Operator;
use App\Models\PartnerApplication;
use App\Models\User;
use App\Repositories\Contracts\PartnerApplicationRepositoryInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerApplicationService
{
    public function __construct(
        private readonly PartnerApplicationRepositoryInterface $applicationRepo,
        private readonly PrivateDocumentService $documents,
    ) {}

    /**
     * Khách gửi đơn đăng ký trở thành đối tác nhà xe (public, chưa có tài khoản).
     *
     * @param  array<string, mixed>  $data
     * @param  UploadedFile|null  $license  Giấy phép kinh doanh
     * @param  array<int, UploadedFile>  $fleetImages  Ảnh đội xe (tối đa 5)
     */
    public function submit(array $data, ?UploadedFile $license = null, array $fleetImages = []): PartnerApplication
    {
        // Chuẩn hóa cơ cấu đội xe về 4 loại + tính tổng (không tin số tổng từ FE)
        $breakdown = collect($data['fleet_breakdown'] ?? [])
            ->only(['sedan_4', 'mpv_7', 'van_9', 'minibus_16'])
            ->map(fn ($v) => (int) $v)
            ->all();

        $data['fleet_breakdown'] = $breakdown;
        $data['vehicle_count'] = array_sum($breakdown);

        $storedPaths = [];

        try {
            if ($license) {
                $data['business_license_path'] = $this->documents->store(
                    $license,
                    'partner-applications/licenses',
                );
                $storedPaths[] = $data['business_license_path'];
            }

            if (! empty($fleetImages)) {
                $data['fleet_image_paths'] = collect($fleetImages)
                    ->take(5)
                    ->map(function (UploadedFile $image) use (&$storedPaths): string {
                        $path = $this->documents->store($image, 'partner-applications/fleet');
                        $storedPaths[] = $path;

                        return $path;
                    })
                    ->all();
            }

            $data['status'] = PartnerApplicationStatusEnum::Pending->value;
            $application = $this->applicationRepo->create($data);
        } catch (\Throwable $e) {
            $this->documents->deleteMany($storedPaths);
            throw $e;
        }

        app(AdminNotificationService::class)->notify(
            'partner_applications.review',
            'Đơn đăng ký đối tác mới',
            "Nhà xe \"{$application->company_name}\" vừa gửi đơn đăng ký đối tác.",
            ['kind' => 'partner_application', 'link' => '/admin/operators', 'application_id' => $application->id],
        );

        return $application;
    }

    /**
     * Admin duyệt đơn → tạo tài khoản nhà xe (User role=operator + Operator verified).
     *
     * @throws DomainException
     */
    public function approve(PartnerApplication $application, float $commissionRate, User $admin): Operator
    {
        if (! $application->canApprove()) {
            throw new DomainException('Đơn này đã được xử lý, không thể duyệt lại');
        }

        if (User::where('phone', $application->phone)->exists()) {
            throw new DomainException('Số điện thoại của đơn đã tồn tại tài khoản, vui lòng xử lý thủ công');
        }

        // Cấp mật khẩu tạm (dễ đọc) để gửi cho nhà xe qua SMS
        $tempPassword = $this->generateTempPassword();
        $user = null;

        $operator = DB::transaction(function () use ($application, $commissionRate, $admin, $tempPassword, &$user) {
            $user = User::create([
                'full_name' => $application->representative_name,
                'phone' => $application->phone,
                'email' => $application->email,
                'password' => $tempPassword,   // cast 'hashed' tự băm
                'role' => UserRoleEnum::Operator,
                'is_verified' => true,
                'is_active' => true,
            ]);

            $operator = Operator::create([
                'user_id' => $user->id,
                'company_name' => $application->company_name,
                'business_license' => $application->tax_code,   // chờ nhà xe bổ sung số GPKD chính thức
                'tax_code' => $application->tax_code,
                'commission_rate' => $commissionRate,
                'description' => "Địa chỉ: {$application->address}. Đội xe khai báo: {$application->vehicle_count} xe ({$application->fleetSummary()}).",
                'license_path' => $application->business_license_path,
                'status' => OperatorStatusEnum::Verified,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            $application->update([
                'status' => PartnerApplicationStatusEnum::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'operator_id' => $operator->id,
            ]);

            return $operator;
        }, attempts: 3);

        // Gửi SMS thông tin đăng nhập — không để lỗi SMS làm hỏng việc duyệt
        $this->sendCredentialsSms($user, $tempPassword, $application->company_name);

        return $operator;
    }

    /**
     * Sinh mật khẩu tạm 8 ký tự, dễ đọc qua SMS (2 chữ in hoa + 6 chữ số).
     */
    private function generateTempPassword(): string
    {
        return Str::upper(Str::random(2)).random_int(100000, 999999);
    }

    /**
     * Gửi SMS thông tin đăng nhập cho nhà xe (async, fire-and-forget).
     */
    private function sendCredentialsSms(User $user, string $password, string $companyName): void
    {
        try {
            $loginUrl = rtrim((string) config('app.url'), '/').'/operator/login';
            $message = "[XeGhepTuyen-Fgroup] Hồ sơ đối tác \"{$companyName}\" đã được duyệt. "
                ."Đăng nhập tại {$loginUrl} — SĐT: {$user->phone}, Mật khẩu tạm: {$password}. "
                .'Vui lòng đổi mật khẩu sau khi đăng nhập.';

            SendSmsNotificationJob::dispatch($user->phone, $message)->onQueue('notifications');
        } catch (\Throwable $e) {
            Log::error('Gửi SMS cấp tài khoản nhà xe thất bại', [
                'phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Admin từ chối đơn đăng ký.
     *
     * @throws DomainException
     */
    public function reject(PartnerApplication $application, string $reason, User $admin): void
    {
        if ($application->status === PartnerApplicationStatusEnum::Approved) {
            throw new DomainException('Đơn đã duyệt, không thể từ chối');
        }

        $application->update([
            'status' => PartnerApplicationStatusEnum::Rejected,
            'note' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }
}
