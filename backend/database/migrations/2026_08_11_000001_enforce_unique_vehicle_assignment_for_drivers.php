<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dữ liệu cũ có thể đã gán một xe cho nhiều tài xế. Giữ assignment
        // được cập nhật gần nhất (ưu tiên tài xế chưa bị soft-delete), gỡ phần còn lại.
        DB::table('drivers')
            ->whereNotNull('current_vehicle_id')
            ->get(['id', 'current_vehicle_id', 'updated_at', 'deleted_at'])
            ->groupBy('current_vehicle_id')
            ->each(function ($drivers): void {
                if ($drivers->count() < 2) {
                    return;
                }

                $keeper = $drivers
                    ->sortByDesc(fn ($driver) => [
                        $driver->deleted_at === null ? 1 : 0,
                        (string) $driver->updated_at,
                    ])
                    ->first();

                DB::table('drivers')
                    ->whereIn('id', $drivers->pluck('id')->reject(fn ($id) => $id === $keeper->id))
                    ->update(['current_vehicle_id' => null]);
            });

        Schema::table('drivers', function (Blueprint $table): void {
            $table->unique('current_vehicle_id', 'drivers_current_vehicle_unique');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->dropUnique('drivers_current_vehicle_unique');
        });
    }
};
