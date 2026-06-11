<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;

class VoucherService
{
    public function validate(string $code, int $subtotal, User $user, string $tripId): Voucher
    {
        $voucher = Voucher::where('code', strtoupper($code))->first();

        if (!$voucher) {
            throw new \InvalidArgumentException('Mã giảm giá không tồn tại');
        }

        if (!$voucher->isValid()) {
            throw new \InvalidArgumentException('Mã giảm giá đã hết hạn hoặc đã được sử dụng hết');
        }

        if ($subtotal < $voucher->min_order) {
            throw new \InvalidArgumentException(
                "Giá trị đơn tối thiểu là " . number_format($voucher->min_order, 0, ',', '.') . 'đ'
            );
        }

        // Kiểm tra user đã dùng voucher này chưa
        $alreadyUsed = VoucherUsage::where('voucher_id', $voucher->id)
                                   ->where('user_id', $user->id)
                                   ->exists();
        if ($alreadyUsed) {
            throw new \InvalidArgumentException('Bạn đã sử dụng mã giảm giá này rồi');
        }

        return $voucher;
    }

    public function calculate(?string $code, int $subtotal, User $user, string $tripId): int
    {
        if (!$code) {
            return 0;
        }

        try {
            $voucher = $this->validate($code, $subtotal, $user, $tripId);
            return $voucher->calculateDiscount($subtotal);
        } catch (\Exception) {
            return 0;
        }
    }

    public function markUsed(Voucher $voucher, Booking $booking, User $user, int $discountApplied): void
    {
        VoucherUsage::create([
            'voucher_id'       => $voucher->id,
            'booking_id'       => $booking->id,
            'user_id'          => $user->id,
            'discount_applied' => $discountApplied,
        ]);

        $voucher->increment('used_count');
    }
}
