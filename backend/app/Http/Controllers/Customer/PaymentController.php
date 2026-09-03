<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethodEnum;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PaymentVerificationException;
use App\Exceptions\TripActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\InitiatePaymentRequest;
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

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $booking = Booking::findOrFail($validated['booking_id']);

        if ($booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập', 'code' => 'FORBIDDEN'], 403);
        }

        try {
            $result = $this->paymentService->initiate($booking, PaymentMethodEnum::from($validated['method']));

            return response()->json([
                'success' => true,
                'message' => 'Khởi tạo thanh toán thành công',
                'data' => $result,
            ]);
        } catch (BookingExpiredException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BOOKING_EXPIRED'], 422);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'INSUFFICIENT_BALANCE'], 422);
        } catch (TripActionException $e) {
            // Chuyến đang sắp xếp lại tài xế → chưa cho khởi tạo thanh toán mới (§6.3).
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => $e->errorCode], $e->status);
        } catch (\InvalidArgumentException $e) {
            // Vé không ở trạng thái chờ / chuyến đã khởi hành / phương thức không hỗ trợ
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'PAYMENT_NOT_ALLOWED'], 422);
        } catch (\Exception $e) {
            Log::error('Payment initiate failed', ['error' => $e->getMessage(), 'booking' => $validated['booking_id']]);

            return response()->json(['success' => false, 'message' => 'Không thể khởi tạo thanh toán, vui lòng thử lại'], 500);
        }
    }

    public function momoCallback(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleMomoCallback($request->all());

            return response()->json(['resultCode' => 0]);
        } catch (PaymentVerificationException $e) {
            Log::warning('MoMo callback verification failed', [
                'order_id' => $request->input('orderId'),
                'request_id' => $request->input('requestId'),
                'reason' => $e->getMessage(),
            ]);

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

    public function sepayWebhook(Request $request): JsonResponse
    {
        try {
            $authHeader = (string) $request->header('Authorization');
            $configuredToken = (string) config('services.sepay.webhook_token');
            $expectedToken = 'Bearer '.$configuredToken;

            if ($configuredToken === '' || ! hash_equals($expectedToken, $authHeader)) {
                Log::warning('SePay webhook unauthorized access attempt');

                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $success = $this->paymentService->handleSepayWebhook($request->all());

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Giao dịch được xử lý thành công' : 'Không tìm thấy giao dịch hoặc giao dịch đã xử lý',
            ]);
        } catch (PaymentVerificationException $e) {
            Log::warning('SePay callback verification failed', [
                'transaction_id' => $request->input('id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('SePay callback error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
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
            'data' => [
                'booking_status' => $booking->booking_status->value,
                'payment_status' => $booking->payment_status->value,
                'expires_at' => $booking->expires_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Tra cứu vé theo mã giao dịch cổng thanh toán (orderId).
     *
     * Khách bị cổng chuyển hướng ra ngoài rồi quay lại nên toàn bộ state phía
     * client đã mất — trang kết quả chỉ còn orderId trên query string. KHÔNG tin
     * tham số redirect để kết luận đã trả tiền (client sửa được); trạng thái
     * thật lấy từ DB, do IPN server-to-server cập nhật.
     */
    public function statusByOrder(string $orderId): JsonResponse
    {
        $payment = Payment::with('booking')->where('gateway_order_id', $orderId)->first();

        if (! $payment || ! $payment->booking) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch'], 404);
        }

        if ($payment->booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $payment->booking->id,
                'booking_code' => $payment->booking->booking_code,
                'booking_status' => $payment->booking->booking_status->value,
                'payment_status' => $payment->booking->payment_status->value,
                'amount' => (int) $payment->amount,
            ],
        ]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $user = auth('customer')->user();
        $balance = $this->walletService->getBalance($user);
        $history = $this->walletService->getTransactions($user);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'balance_text' => number_format($balance, 0, ',', '.').'đ',
                'transactions' => $history->items(),
            ],
            'meta' => [
                'current_page' => $history->currentPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
