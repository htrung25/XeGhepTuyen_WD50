<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Khóa nghiệp vụ tất định do caller sinh: "refund:{booking_id}",
            // "compensation:{booking_id}", "wallet_payment:{booking_id}".
            // NULLABLE: dòng lịch sử giữ NULL — MySQL cho phép nhiều NULL trong UNIQUE
            // index nên migration không vỡ vì dữ liệu cũ.
            // UNIQUE: bảo đảm CẤU TRÚC chống ghi trùng — queue redis là at-least-once,
            // lock ở tầng ứng dụng có thể mất khi refactor, constraint thì không.
            $table->string('idempotency_key', 191)->nullable()->unique()->after('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
