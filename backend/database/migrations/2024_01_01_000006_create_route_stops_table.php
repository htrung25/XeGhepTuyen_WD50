<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('route_id')->constrained()->cascadeOnDelete();
            $table->string('stop_name', 100)->comment('Tên điểm dừng VD: Mỹ Đình');
            $table->string('address', 300)->comment('Địa chỉ đầy đủ');
            $table->decimal('lat', 10, 8)->comment('Vĩ độ (latitude)');
            $table->decimal('lng', 11, 8)->comment('Kinh độ (longitude)');
            $table->tinyInteger('stop_order')->unsigned()->comment('Thứ tự dừng trên tuyến');
            $table->smallInteger('offset_minutes')->unsigned()->default(0)->comment('Phút tính từ điểm xuất phát');
            $table->boolean('is_pickup')->default(true)->comment('Điểm đón khách');
            $table->boolean('is_dropoff')->default(true)->comment('Điểm trả khách');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['route_id', 'stop_order']);
            $table->index(['route_id', 'is_pickup']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
