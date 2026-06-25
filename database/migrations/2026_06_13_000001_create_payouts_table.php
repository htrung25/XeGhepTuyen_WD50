<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 0)->comment('Số tiền quyết toán (net, đồng)');
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])
                  ->default('pending')
                  ->comment('pending=chờ duyệt, approved=đã duyệt, paid=đã chi, rejected=từ chối');
            $table->string('note', 500)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->uuid('processed_by')->nullable()->comment('admin user_id xử lý');
            $table->timestamps();

            $table->index(['operator_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
