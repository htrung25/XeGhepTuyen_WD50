<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Tài xế đã thu tiền mặt (chỉ dùng cho method=cash)
            $table->uuid('collected_by')->nullable()->after('paid_at')
                  ->comment('driver_id thu tiền mặt (chỉ với thanh toán tiền mặt)');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('collected_by');
        });
    }
};
