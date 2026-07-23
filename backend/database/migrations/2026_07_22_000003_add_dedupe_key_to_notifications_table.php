<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chuyển `type` từ enum DB → string: thêm loại thông báo mới (trip_driver_*)
        // không còn phải sửa enum ở tầng DB (vốn phiền trên cả MySQL lẫn SQLite).
        // Ràng buộc giá trị vẫn được bảo đảm bởi PHP enum cast (NotificationTypeEnum).
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type', 50)->comment('Loại thông báo')->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Chống gửi trùng ở TẦNG DB khi job retry: unique constraint thay cho exists()+create().
            // MySQL & SQLite đều cho phép nhiều NULL trong unique index → notification thường để null.
            $table->string('dedupe_key', 191)->nullable()->after('data');
            $table->unique('dedupe_key');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });

        // Hoàn nguyên `type` về enum gốc (danh sách trong migration tạo bảng). Bỏ các
        // giá trị trip_driver_* mới trước khi đổi kiểu, nếu không MySQL sẽ lỗi truncate.
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'booking_confirmed', 'booking_cancelled', 'trip_reminder',
                'driver_arriving', 'trip_started', 'trip_completed',
                'payment_success', 'refund_processed', 'system',
            ])->comment('Loại thông báo')->change();
        });
    }
};
