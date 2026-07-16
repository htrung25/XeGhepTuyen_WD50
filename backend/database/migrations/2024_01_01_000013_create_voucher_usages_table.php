<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_applied', 10, 0)->comment('Số tiền giảm thực tế');
            $table->timestamp('used_at')->useCurrent()->comment('Thời điểm áp dụng voucher');

            $table->unique(['voucher_id', 'user_id', 'booking_id']);
            $table->index(['voucher_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
