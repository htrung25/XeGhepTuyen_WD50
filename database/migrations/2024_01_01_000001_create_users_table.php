<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 100);
            $table->string('phone', 15)->unique()->comment('Số điện thoại đăng nhập');
            $table->string('email', 100)->unique()->nullable();
            $table->string('password', 255);
            $table->enum('role', ['customer', 'driver', 'operator', 'admin'])
                  ->default('customer')
                  ->comment('customer=khách hàng, driver=tài xế, operator=nhà xe, admin=quản trị');
            $table->string('avatar_url', 500)->nullable();
            $table->string('zalo_user_id', 100)->nullable()->comment('Zalo OA binding');
            $table->string('fcm_token', 500)->nullable()->comment('Firebase FCM push token');
            $table->boolean('is_verified')->default(false)->comment('Đã xác thực SĐT qua OTP');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index(['role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
