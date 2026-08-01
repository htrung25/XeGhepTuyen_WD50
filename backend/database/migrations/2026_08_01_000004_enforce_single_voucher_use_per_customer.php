<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->dropUnique(['voucher_id', 'user_id', 'booking_id']);
            $table->unique(['voucher_id', 'user_id']);
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
            $table->dropUnique(['voucher_id', 'user_id']);
            $table->unique(['voucher_id', 'user_id', 'booking_id']);
        });
    }
};
