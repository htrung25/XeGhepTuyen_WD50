<?php

use App\Enums\UserRole;
use App\Models\PartnerApplication;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** Dashboard admin: khớp field FE + "nhà xe chờ duyệt" = đơn đối tác pending. */
function actingAsDashboardAdmin(): void
{
    Sanctum::actingAs(User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]));
}

it('trả về đúng các field KPI mà FE đọc', function () {
    actingAsDashboardAdmin();

    $this->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'stats' => [
                    'bookings_today',
                    'revenue_today',
                    'active_trips',
                    'new_users_today',
                    'pending_operators',
                    'pending_drivers',
                ],
                'recent_bookings',
            ],
        ]);
});

it('pending_operators đếm đơn đăng ký đối tác đang chờ duyệt', function () {
    PartnerApplication::create([
        'company_name' => 'NX Chờ Duyệt', 'tax_code' => '0100',
        'address' => 'HN', 'vehicle_count' => 1,
        'fleet_breakdown' => ['sedan_4' => 1], 'representative_name' => 'A',
        'phone' => '0911110000', 'email' => 'a@x.vn', 'status' => 'pending',
    ]);
    actingAsDashboardAdmin();

    $this->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.stats.pending_operators', 1);
});

it('chặn admin không có quyền dashboard.view', function () {
    $role = \App\Models\AdminRole::create([
        'name' => 'Kế toán', 'slug' => 'kt-no-dash',
        'permissions' => ['finance.view'], 'is_super' => false,
    ]);
    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => $role->id]));

    $this->getJson('/api/admin/dashboard')->assertStatus(403);
});
