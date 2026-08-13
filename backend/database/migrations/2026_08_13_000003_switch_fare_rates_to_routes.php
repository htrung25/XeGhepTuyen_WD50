<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng giá đổi từ "theo tỉnh/huyện điểm đi" sang "theo TUYẾN": nhà xe tạo tuyến
 * trước, sau đó vào Cấu hình giá vé chọn đúng tuyến để gán đơn giá/km.
 *
 * Tạo lại bảng thay vì alter: tính năng chưa phát hành, dữ liệu bảng giá cũ
 * (nếu có ở môi trường dev) không còn ánh xạ được sang tuyến nào.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('operator_fare_rates');

        Schema::create('operator_fare_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('route_id')->constrained('routes')->cascadeOnDelete();
            $table->decimal('base_fare', 10, 0)->default(0)->comment('Phí mở cửa (đồng)');
            $table->decimal('price_per_km', 10, 2)->comment('Đơn giá mỗi km (đồng)');
            $table->timestamps();

            $table->unique(['operator_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_fare_rates');

        Schema::create('operator_fare_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->string('province_code', 10)->nullable();
            $table->string('district_code', 10)->nullable();
            $table->string('province_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->decimal('base_fare', 10, 0)->default(0);
            $table->decimal('price_per_km', 10, 2);
            $table->timestamps();

            $table->unique(['operator_id', 'province_code', 'district_code'], 'fare_rates_scope_unique');
        });
    }
};
