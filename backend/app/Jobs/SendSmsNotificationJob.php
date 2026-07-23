<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly ?string $bookingId = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $apiKey = config('services.esms.api_key');
        $secretKey = config('services.esms.secret_key');
        $brandName = config('services.esms.brand_name', 'XeGhep');

        // Fallback dev: ESMS chưa cấu hình → in nội dung SMS (gồm mật khẩu tạm lúc
        // duyệt nhà xe/tài xế) ra log/terminal thay vì gọi API (sẽ thất bại + retry).
        // Production có key thật thì nhánh này KHÔNG chạy → không rò rỉ.
        if (blank($apiKey) || blank($secretKey)) {
            // Log toàn bộ tin nhắn dạng cấu trúc (không bị cắt bởi terminal)
            Log::info('[SMS][DEV] Nội dung tin nhắn đầy đủ', [
                'phone' => $this->phone,
                'message' => $this->message,
            ]);

            return;
        }

        // Timeout tường minh: tránh call treo vượt retry_after → job bị retry khi vẫn chạy ⇒ SMS trùng.
        $response = Http::connectTimeout(5)
            ->timeout(15)
            ->post('https://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/', [
                'ApiKey' => $apiKey,
                'SecretKey' => $secretKey,
                'SMSTYPE' => 2,
                'Brandname' => $brandName,
                'Phone' => $this->phone,
                'Content' => $this->message,
            ]);

        if (! $response->successful() || ($response->json('CodeResult') ?? '') !== '100') {
            Log::warning('SMS gửi thất bại', [
                'phone' => $this->phone,
                'code' => $response->json('CodeResult'),
            ]);
            $this->fail(new \RuntimeException('ESMS trả về lỗi: '.$response->json('CodeResult')));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendSmsNotificationJob thất bại', [
            'phone' => $this->phone,
            'error' => $e->getMessage(),
        ]);
    }
}
