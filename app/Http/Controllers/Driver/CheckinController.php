<?php

namespace App\Http\Controllers\Driver;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckinController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'qr_token'       => ['required', 'string'],
            'cash_collected' => ['sometimes', 'boolean'],
        ]);

        $driver  = auth('driver')->user()->driver;
        $booking = Booking::where('qr_token', $request->qr_token)
                          ->with(['trip', 'user'])
                          ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Mã QR không hợp lệ'], 404);
        }

        if ($booking->trip->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'Mã QR không thuộc chuyến này'], 403);
        }

        if ($booking->booking_status !== BookingStatus::Confirmed) {
            return response()->json([
                'success' => false,
                'message' => "Vé này đã ở trạng thái: {$booking->booking_status->label()}",
            ], 422);
        }

        // Vé tiền mặt chưa thu → cần thu tiền trước/đồng thời check-in
        $cashDue = $booking->payment_method === PaymentMethod::Cash
            && $booking->payment_status === BookingPaymentStatus::Unpaid;

        // Chưa xác nhận thu tiền → báo cho app hiện popup "Thu Xđ tiền mặt", chưa check-in
        if ($cashDue && ! $request->boolean('cash_collected')) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'requires_cash'  => true,
                    'amount_due'     => (int) $booking->final_amount,
                    'booking_code'   => $booking->booking_code,
                    'passenger_name' => $booking->contact_name,
                ],
            ]);
        }

        try {
            // Thu tiền mặt (nếu cần) trước khi check-in
            if ($cashDue) {
                $this->paymentService->collectCash($booking, $driver->id);
            }

            $booking->update([
                'booking_status' => BookingStatus::CheckedIn,
                'checked_in_at'  => now(),
            ]);

            $booking->refresh()->load(['passengers.seatMap', 'pickupStop']);

            return response()->json([
                'success' => true,
                'message' => $cashDue
                    ? "Đã thu " . number_format($booking->final_amount, 0, ',', '.') . "đ & check-in: {$booking->contact_name}"
                    : "Check-in thành công: {$booking->contact_name}",
                'data'    => [
                    'requires_cash'   => false,
                    'booking_code'    => $booking->booking_code,
                    'passenger_name'  => $booking->contact_name,
                    'payment_status'  => $booking->payment_status->value,
                    'cash_collected'  => $cashDue ? (int) $booking->final_amount : 0,
                    'seat_codes'      => $booking->passengers->map(fn ($p) => $p->seatMap?->seat_code)->filter()->values(),
                    'pickup_stop'     => [
                        'stop_name' => $booking->pickupStop?->stop_name,
                        'address'   => $booking->pickupStop?->address,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Checkin failed', ['error' => $e->getMessage(), 'qr_token' => $request->qr_token]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }
}
