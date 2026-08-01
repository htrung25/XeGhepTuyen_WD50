<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('gateway_order_id');
            $table->unique(['method', 'gateway_txn_id']);
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('idempotency_key', 191)->unique();
            $table->timestamp('processed_at');

            $table->index(['payment_id', 'processed_at']);
            $table->index(['booking_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['method', 'gateway_txn_id']);
            $table->dropUnique(['gateway_order_id']);
        });
    }
};
