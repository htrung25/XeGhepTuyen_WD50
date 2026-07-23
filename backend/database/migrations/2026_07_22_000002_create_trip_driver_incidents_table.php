<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_driver_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Xóa trip thì xóa incident (lịch sử gắn chặt với chuyến).
            $table->foreignUuid('trip_id')->constrained('trips')->cascadeOnDelete();

            // Giữ record bằng chứng: không cho xóa tài xế đang có incident.
            $table->foreignUuid('reported_driver_id')->constrained('drivers')->restrictOnDelete();

            $table->string('reason', 255);
            $table->timestamp('reported_at');

            $table->string('status', 20)->default('open')->comment('open | resolved');
            $table->string('resolution', 20)->nullable()->comment('reassigned | cancelled (null khi còn open)');

            // Tài xế thay (khi reassigned) & người xử lý — nullable, không chặn xóa.
            $table->foreignUuid('replacement_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignUuid('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Tìm nhanh incident đang mở của một chuyến (bất biến: tối đa 1 open/trip).
            $table->index(['trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_driver_incidents');
    }
};
