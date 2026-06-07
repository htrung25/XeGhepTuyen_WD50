<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('seat_map_id')->constrained('seat_maps')->cascadeOnDelete();
            $table->string('full_name', 100)->comment('Họ tên hành khách');
            $table->string('phone', 15)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable()
                  ->comment('male=nam, female=nữ, other=khác');
            $table->boolean('is_primary')->default(false)->comment('Người đặt chính');

            $table->index('booking_id');
            $table->index('seat_map_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_passengers');
    }
};
