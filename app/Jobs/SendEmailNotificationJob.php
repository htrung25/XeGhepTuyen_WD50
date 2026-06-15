<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;

    public function __construct(
        public readonly string $email,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $data = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        Mail::html($this->body, function ($message) {
            $message->to($this->email)
                    ->subject($this->subject)
                    ->from(config('mail.from.address'), config('mail.from.name', 'XeGhep'));
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendEmailNotificationJob thất bại', [
            'email' => $this->email,
            'error' => $e->getMessage(),
        ]);
    }
}
