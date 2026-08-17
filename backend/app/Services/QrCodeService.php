<?php

namespace App\Services;

use App\Models\Booking;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    private const QR_DISK = 'public';

    private const QR_PATH = 'qrcodes';

    public function generate(Booking $booking): string
    {
        $qrImage = $this->renderSvg($booking);
        $path = self::QR_PATH."/qr_{$booking->booking_code}.svg";
        Storage::disk(self::QR_DISK)->put($path, $qrImage);

        return Storage::disk(self::QR_DISK)->url($path);
    }

    public function renderSvg(Booking $booking): string
    {
        if (! $booking->qr_token) {
            throw new \LogicException('Booking chưa có QR token.');
        }

        $content = json_encode([
            'token' => $booking->qr_token,
            'booking_code' => $booking->booking_code,
        ], JSON_THROW_ON_ERROR);
        // Dùng trực tiếp bacon/bacon-qr-code (đã có sẵn qua laravel/fortify) —
        // KHÔNG dùng simplesoftwareio/simple-qrcode vì nó yêu cầu bacon ^2.0
        // xung đột với fortify (cần bacon ^3.0).
        $renderer = new ImageRenderer(
            new RendererStyle(300, 1),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString(
            $content,
            'UTF-8',
            ErrorCorrectionLevel::H()
        );
    }
}
