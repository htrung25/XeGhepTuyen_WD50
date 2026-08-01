<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('ticket_number')->default(0);
        });

        $latestCode = DB::table('support_tickets')->max('ticket_code');
        $latestNumber = is_string($latestCode) ? (int) substr($latestCode, 3) : 0;

        DB::table('support_ticket_sequences')->insert([
            'id' => 1,
            'ticket_number' => $latestNumber,
        ]);

        Schema::table('support_messages', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });

        Schema::dropIfExists('support_ticket_sequences');
    }
};
