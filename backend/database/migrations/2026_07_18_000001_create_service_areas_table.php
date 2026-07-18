<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->comment('Tên tỉnh/thành VD: Hà Nội');
            $table->string('code', 20)->unique()->comment('Mã vùng VD: HN, HP');
            // SRID 4326 (WGS 84) — trùng hệ tọa độ lat/lng của Mapbox & GPS tài xế.
            // MySQL 8 với SRID 4326 hiểu trục theo thứ tự (lat, lng); khi build POINT
            // từ lng/lat phải kèm option 'axis-order=long-lat' (xem ServiceArea::scopeContainingPoint).
            $table->geometry('boundary', subtype: 'multipolygon', srid: 4326)->comment('Ranh giới tỉnh (MULTIPOLYGON, WGS 84)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // SPATIAL INDEX chỉ có trên MySQL (cột geometry NOT NULL là điều kiện bắt buộc).
        // SQLite (test in-memory) không hỗ trợ → bỏ qua, test vẫn migrate được.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('service_areas', function (Blueprint $table) {
                $table->spatialIndex('boundary');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
