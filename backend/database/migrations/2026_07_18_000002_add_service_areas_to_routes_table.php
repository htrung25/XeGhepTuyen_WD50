<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // Vùng phục vụ điểm đón/trả do NHÀ XE cấu hình trên tuyến — nguồn sự thật
            // duy nhất cho geofencing. FE chỉ gửi tọa độ, không bao giờ gửi area id.
            // Nullable: tuyến chưa cấu hình vùng → bỏ qua kiểm tra (tương thích ngược).
            $table->foreignUuid('pickup_service_area_id')->nullable()
                ->constrained('service_areas')->nullOnDelete()
                ->comment('Vùng cho phép điểm đón (VD: Hà Nội)');
            $table->foreignUuid('dropoff_service_area_id')->nullable()
                ->constrained('service_areas')->nullOnDelete()
                ->comment('Vùng cho phép điểm trả (VD: Hải Phòng)');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_service_area_id');
            $table->dropConstrainedForeignId('dropoff_service_area_id');
        });
    }
};
