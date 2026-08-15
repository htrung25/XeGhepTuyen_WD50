<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // Huyện PHẢI nằm ở cột riêng: origin_city/dest_city được khớp chuỗi
            // chính xác bởi CityCodeResolver (geofencing), TripService (liên
            // tuyến) và TripRepository (tìm chuyến) — ghép "Tỉnh - Huyện" vào
            // đó sẽ làm hỏng cả ba. Nullable cho tuyến cũ chưa có huyện.
            $table->string('origin_district', 100)->nullable()->after('origin_city')
                ->comment('Quận/huyện điểm đi, VD: Quận Ba Đình');
            $table->string('dest_district', 100)->nullable()->after('dest_city')
                ->comment('Quận/huyện điểm đến');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['origin_district', 'dest_district']);
        });
    }
};
