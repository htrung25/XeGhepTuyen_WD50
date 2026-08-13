<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_fare_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            // null = dòng mặc định. (tỉnh, huyện) → (tỉnh, null) → (null, null)
            // là thứ tự fallback khi tra giá, xem FarePricingService::resolveRate.
            $table->string('province_code', 10)->nullable()->comment('Mã tỉnh, null = mặc định nhà xe');
            $table->string('district_code', 10)->nullable()->comment('Mã huyện, null = mặc định cả tỉnh');
            $table->string('province_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->decimal('base_fare', 10, 0)->default(0)->comment('Phí mở cửa (đồng)');
            $table->decimal('price_per_km', 10, 2)->comment('Đơn giá mỗi km (đồng)');
            $table->timestamps();

            // MySQL coi mỗi NULL là khác nhau nên unique index KHÔNG chặn được
            // trùng dòng mặc định — kiểm tra trùng thực hiện ở FormRequest.
            $table->unique(['operator_id', 'province_code', 'district_code'], 'fare_rates_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_fare_rates');
    }
};
