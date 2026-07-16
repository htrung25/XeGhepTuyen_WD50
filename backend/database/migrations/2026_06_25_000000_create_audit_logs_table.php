<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin user who performed the action');
            $table->string('action', 100)->comment('Action performed');
            $table->string('model_type', 255)->nullable()->comment('Associated model class');
            $table->uuid('model_id')->nullable()->comment('Associated model ID');
            $table->text('description')->nullable()->comment('Friendly description of the action');
            $table->json('old_values')->nullable()->comment('Data before change');
            $table->json('new_values')->nullable()->comment('Data after change');
            $table->string('ip_address', 45)->nullable()->comment('Client IP address');
            $table->string('user_agent', 500)->nullable()->comment('User Agent');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
