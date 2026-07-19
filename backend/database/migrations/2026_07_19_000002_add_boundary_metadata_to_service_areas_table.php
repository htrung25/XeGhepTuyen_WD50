<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            // Truy vết nguồn gốc ranh giới — bắt buộc để phân biệt demo-v1 với
            // dữ liệu GADM thật, tránh seeder/import ghi đè nhầm lẫn nhau.
            $table->string('source', 50)->nullable()->comment('demo|GADM|OSM…');
            $table->string('source_version', 50)->nullable()->comment('VD: 4.1');
            $table->string('boundary_version', 50)->nullable()->comment('VD: demo-v1, gadm41-2026-07');
            $table->timestamp('imported_at')->nullable();
            $table->char('checksum', 64)->nullable()->comment('sha256 của WKT nguồn');
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_version', 'boundary_version', 'imported_at', 'checksum']);
        });
    }
};
