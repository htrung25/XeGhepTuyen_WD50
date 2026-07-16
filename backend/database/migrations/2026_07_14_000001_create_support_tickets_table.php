<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_code', 20)->unique()->comment('Mã hỗ trợ: TK-000001');
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 255)->comment('Tiêu đề hỗ trợ');
            $table->enum('category', ['general', 'payment', 'refund', 'complaint', 'technical', 'other'])
                ->default('other')
                ->comment('Danh mục hỗ trợ');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])
                ->default('open')
                ->comment('Trạng thái: open=chờ xử lý, in_progress=đang xử lý, resolved=đã giải quyết, closed=đã đóng');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                ->default('normal')
                ->comment('Mức độ ưu tiên');
            $table->string('booking_code', 50)->nullable()->comment('Mã đặt vé liên quan (nếu có)');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete()->comment('Admin/Nhân viên được giao xử lý');
            $table->timestamp('resolved_at')->nullable()->comment('Thời điểm giải quyết');
            $table->timestamp('closed_at')->nullable()->comment('Thời điểm đóng ticket');
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
