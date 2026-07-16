<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->comment('Tên vai trò hiển thị');
            $table->string('slug', 100)->unique()->comment('Định danh duy nhất của vai trò');
            $table->text('description')->nullable()->comment('Mô tả vai trò');
            $table->json('permissions')->nullable()->comment('Danh sách key quyền (thuộc AdminPermission)');
            $table->boolean('is_super')->default(false)->comment('Bỏ qua mọi kiểm tra quyền');
            $table->boolean('is_system')->default(false)->comment('Vai trò hệ thống: không cho xóa/sửa quyền');
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
