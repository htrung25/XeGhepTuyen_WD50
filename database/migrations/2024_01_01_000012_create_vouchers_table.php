<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique()->comment('Mã giảm giá');
            $table->uuid('operator_id')->nullable()->comment('NULL = áp dụng toàn sàn');
            $table->enum('discount_type', ['percent', 'fixed'])
                  ->comment('percent=phần trăm, fixed=số tiền cố định');
            $table->decimal('discount_value', 10, 2)->comment('Giá trị giảm (% hoặc đồng)');
            $table->decimal('min_order', 10, 0)->default(0)->comment('Giá trị đơn tối thiểu');
            $table->decimal('max_discount', 10, 0)->nullable()->comment('Giảm tối đa (dùng cho percent)');
            $table->integer('usage_limit')->default(1)->comment('Tổng số lần được dùng');
            $table->integer('used_count')->default(0)->comment('Số lần đã dùng');
            $table->dateTime('valid_from')->comment('Thời điểm bắt đầu hiệu lực');
            $table->dateTime('valid_until')->comment('Thời điểm hết hiệu lực');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index(['valid_from', 'valid_until', 'is_active']);
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
