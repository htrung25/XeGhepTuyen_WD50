<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('route_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('driver_id')->constrained()->cascadeOnDelete();
            $table->dateTime('depart_at')->comment('Giờ xuất phát');
            $table->dateTime('arrive_at')->comment('Giờ đến dự kiến');
            $table->tinyInteger('available_seats')->unsigned()->comment('Số ghế còn trống');
            $table->decimal('price', 10, 0)->comment('Giá vé chuyến này (đồng)');
            $table->text('note')->nullable()->comment('Ghi chú nội bộ');
            $table->string('tracking_code', 20)->unique()->nullable()->comment('Mã theo dõi public');
            $table->enum('status', ['scheduled', 'boarding', 'in_progress', 'completed', 'cancelled'])
                  ->default('scheduled')
                  ->comment('scheduled=đã lên lịch, boarding=đang đón khách, in_progress=đang chạy, completed=hoàn thành, cancelled=đã hủy');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['route_id', 'depart_at', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('tracking_code');
            $table->index(['depart_at', 'status', 'available_seats']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
