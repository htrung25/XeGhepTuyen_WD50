<?php

namespace App\Services;

use App\Models\Booking;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    private const QR_DISK = 'public';
    private const QR_PATH = 'qrcodes';

    public function generate(Booking $booking): string
    {
        $token   = $booking->qr_token ?? Str::random(32);
        $content = json_encode([
            'token'        => $token,
            'booking_code' => $booking->booking_code,
        ]);

        // Dùng trực tiếp bacon/bacon-qr-code (đã có sẵn qua laravel/fortify) —
        // KHÔNG dùng simplesoftwareio/simple-qrcode vì nó yêu cầu bacon ^2.0
        // xung đột với fortify (cần bacon ^3.0).
        $renderer = new ImageRenderer(
            new RendererStyle(300, 1),
            new ImagickImageBackEnd()
        );
        $qrImage = (new Writer($renderer))->writeString(
            $content,
            'UTF-8',
            ErrorCorrectionLevel::H()
        );

        $path = self::QR_PATH . "/qr_{$booking->booking_code}.png";
        Storage::disk(self::QR_DISK)->put($path, $qrImage);

        return Storage::disk(self::QR_DISK)->url($path);
    }

    public function verify(string $token): ?Booking
    {
        return Booking::where('qr_token', $token)->first();
    }
}
