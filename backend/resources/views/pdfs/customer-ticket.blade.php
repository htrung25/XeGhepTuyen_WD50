<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Vé {{ $booking->booking_code }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { background: #2563eb; color: #fff; padding: 20px 24px; }
        .brand { font-size: 18px; font-weight: bold; }
        .code-label { color: #dbeafe; font-size: 10px; margin-top: 18px; }
        .code { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .body { border: 1px solid #dbeafe; border-top: 0; padding: 20px 24px; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 7px 0; vertical-align: top; width: 50%; }
        .label { color: #64748b; font-size: 10px; }
        .value { font-weight: bold; margin-top: 3px; }
        .divider { border-top: 1px dashed #cbd5e1; margin: 14px 0; }
        .qr { margin-top: 16px; text-align: center; }
        .qr img { height: 145px; width: 145px; }
        .note { color: #64748b; font-size: 10px; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">XeGhep.vn — Vé điện tử</div>
        <div class="code-label">Mã đặt vé</div>
        <div class="code">{{ $booking->booking_code }}</div>
    </div>
    <div class="body">
        <table>
            <tr>
                <td>
                    <div class="label">Tuyến đường</div>
                    <div class="value">{{ $booking->trip->route->origin_city }} → {{ $booking->trip->route->dest_city }}</div>
                </td>
                <td>
                    <div class="label">Khởi hành</div>
                    <div class="value">{{ $booking->trip->depart_at->format('H:i d/m/Y') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Hành khách</div>
                    <div class="value">{{ $booking->contact_name }} — {{ $booking->contact_phone }}</div>
                </td>
                <td>
                    <div class="label">Ghế</div>
                    <div class="value">{{ $booking->passengers->pluck('seatMap.seat_code')->filter()->join(', ') ?: '—' }}</div>
                </td>
            </tr>
        </table>
        <div class="divider"></div>
        <div><span class="label">Điểm đón:</span> <span class="value">{{ $booking->pickup_address ?: $booking->pickupStop?->address }}</span></div>
        <div style="margin-top: 8px"><span class="label">Điểm trả:</span> <span class="value">{{ $booking->dropoff_address ?: $booking->dropoffStop?->address }}</span></div>
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="QR vé">
            <div class="note">Xuất trình mã QR này để tài xế check-in khi đón bạn.</div>
        </div>
    </div>
</body>
</html>
