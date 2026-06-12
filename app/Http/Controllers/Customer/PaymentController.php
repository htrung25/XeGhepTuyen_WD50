<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly WalletService $walletService,
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'uuid', 'exists:bookings,id'],
            'method'     => ['required', 'in:momo,vnpay,zalopay,wallet,cash'],
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        try {
            $result = $this->paymentService->initiate($booking, PaymentMethod::from($request->method));

            return response()->json([
                'success' => true,
                'message' => 'Khởi tạo thanh toán thành công',
                'data'    => $result,
            ]);
        } catch (BookingExpiredException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BOOKING_EXPIRED'], 422);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'INSUFFICIENT_BALANCE'], 422);
        } catch (\Exception $e) {
            Log::error('Payment initiate failed', ['error' => $e->getMessage(), 'booking' => $request->booking_id]);
            return response()->json(['success' => false, 'message' => 'Không thể khởi tạo thanh toán, vui lòng thử lại'], 500);
        }
    }

    public function momoCallback(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleMomoCallback($request->all());
            return response()->json(['resultCode' => 0]);
        } catch (PaymentVerificationException $e) {
            Log::warning('MoMo callback verification failed', ['payload' => $request->all()]);
            return response()->json(['resultCode' => 1], 400);
        } catch (\Exception $e) {
            Log::error('MoMo callback error', ['error' => $e->getMessage()]);
            return response()->json(['resultCode' => 1], 500);
        }
    }

    public function vnpayCallback(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleVnpayCallback($request->all());
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        } catch (PaymentVerificationException $e) {
            Log::warning('VNPay callback verification failed', ['payload' => $request->all()]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid Signature'], 400);
        } catch (\Exception $e) {
            Log::error('VNPay callback error', ['error' => $e->getMessage()]);
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown Error'], 500);
        }
    }

    public function status(string $bookingId): JsonResponse
    {
        $booking = Booking::with('payment')->findOrFail($bookingId);

        if ($booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'booking_status' => $booking->booking_status->value,
                'payment_status' => $booking->payment_status->value,
                'expires_at'     => $booking->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $user    = auth('customer')->user();
        $balance = $this->walletService->getBalance($user);
        $history = $this->walletService->getTransactions($user);

        return response()->json([
            'success' => true,
            'data'    => [
                'balance'      => $balance,
                'balance_text' => number_format($balance, 0, ',', '.') . 'đ',
                'transactions' => $history->items(),
            ],
            'meta'    => [
                'current_page' => $history->currentPage(),
                'total'        => $history->total(),
            ],
        ]);
    }
}
