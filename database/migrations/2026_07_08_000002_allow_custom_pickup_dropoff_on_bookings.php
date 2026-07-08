<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Chuyển khóa ngoại sang nullable
            $table->foreignUuid('pickup_stop_id')->nullable()->change();
            $table->foreignUuid('dropoff_stop_id')->nullable()->change();

            // Thêm cột tọa độ điểm trả
            $table->decimal('dropoff_lat', 10, 8)->nullable()->after('dropoff_address');
            $table->decimal('dropoff_lng', 11, 8)->nullable()->after('dropoff_lat');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['dropoff_lat', 'dropoff_lng']);
            $table->foreignUuid('pickup_stop_id')->change();
            $table->foreignUuid('dropoff_stop_id')->change();
        });
    }
};
