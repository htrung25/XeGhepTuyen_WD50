<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->uuid('booking_id')->nullable()->comment('Liên kết với booking nếu có');
            $table->enum('type', ['topup', 'payment', 'refund', 'payout', 'commission'])
                  ->comment('topup=nạp tiền, payment=thanh toán, refund=hoàn tiền, payout=rút tiền, commission=hoa hồng');
            $table->decimal('amount', 12, 0)->comment('Số tiền giao dịch (dương=nhận, âm=trừ)');
            $table->decimal('balance_after', 12, 0)->comment('Số dư sau giao dịch');
            $table->string('description', 500)->nullable()->comment('Mô tả giao dịch');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
