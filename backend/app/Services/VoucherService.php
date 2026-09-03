<?php

namespace App\Services;

use App\Enums\BookingPaymentStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;

class VoucherService
{
    public function validate(string $code, int $subtotal, User $user, string|Trip $trip): Voucher
    {
        $voucher = Voucher::where('code', strtoupper($code))->first();

        return $this->validateVoucher($voucher, $subtotal, $user, $trip);
    }

    /** Validate và khóa voucher trong transaction tạo booking để chống vượt usage_limit. */
    public function reserve(string $code, int $subtotal, User $user, string|Trip $trip): Voucher
    {
        $voucher = Voucher::where('code', strtoupper($code))->lockForUpdate()->first();

        return $this->validateVoucher($voucher, $subtotal, $user, $trip);
    }

    private function validateVoucher(?Voucher $voucher, int $subtotal, User $user, string|Trip $trip): Voucher
    {

        if (! $voucher) {
            throw new \InvalidArgumentException('Mã giảm giá không tồn tại');
        }

        if (! $voucher->isValid()) {
            throw new \InvalidArgumentException('Mã giảm giá đã hết hạn hoặc đã được sử dụng hết');
        }

        if ($subtotal < $voucher->min_order) {
            throw new \InvalidArgumentException(
                'Giá trị đơn tối thiểu là '.number_format($voucher->min_order, 0, ',', '.').'đ'
            );
        }

        $trip = $trip instanceof Trip ? $trip : Trip::with('route')->find($trip);
        if (! $trip) {
            throw new \InvalidArgumentException('Chuyến đi không tồn tại');
        }
        $trip->loadMissing('route');
        if ($voucher->operator_id !== null && $voucher->operator_id !== $trip->route->operator_id) {
            throw new \InvalidArgumentException('Mã giảm giá không áp dụng cho nhà xe của chuyến này');
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
        if (! $code) {
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
            'voucher_id' => $voucher->id,
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'discount_applied' => $discountApplied,
        ]);

        $voucher->increment('used_count');
    }

    /** Trả lượt voucher khi booking chưa thanh toán bị hủy/hết hạn. */
    public function releaseUnpaid(Booking $booking): void
    {
        if (! $booking->voucher_id || $booking->payment_status !== BookingPaymentStatusEnum::Unpaid) {
            return;
        }

        // withTrashed: voucher có thể đã bị admin xoá (xoá mềm) sau khi khách
        // giữ chỗ — vẫn phải trả lượt/dọn usage của vé chưa thanh toán, nếu
        // không sẽ để lại usage rác chặn chính khách đó dùng voucher khác.
        $voucher = Voucher::withTrashed()->whereKey($booking->voucher_id)->lockForUpdate()->first();
        if (! $voucher) {
            return;
        }

        $deleted = VoucherUsage::where('booking_id', $booking->id)
            ->where('voucher_id', $voucher->id)
            ->delete();

        if ($deleted > 0 && $voucher->used_count > 0) {
            $voucher->decrement('used_count');
        }
    }
}
