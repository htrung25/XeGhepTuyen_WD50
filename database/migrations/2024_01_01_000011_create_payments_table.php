<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 0)->comment('Số tiền thanh toán (đồng)');
            $table->enum('method', ['momo', 'vnpay', 'zalopay', 'wallet', 'cash'])
                  ->comment('Phương thức thanh toán');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])
                  ->default('pending')
                  ->comment('pending=đang xử lý, success=thành công, failed=thất bại, refunded=đã hoàn');
            $table->string('gateway_txn_id', 100)->nullable()->comment('Mã giao dịch từ cổng TT');
            $table->string('gateway_order_id', 100)->nullable()->comment('Order ID gửi lên cổng TT');
            $table->json('gateway_response')->nullable()->comment('Raw response từ cổng thanh toán');
            $table->decimal('refund_amount', 10, 0)->default(0)->comment('Số tiền đã hoàn');
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('gateway_txn_id');
            $table->index(['status', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
