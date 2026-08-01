<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->renameColumn('id_card_front_url', 'id_card_front_path');
            $table->renameColumn('id_card_back_url', 'id_card_back_path');
            $table->renameColumn('license_front_url', 'license_front_path');
        });

        Schema::table('partner_applications', function (Blueprint $table): void {
            $table->renameColumn('business_license_url', 'business_license_path');
            $table->renameColumn('fleet_images', 'fleet_image_paths');
        });

        Schema::table('operators', function (Blueprint $table): void {
            $table->renameColumn('license_url', 'license_path');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->renameColumn('id_card_front_path', 'id_card_front_url');
            $table->renameColumn('id_card_back_path', 'id_card_back_url');
            $table->renameColumn('license_front_path', 'license_front_url');
        });

        Schema::table('partner_applications', function (Blueprint $table): void {
            $table->renameColumn('business_license_path', 'business_license_url');
            $table->renameColumn('fleet_image_paths', 'fleet_images');
        });

        Schema::table('operators', function (Blueprint $table): void {
            $table->renameColumn('license_path', 'license_url');
        });
    }
};
