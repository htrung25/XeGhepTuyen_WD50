<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher ĐÃ CÓ NGƯỜI DÙNG trước đây không xoá được, vì voucher_usages khai
     * báo cascadeOnDelete — xoá cứng sẽ kéo theo toàn bộ lịch sử sử dụng (ai
     * dùng, giảm bao nhiêu) và để lại bookings.voucher_id trỏ vào khoảng không.
     * Chuyển sang xoá mềm: admin xoá được như bình thường, voucher biến mất khỏi
     * danh sách và không áp dụng cho đơn mới, còn lịch sử vẫn nguyên vẹn.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
