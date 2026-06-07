<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number', 15)->unique()->comment('Biển số xe');
            $table->string('brand', 50)->comment('Hãng xe: Toyota, Ford...');
            $table->string('model', 50)->comment('Dòng xe: Innova, Transit...');
            $table->string('color', 30)->nullable();
            $table->year('year')->nullable()->comment('Năm sản xuất');
            $table->enum('vehicle_type', ['sedan_4', 'mpv_7', 'van_9', 'minibus_16'])
                  ->comment('sedan_4=sedan 4 chỗ, mpv_7=MPV 7 chỗ, van_9=van 9 chỗ, minibus_16=16 chỗ');
            $table->tinyInteger('seat_count')->unsigned()->comment('Tổng số ghế');
            $table->string('registration_number', 50)->nullable()->comment('Số đăng ký xe');
            $table->date('registration_expiry')->nullable()->comment('Hạn đăng kiểm');
            $table->date('insurance_expiry')->nullable()->comment('Hạn bảo hiểm');
            $table->string('image_url', 500)->nullable();
            $table->json('amenities')->nullable()->comment('Tiện ích: ["wifi","usb","ac"]');
            $table->enum('status', ['active', 'maintenance', 'inactive'])
                  ->default('active')
                  ->comment('active=hoạt động, maintenance=bảo dưỡng, inactive=ngừng');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['operator_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
