<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->string('license_number', 20)->unique()->comment('Số giấy phép lái xe');
            $table->string('license_class', 5)->comment('Hạng GPLX: B2, C, D, E');
            $table->date('license_expiry')->comment('Ngày hết hạn GPLX');
            $table->string('id_card_number', 20)->comment('Số CMND/CCCD');
            $table->string('id_card_front_url', 500)->nullable()->comment('Ảnh CMND mặt trước');
            $table->string('id_card_back_url', 500)->nullable()->comment('Ảnh CMND mặt sau');
            $table->string('license_front_url', 500)->nullable()->comment('Ảnh GPLX mặt trước');
            $table->decimal('rating_avg', 3, 2)->default(5.00)->comment('Điểm đánh giá trung bình (1-5)');
            $table->integer('total_trips')->default(0);
            $table->boolean('is_online')->default(false)->comment('Đang sẵn sàng nhận chuyến');
            $table->decimal('current_lat', 10, 8)->nullable()->comment('Vĩ độ GPS hiện tại');
            $table->decimal('current_lng', 11, 8)->nullable()->comment('Kinh độ GPS hiện tại');
            $table->timestamp('location_updated_at')->nullable();
            $table->enum('status', ['pending', 'verified', 'suspended', 'rejected'])
                  ->default('pending')
                  ->comment('pending=chờ duyệt, verified=đã duyệt, suspended=đình chỉ, rejected=từ chối');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('operator_id');
            $table->index('status');
            $table->index('is_online');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
