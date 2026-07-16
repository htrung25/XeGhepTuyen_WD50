<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200)->comment('Tên tuyến VD: Hà Nội → Hải Phòng');
            $table->string('origin_city', 100)->default('Hà Nội');
            $table->string('dest_city', 100)->default('Hải Phòng');
            $table->smallInteger('distance_km')->unsigned()->default(120)->comment('Khoảng cách km');
            $table->smallInteger('est_duration_min')->unsigned()->default(120)->comment('Thời gian ước tính (phút)');
            $table->decimal('base_price', 10, 0)->comment('Giá cơ bản (đồng)');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_round_trip')->default(true)->comment('Có chiều về không');
            $table->timestamps();

            $table->index(['operator_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
