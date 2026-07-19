<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // 'auto': hệ thống tự gán lại theo origin/dest_city mỗi lần lưu.
            // 'manual': admin/operator gán tay — sync không được ghi đè.
            $table->string('pickup_service_area_source', 10)->default('auto')
                ->comment('auto|manual — nguồn gán pickup_service_area_id');
            $table->string('dropoff_service_area_source', 10)->default('auto')
                ->comment('auto|manual — nguồn gán dropoff_service_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['pickup_service_area_source', 'dropoff_service_area_source']);
        });
    }
};
