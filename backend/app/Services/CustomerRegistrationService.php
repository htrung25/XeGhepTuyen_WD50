<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Exceptions\InvalidOtpException;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerRegistrationService
{
    /**
     * @param  array{full_name:string,phone:string,email?:?string,password:string,verification_token:string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $verification = OtpVerification::query()
                ->where('token_hash', hash('sha256', $data['verification_token']))
                ->where('phone', $data['phone'])
                ->where('purpose', OtpVerification::PURPOSE_REGISTER)
                ->lockForUpdate()
                ->first();

            if (! $verification || $verification->consumed_at || $verification->expires_at->isPast()) {
                throw new InvalidOtpException('Phiên xác thực OTP không hợp lệ hoặc đã hết hạn');
            }

            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => UserRoleEnum::Customer,
                'is_verified' => true,
            ]);

            $verification->update(['consumed_at' => now()]);

            return $user;
        });
    }
}
