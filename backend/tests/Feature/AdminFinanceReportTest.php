<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** Cảnh báo bất thường + xuất CSV (P2). Helpers ở tests/Pest.php. */
function actingAsFinanceAdmin(): void
{
    Sanctum::actingAs(User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]));
}

it('phát hiện SĐT đặt nhiều vé bất thường', function () {
    // makeOperatorWithRevenue tạo các vé cùng contact_phone 0900000000
    makeOperatorWithRevenue(online: 3, cash: 0);
    actingAsFinanceAdmin();

    $this->getJson('/api/admin/finance/anomalies')
        ->assertOk()
        ->assertJsonPath('data.0.contact_phone', '0900000000')
        ->assertJsonPath('data.0.booking_count', 3);
});

it('không cảnh báo khi dưới ngưỡng', function () {
    makeOperatorWithRevenue(online: 1, cash: 0);
    actingAsFinanceAdmin();

    $this->getJson('/api/admin/finance/anomalies')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('xuất CSV giao dịch trả về file text/csv', function () {
    makeOperatorWithRevenue(online: 1, cash: 0);
    actingAsFinanceAdmin();

    $res = $this->get('/api/admin/finance/export?type=transactions');
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('text/csv');
});
