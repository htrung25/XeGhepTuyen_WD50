<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (test in-memory) không có kiểu spatial: giữ nguyên cặp cột
        // lat/lng vật lý — GeometryFactory::coordinateAttributes ghi theo driver.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->geometry('pickup_point', subtype: 'point', srid: 4326)->nullable()
                ->comment('Nguồn sự thật tọa độ đón (WGS 84)');
            $table->geometry('dropoff_point', subtype: 'point', srid: 4326)->nullable()
                ->comment('Nguồn sự thật tọa độ trả (WGS 84)');
        });

        // Backfill từ dữ liệu cũ — WKT theo thứ tự (lng lat), constructor luôn long-lat
        DB::statement(<<<'SQL'
            update bookings set
                pickup_point = if(pickup_lat is null or pickup_lng is null, null,
                    ST_GeomFromText(concat('POINT(', pickup_lng, ' ', pickup_lat, ')'), 4326, 'axis-order=long-lat')),
                dropoff_point = if(dropoff_lat is null or dropoff_lng is null, null,
                    ST_GeomFromText(concat('POINT(', dropoff_lng, ' ', dropoff_lat, ')'), 4326, 'axis-order=long-lat'))
            SQL);

        // lat/lng thành GENERATED COLUMN đọc từ POINT — một nguồn dữ liệu duy nhất,
        // không thể lệch nhau; mọi code đọc (resources, tracking, FE) giữ nguyên tên cột.
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('pickup_lat', 10, 8)->nullable()->storedAs('ST_Latitude(pickup_point)');
            $table->decimal('pickup_lng', 11, 8)->nullable()->storedAs('ST_Longitude(pickup_point)');
            $table->decimal('dropoff_lat', 10, 8)->nullable()->storedAs('ST_Latitude(dropoff_point)');
            $table->decimal('dropoff_lng', 11, 8)->nullable()->storedAs('ST_Longitude(dropoff_point)');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->decimal('dropoff_lat', 10, 8)->nullable();
            $table->decimal('dropoff_lng', 11, 8)->nullable();
        });

        DB::statement(<<<'SQL'
            update bookings set
                pickup_lat = ST_Latitude(pickup_point),
                pickup_lng = ST_Longitude(pickup_point),
                dropoff_lat = ST_Latitude(dropoff_point),
                dropoff_lng = ST_Longitude(dropoff_point)
            SQL);

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_point', 'dropoff_point']);
        });
    }
};
