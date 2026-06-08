<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('booking_id')->nullable()->comment('Liên kết booking nếu có');
            $table->enum('type', [
                'booking_confirmed', 'booking_cancelled', 'trip_reminder',
                'driver_arriving', 'trip_started', 'trip_completed',
                'payment_success', 'refund_processed', 'system',
            ])->comment('Loại thông báo');
            $table->string('title', 200)->comment('Tiêu đề thông báo');
            $table->text('body')->comment('Nội dung thông báo');
            $table->json('data')->nullable()->comment('Payload bổ sung');
            $table->enum('channel', ['push', 'sms', 'zalo', 'email', 'in_app'])
                  ->comment('Kênh gửi thông báo');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();

            $table->index(['user_id', 'is_read', 'sent_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
