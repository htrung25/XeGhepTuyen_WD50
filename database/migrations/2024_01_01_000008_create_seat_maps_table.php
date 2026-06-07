<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_maps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trip_id')->constrained()->cascadeOnDelete();
            $table->string('seat_code', 10)->comment('Mã ghế: A1, A2, B1...');
            $table->enum('seat_type', ['standard', 'vip'])
                  ->default('standard')
                  ->comment('standard=thường, vip=VIP');
            $table->decimal('price', 10, 0)->comment('Giá ghế (đồng)');
            $table->enum('status', ['available', 'locked', 'booked', 'disabled'])
                  ->default('available')
                  ->comment('available=còn trống, locked=đang giữ, booked=đã đặt, disabled=hỏng');
            $table->timestamp('locked_at')->nullable()->comment('Thời điểm bắt đầu lock ghế');
            $table->uuid('locked_by')->nullable()->comment('user_id đang giữ ghế');

            $table->unique(['trip_id', 'seat_code']);
            $table->index(['trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_maps');
    }
};
