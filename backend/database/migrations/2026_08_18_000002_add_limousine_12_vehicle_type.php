<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = [
        'sedan_4',
        'mpv_7',
        'van_9',
        'limousine_12',
        'minibus_16',
    ];

    public function up(): void
    {
        $this->alterMysqlEnum(self::TYPES);

        // Dữ liệu cũ dùng minibus_16 cho xe thực tế chỉ cấu hình 12 chỗ.
        // Chỉ chuyển đúng nhóm sai lệch; minibus 16 chỗ thật được giữ nguyên.
        DB::table('vehicles')
            ->where('vehicle_type', 'minibus_16')
            ->where('seat_count', 12)
            ->update(['vehicle_type' => 'limousine_12']);
    }

    public function down(): void
    {
        DB::table('vehicles')
            ->where('vehicle_type', 'limousine_12')
            ->update([
                'vehicle_type' => 'minibus_16',
                'seat_count' => 12,
            ]);

        $this->alterMysqlEnum([
            'sedan_4',
            'mpv_7',
            'van_9',
            'minibus_16',
        ]);
    }

    /** @param array<int, string> $types */
    private function alterMysqlEnum(array $types): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('vehicles', function (Blueprint $table) use ($types): void {
                $table->enum('vehicle_type', $types)
                    ->comment('Mã loại xe; số chỗ chuẩn do VehicleTypeEnum quy định')
                    ->change();
            });

            return;
        }

        $values = collect($types)
            ->map(fn (string $type): string => "'{$type}'")
            ->implode(', ');

        DB::statement(
            "ALTER TABLE vehicles MODIFY vehicle_type ENUM({$values}) NOT NULL "
            .'COMMENT \'Mã loại xe; số chỗ chuẩn do VehicleTypeEnum quy định\''
        );
    }
};
