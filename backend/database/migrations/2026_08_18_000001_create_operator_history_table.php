<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->constrained('operators')->cascadeOnDelete();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40)->comment('booking | trip | vehicle | driver');
            $table->string('action', 80);
            $table->string('severity', 20)->default('info')->comment('info | success | warning | danger');
            $table->string('subject_type', 255)->nullable();
            $table->uuid('subject_id')->nullable();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('dedupe_key', 180)->nullable()->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['operator_id', 'occurred_at']);
            $table->index(['operator_id', 'category']);
            $table->index(['operator_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_history');
    }
};
