<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendZaloNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ?string $zaloUserId,
        public readonly string $message,
        public readonly array $data = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if (!$this->zaloUserId) {
            return;
        }

        $accessToken = config('services.zalo.oa_access_token');

        $response = Http::withToken($accessToken)
                        ->post('https://openapi.zalo.me/v2.0/oa/message', [
                            'recipient' => ['user_id' => $this->zaloUserId],
                            'message'   => ['text' => $this->message],
                        ]);

        if (!$response->successful()) {
            Log::warning('Zalo OA gửi thất bại', [
                'zalo_user_id' => $this->zaloUserId,
                'error'        => $response->json('message'),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendZaloNotificationJob thất bại', [
            'zalo_user_id' => $this->zaloUserId,
            'error'        => $e->getMessage(),
        ]);
    }
}
