<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // NOT NULL = chuyến đang "chờ sắp xếp lại" (tài xế đã báo không chạy được).
            // NULL = bình thường. Cờ dùng để query nhanh; lịch sử nằm ở trip_driver_incidents.
            $table->timestamp('driver_unavailable_at')->nullable()->after('cancel_reason')
                ->comment('Thời điểm tài xế báo không chạy được; NOT NULL = đang chờ đổi tài xế');
            $table->string('driver_unavailable_reason', 255)->nullable()->after('driver_unavailable_at')
                ->comment('Lý do tài xế nêu (bản sao nhanh của incident đang mở)');

            // Tìm nhanh các chuyến đang chờ sắp xếp lại.
            $table->index('driver_unavailable_at');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['driver_unavailable_at']);
            $table->dropColumn(['driver_unavailable_at', 'driver_unavailable_reason']);
        });
    }
};
