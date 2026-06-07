<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_code', 20)->unique()->comment('Mã vé: HNHP240615001');
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pickup_stop_id')->constrained('route_stops');
            $table->foreignUuid('dropoff_stop_id')->constrained('route_stops');
            $table->string('pickup_address', 500)->nullable()->comment('Địa chỉ đón chi tiết');
            $table->string('dropoff_address', 500)->nullable()->comment('Địa chỉ trả chi tiết');
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->tinyInteger('passenger_count')->unsigned()->default(1)->comment('Số hành khách');
            $table->string('contact_name', 100)->comment('Tên người liên hệ');
            $table->string('contact_phone', 15)->comment('SĐT người liên hệ');
            $table->text('note')->nullable()->comment('Ghi chú cho tài xế');
            $table->decimal('subtotal', 10, 0)->comment('Tổng tiền trước giảm giá');
            $table->decimal('discount_amount', 10, 0)->default(0)->comment('Số tiền giảm giá');
            $table->decimal('final_amount', 10, 0)->comment('Số tiền cần thanh toán');
            $table->enum('payment_method', ['momo', 'vnpay', 'zalopay', 'wallet', 'cash'])
                  ->default('momo')
                  ->comment('Phương thức thanh toán');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'partial_refund'])
                  ->default('unpaid')
                  ->comment('unpaid=chưa TT, paid=đã TT, refunded=đã hoàn, partial_refund=hoàn một phần');
            $table->enum('booking_status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'])
                  ->default('pending')
                  ->comment('pending=chờ TT, confirmed=đã xác nhận, checked_in=đã lên xe, completed=hoàn thành, cancelled=đã hủy, no_show=không lên xe');
            $table->string('qr_code', 500)->nullable()->comment('URL ảnh QR code');
            $table->string('qr_token', 100)->unique()->nullable()->comment('Token xác thực QR');
            $table->uuid('voucher_id')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Hết hạn thanh toán (15 phút)');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['trip_id', 'booking_status']);
            $table->index('booking_code');
            $table->index('qr_token');
            $table->index(['expires_at', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
