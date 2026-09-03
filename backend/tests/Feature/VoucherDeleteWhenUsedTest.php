<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Laravel\Sanctum\Sanctum;

function actAsSuperAdmin(): void
{
    Sanctum::actingAs(User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]));
}

/**
 * Voucher ĐÃ CÓ NGƯỜI DÙNG phải xoá được. Xoá mềm để giữ lịch sử sử dụng
 * (voucher_usages khai báo cascadeOnDelete nên xoá cứng sẽ xoá sạch lịch sử)
 * và không để bookings.voucher_id trỏ vào khoảng không.
 */
function usedVoucher(): array
{
    $voucher = Voucher::create([
        'code' => 'USED'.fake()->unique()->numerify('####'),
        'discount_type' => 'percent', 'discount_value' => 10,
        'min_order' => 0, 'usage_limit' => 100, 'used_count' => 1,
        'valid_from' => now()->subDay(), 'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);

    // Cần booking + user THẬT vì voucher_usages ràng buộc khoá ngoại cả hai.
    makeOperatorWithRevenue(online: 1, cash: 0);
    $booking = Booking::firstOrFail();

    $usage = VoucherUsage::create([
        'voucher_id' => $voucher->id,
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'discount_applied' => 15000,
    ]);

    return [$voucher, $usage];
}

it('xoá được voucher đã có lượt sử dụng', function () {
    [$voucher] = usedVoucher();
    actAsSuperAdmin();

    $this->deleteJson("/api/admin/vouchers/{$voucher->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    // Biến mất khỏi danh sách quản trị / không áp được cho đơn mới…
    expect(Voucher::find($voucher->id))->toBeNull();
    expect(Voucher::where('code', $voucher->code)->first())->toBeNull();
    // …nhưng bản ghi vẫn còn để đối soát.
    expect(Voucher::withTrashed()->find($voucher->id))->not->toBeNull();
});

it('giữ nguyên lịch sử sử dụng sau khi xoá voucher', function () {
    [$voucher, $usage] = usedVoucher();
    actAsSuperAdmin();

    $this->deleteJson("/api/admin/vouchers/{$voucher->id}")
        ->assertOk();

    expect(VoucherUsage::find($usage->id))->not->toBeNull();
    expect(VoucherUsage::where('voucher_id', $voucher->id)->count())->toBe(1);
});
