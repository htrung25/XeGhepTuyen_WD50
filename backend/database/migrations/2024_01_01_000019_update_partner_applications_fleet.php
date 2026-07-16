<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chuyển từ "loại xe chủ đạo" (enum đơn) sang "cơ cấu đội xe theo loại" (JSON).
        // vehicle_count vẫn giữ làm tổng (tính ở BE) để sort/hiển thị nhanh.
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn('vehicle_type');
        });

        Schema::table('partner_applications', function (Blueprint $table) {
            $table->json('fleet_breakdown')->nullable()->after('vehicle_count')
                ->comment('Cơ cấu đội xe theo loại: {sedan_4,mpv_7,van_9,minibus_16}');
        });
    }

    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn('fleet_breakdown');
        });

        Schema::table('partner_applications', function (Blueprint $table) {
            $table->enum('vehicle_type', ['sedan_4', 'mpv_7', 'limousine', 'mixed'])
                ->default('mpv_7')
                ->after('vehicle_count');
        });
    }
};
