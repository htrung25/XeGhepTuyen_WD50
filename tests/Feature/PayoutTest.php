<?php

use App\Enums\UserRole;
use App\Models\Operator;
use App\Models\Payout;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * R1/R2/R4: đồng bộ luồng quyết toán operator↔admin (không chi trùng / mồ côi pending)
 * + atomic. Helper makeOperatorWithRevenue() định nghĩa ở tests/Pest.php (global).
 * settlement = online_net − cash_commission (SettlementService).
 */
it('operator gửi yêu cầu quyết toán = settlement; lần 2 hết số dư', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0); // settlement = 135.000
    // RevenueController dùng auth('operator') → cần token thật để cả middleware lẫn guard resolve.
    $headers = ['Authorization' => 'Bearer '.$operator->user->createToken('operator_token')->plainTextToken];

    $this->postJson('/api/operator/revenue/payout-request', [], $headers)
        ->assertCreated()
        ->assertJsonPath('data.amount', 135000);

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'pending')->sum('amount'))->toBe(135000);

    // Hết số dư khả dụng (đã có yêu cầu pending)
    $this->postJson('/api/operator/revenue/payout-request', [], $headers)->assertStatus(422);
});

it('admin quyết toán gộp yêu cầu pending: 1 paid = outstanding, pending → rejected', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    Payout::create(['operator_id' => $operator->id, 'amount' => 135000, 'status' => 'pending', 'requested_at' => now()]);

    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertCreated()
        ->assertJsonPath('data.amount', 135000);

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'paid')->sum('amount'))->toBe(135000);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'paid')->count())->toBe(1);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'pending')->count())->toBe(0);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'rejected')->count())->toBe(1);
});

it('admin KHÔNG chi khi nhà xe đang NỢ nền tảng (vé tiền mặt)', function () {
    $operator = makeOperatorWithRevenue(online: 0, cash: 1); // settlement = -15.000

    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertStatus(422)
        ->assertJsonPath('code', 'OPERATOR_OWES_PLATFORM');

    expect(Payout::where('operator_id', $operator->id)->where('status', 'paid')->count())->toBe(0);
});

it('admin chi lần 2 báo hết số dư (không chi trùng)', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Sanctum::actingAs($admin);
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])->assertCreated();
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertStatus(422)
        ->assertJsonPath('code', 'NOTHING_TO_SETTLE');

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'paid')->sum('amount'))->toBe(135000);
});
