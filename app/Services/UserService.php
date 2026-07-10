<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Khóa tài khoản người dùng: vô hiệu hóa + thu hồi TOÀN BỘ token đăng nhập.
     * Bọc transaction để 2 bảng (users + personal_access_tokens) luôn nhất quán.
     */
    public function ban(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update(['is_active' => false]);
            $user->tokens()->delete();
        });
    }

    public function unban(User $user): void
    {
        $user->update(['is_active' => true]);
    }
}
