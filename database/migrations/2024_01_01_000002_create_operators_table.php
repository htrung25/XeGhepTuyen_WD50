<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name', 200);
            $table->string('business_license', 100)->comment('Số giấy phép kinh doanh vận tải');
            $table->string('tax_code', 20)->nullable()->comment('Mã số thuế');
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00)->comment('% hoa hồng nền tảng thu');
            $table->string('logo_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('license_url', 500)->nullable()->comment('URL ảnh giấy phép kinh doanh');
            $table->enum('status', ['pending', 'verified', 'suspended', 'rejected'])
                  ->default('pending')
                  ->comment('pending=chờ duyệt, verified=đã duyệt, suspended=đình chỉ, rejected=từ chối');
            $table->timestamp('verified_at')->nullable();
            $table->uuid('verified_by')->nullable()->comment('admin user_id đã duyệt');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
