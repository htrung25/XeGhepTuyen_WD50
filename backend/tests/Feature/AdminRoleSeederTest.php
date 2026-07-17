<?php

use App\Models\AdminRole;
use Database\Seeders\AdminRoleSeeder;

it('thêm quyền support cho cskh mà không ghi đè quyền đã tùy chỉnh', function () {
    $role = AdminRole::create([
        'name' => 'CSKH tùy chỉnh',
        'slug' => 'cskh',
        'permissions' => ['finance.view'],
        'is_super' => false,
        'is_system' => false,
    ]);

    $this->seed(AdminRoleSeeder::class);

    $permissions = $role->refresh()->permissions;

    expect($permissions)
        ->toContain('finance.view')
        ->toContain('support_tickets.view')
        ->toContain('support_tickets.manage')
        ->not->toContain('users.ban');
});

it('tạo mới preset cskh với đầy đủ quyền support', function () {
    $this->seed(AdminRoleSeeder::class);

    expect(AdminRole::where('slug', 'cskh')->firstOrFail()->permissions)
        ->toContain('support_tickets.view')
        ->toContain('support_tickets.manage');
});
