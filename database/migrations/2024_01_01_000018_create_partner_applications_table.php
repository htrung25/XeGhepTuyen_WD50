<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Đơn đăng ký trở thành đối tác nhà xe (lead) — khách gửi từ landing page,
        // admin xem & duyệt. Khi duyệt sẽ tạo tài khoản Operator tương ứng.
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Thông tin doanh nghiệp
            $table->string('company_name', 200)->comment('Tên nhà xe / công ty');
            $table->string('tax_code', 50)->comment('Mã số thuế');
            $table->string('address', 500)->comment('Địa chỉ trụ sở');

            // Thông tin đội xe
            $table->unsignedSmallInteger('vehicle_count')->comment('Số lượng xe hiện có');
            $table->enum('vehicle_type', ['sedan_4', 'mpv_7', 'limousine', 'mixed'])
                ->comment('sedan_4=xe 4 chỗ, mpv_7=xe 7 chỗ, limousine=limousine, mixed=hỗn hợp');

            // Thông tin liên hệ
            $table->string('representative_name', 100)->comment('Họ tên người đại diện');
            $table->string('phone', 15)->comment('SĐT liên hệ');
            $table->string('email', 100)->nullable();

            // Hồ sơ đính kèm
            $table->string('business_license_url', 500)->nullable()->comment('URL giấy phép kinh doanh');
            $table->json('fleet_images')->nullable()->comment('Mảng URL ảnh đội xe');

            // Xử lý
            $table->enum('status', ['pending', 'contacted', 'approved', 'rejected'])
                ->default('pending')
                ->comment('pending=chờ xử lý, contacted=đã liên hệ, approved=đã duyệt, rejected=từ chối');
            $table->string('note', 500)->nullable()->comment('Ghi chú admin / lý do từ chối');
            $table->uuid('reviewed_by')->nullable()->comment('admin user_id đã xử lý');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('operator_id')->nullable()->comment('Operator được tạo khi duyệt');

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
