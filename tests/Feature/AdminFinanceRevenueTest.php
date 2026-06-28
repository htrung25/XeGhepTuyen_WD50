<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** Báo cáo doanh thu theo kỳ (P3). Trục = depart_at. Helpers ở tests/Pest.php. */
function actingAsRevenueAdmin(): void
{
    Sanctum::actingAs(User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]));
}

it('báo cáo doanh thu theo kỳ: tổng + theo nhà xe + biểu đồ', function () {
    // 2 vé momo 150.000đ, chuyến depart_at = hôm qua (xem makeOperatorWithRevenue)
    makeOperatorWithRevenue(online: 2, cash: 0);
    actingAsRevenueAdmin();

    $from = now()->subDays(3)->format('Y-m-d');
    $to = now()->addDay()->format('Y-m-d');

    $res = $this->getJson("/api/admin/finance/revenue?period=custom&from_date={$from}&to_date={$to}")
        ->assertOk()
        ->assertJsonPath('data.summary.gmv', 300000)
        ->assertJsonPath('data.summary.commission', 30000)
        ->assertJsonPath('data.summary.total_bookings', 2);

    expect($res->json('data.by_operator'))->toHaveCount(1);
    expect($res->json('data.by_operator.0.revenue'))->toBe(300000);
    expect($res->json('data.daily'))->not->toBeEmpty();
});

it('kỳ không có chuyến trả về 0', function () {
    makeOperatorWithRevenue(online: 1, cash: 0);
    actingAsRevenueAdmin();

    $this->getJson('/api/admin/finance/revenue?period=custom&from_date=2099-01-01&to_date=2099-12-31')
        ->assertOk()
        ->assertJsonPath('data.summary.gmv', 0)
        ->assertJsonPath('data.summary.total_bookings', 0)
        ->assertJsonCount(0, 'data.by_operator');
});

it('chặn admin không có quyền finance.view xem báo cáo', function () {
    $role = \App\Models\AdminRole::create([
        'name' => 'CSKH', 'slug' => 'cskh-no-finance',
        'permissions' => ['users.view'], 'is_super' => false,
    ]);
    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => $role->id]));

    $this->getJson('/api/admin/finance/revenue?period=month')->assertStatus(403);
});
