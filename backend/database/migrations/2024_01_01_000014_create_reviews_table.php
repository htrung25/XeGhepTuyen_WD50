<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('driver_rating')->unsigned()->comment('Đánh giá tài xế 1-5');
            $table->tinyInteger('vehicle_rating')->unsigned()->comment('Đánh giá xe 1-5');
            $table->tinyInteger('service_rating')->unsigned()->comment('Đánh giá dịch vụ 1-5');
            $table->text('comment')->nullable()->comment('Bình luận (max 500 ký tự)');
            $table->boolean('is_published')->default(true)->comment('Hiển thị công khai');
            $table->timestamp('created_at')->useCurrent();

            $table->index('driver_id');
            $table->index('operator_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
