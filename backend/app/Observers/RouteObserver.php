<?php

namespace App\Observers;

use App\Models\Route;

/**
 * Đồng bộ vùng phục vụ trước MỌI lần persist Route — phủ controller, admin,
 * job, tinker mà không cần nhớ gọi. Hook `saving` chỉ mutate attribute trước
 * khi ghi nên không có recursion. Seeder/command dùng WithoutModelEvents hoặc
 * cần chắc chắn thì gọi thẳng syncServiceAreasFromCities().
 */
class RouteObserver
{
    public function saving(Route $route): void
    {
        $route->syncServiceAreasFromCities();
    }
}
