<?php

namespace App\Services;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentVerificationException;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Khởi tạo thanh toán — trả về URL chuyển hướng
     */
    public function initiate(Booking $booking, PaymentMethod $method): array
    {
        if ($booking->isExpired()) {
            throw new \App\Exceptions\BookingExpiredException();
        }

        // Chuyến đã khởi hành → không cho thanh toán/confirm (tránh tạo vé "mồ côi")
        if ($booking->trip->depart_at->isPast()) {
            throw new \InvalidArgumentException('Chuyến đã khởi hành, không thể thanh toán vé này');
        }

        // Đồng bộ phương thức thật do khách chọn ở bước thanh toán
        // (booking được tạo với method tạm thời ở bước checkout)
        if ($booking->payment_method !== $method) {
            $booking->update(['payment_method' => $method]);
        }

        $payment = Payment::create([
            'booking_id'       => $booking->id,
            'user_id'          => $booking->user_id,
            'amount'           => $booking->final_amount,
            'method'           => $method,
            'status'           => PaymentStatus::Pending,
            'gateway_order_id' => 'XEGHEP-' . strtoupper(Str::random(10)),
        ]);

        return match($method) {
            PaymentMethod::Momo    => $this->initiateMomo($payment, $booking),
            PaymentMethod::Vnpay   => $this->initiateVnpay($payment, $booking),
            PaymentMethod::Wallet  => $this->initiateWallet($payment, $booking),
            PaymentMethod::Cash    => $this->initiateCash($payment, $booking),
            default                => throw new \InvalidArgumentException('Phương thức thanh toán không hỗ trợ'),
        };
    }

    /**
     * Xử lý callback từ MoMo — verify HMAC + idempotency
     */
    public function handleMomoCallback(array $payload): bool
    {
        $this->verifyMomoSignature($payload);
        return $this->processCallback($payload['orderId'], $payload['transId'] ?? null, $payload);
    }

    /**
     * Xử lý callback từ VNPay — verify HMAC + idempotency
     */
    public function handleVnpayCallback(array $payload): bool
    {
        $this->verifyVnpaySignature($payload);
        $success = ($payload['vnp_ResponseCode'] ?? '') === '00';
        return $this->processCallback(
            $payload['vnp_TxnRef'],
            $payload['vnp_TransactionNo'] ?? null,
            $payload,
            $success
        );
    }

    /**
     * Hoàn tiền vé
     */
    public function refund(Booking $booking, int $amount): void
    {
        DB::transaction(function () use ($booking, $amount) {
            $payment = $booking->payment;

            if (!$payment || !$payment->isSuccessful()) {
                return;
            }

            // Hoàn về ví nội bộ
            $this->walletService->credit(
                $booking->user,
                $amount,
                "Hoàn tiền vé {$booking->booking_code}",
                $booking->id
            );

            $payment->update([
                'status'       => PaymentStatus::Refunded,
                'refund_amount' => $amount,
                'refunded_at'  => now(),
            ]);

            $booking->update(['payment_status' => BookingPaymentStatus::Refunded]);
        });
    }

    private function processCallback(string $orderId, ?string $gatewayTxnId, array $payload, bool $success = true): bool
    {
        $payment = Payment::where('gateway_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('Payment callback: không tìm thấy payment', ['order_id' => $orderId]);
            return false;
        }

        // Idempotency check — tránh xử lý 2 lần
        if ($payment->status === PaymentStatus::Success) {
            return true;
        }

        if (!$success) {
            $payment->update(['status' => PaymentStatus::Failed, 'gateway_response' => $payload]);
            return false;
        }

        DB::transaction(function () use ($payment, $gatewayTxnId, $payload) {
            $payment->update([
                'status'          => PaymentStatus::Success,
                'gateway_txn_id'  => $gatewayTxnId,
                'gateway_response'=> $payload,
                'paid_at'         => now(),
            ]);

            $booking = $payment->booking;
            $booking->update([
                'payment_status' => BookingPaymentStatus::Paid,
                'booking_status' => BookingStatus::Confirmed,
                'confirmed_at'   => now(),
            ]);

            event(new \App\Events\PaymentProcessed($booking, $payment));
        });

        return true;
    }

    private function initiateMomo(Payment $payment, Booking $booking): array
    {
        $endpoint = config('services.momo.endpoint');
        $partnerCode = config('services.momo.partner_code');
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');
        $redirectUrl = config('app.url') . '/payment/momo/return';
        $ipnUrl = config('app.url') . '/api/public/payments/momo/callback';

        $rawHash = "accessKey={$accessKey}&amount={$payment->amount}&extraData=&ipnUrl={$ipnUrl}&orderId={$payment->gateway_order_id}&orderInfo=Vé xe {$booking->booking_code}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$payment->id}&requestType=captureWallet";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        try {
            $response = \Illuminate\Support\Facades\Http::post($endpoint . '/v2/gateway/api/create', [
                'partnerCode' => $partnerCode,
                'requestId'   => $payment->id,
                'amount'      => $payment->amount,
                'orderId'     => $payment->gateway_order_id,
                'orderInfo'   => "Vé xe {$booking->booking_code}",
                'redirectUrl' => $redirectUrl,
                'ipnUrl'      => $ipnUrl,
                'lang'        => 'vi',
                'requestType' => 'captureWallet',
                'extraData'   => '',
                'signature'   => $signature,
            ]);

            return ['payment_url' => $response->json('payUrl'), 'order_id' => $payment->gateway_order_id];
        } catch (\Exception $e) {
            Log::error('MoMo initiate failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Không thể kết nối cổng thanh toán MoMo');
        }
    }

    private function initiateVnpay(Payment $payment, Booking $booking): array
    {
        $tmnCode   = config('services.vnpay.tmn_code');
        $hashSecret = config('services.vnpay.hash_secret');
        $vnpUrl    = config('services.vnpay.url');

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $tmnCode,
            'vnp_Locale'     => 'vn',
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $payment->gateway_order_id,
            'vnp_OrderInfo'  => "Thanh toan ve xe {$booking->booking_code}",
            'vnp_OrderType'  => 'other',
            'vnp_Amount'     => $payment->amount * 100,
            'vnp_ReturnUrl'  => config('app.url') . '/payment/vnpay/return',
            'vnp_IpAddr'     => request()->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'),
        ];

        ksort($inputData);
        $hashData = urldecode(http_build_query($inputData));
        $vnpSecureHash = hash_hmac('sha512', $hashData, $hashSecret);
        $inputData['vnp_SecureHash'] = $vnpSecureHash;

        $paymentUrl = $vnpUrl . '?' . http_build_query($inputData);

        return ['payment_url' => $paymentUrl, 'order_id' => $payment->gateway_order_id];
    }

    private function initiateWallet(Payment $payment, Booking $booking): array
    {
        $this->walletService->debit(
            $booking->user,
            $payment->amount,
            "Thanh toán vé {$booking->booking_code}",
            $booking->id
        );

        $this->processCallback($payment->gateway_order_id, 'WALLET-' . time(), [], true);

        return ['payment_url' => null, 'status' => 'paid'];
    }

    private function initiateCash(Payment $payment, Booking $booking): array
    {
        // Vé tiền mặt: xác nhận giữ chỗ NGAY, thu tiền khi lên xe.
        // expires_at = null ⇒ isExpired() trả false ⇒ ExpireUnpaidBookingJob KHÔNG hủy vé.
        // payment_status vẫn 'unpaid' (nghĩa: chờ tài xế thu tiền mặt).
        DB::transaction(function () use ($payment, $booking) {
            $payment->update(['status' => PaymentStatus::Pending]); // chờ tài xế thu
            $booking->update([
                'booking_status' => BookingStatus::Confirmed,
                'confirmed_at'   => now(),
                'expires_at'     => null,
            ]);
        });

        // Thông báo xác nhận đặt vé (tương tự luồng online)
        event(new \App\Events\BookingConfirmed($booking->fresh()));

        return [
            'payment_url' => null,
            'status'      => 'confirmed_unpaid',
            'message'     => 'Đặt vé thành công. Vui lòng thanh toán tiền mặt khi lên xe.',
        ];
    }

    /**
     * Tài xế thu tiền mặt khi đón khách → đánh dấu đã thanh toán.
     *
     * @throws \InvalidArgumentException nếu vé không phải tiền mặt hoặc đã thanh toán
     */
    public function collectCash(Booking $booking, string $driverId): Payment
    {
        if ($booking->payment_method !== PaymentMethod::Cash) {
            throw new \InvalidArgumentException('Vé này không phải thanh toán tiền mặt');
        }
        if ($booking->payment_status === BookingPaymentStatus::Paid) {
            throw new \InvalidArgumentException('Vé này đã được thanh toán');
        }

        return DB::transaction(function () use ($booking, $driverId) {
            // Lấy payment cash đang chờ; nếu chưa có thì tạo mới (an toàn)
            $payment = Payment::where('booking_id', $booking->id)
                              ->where('method', PaymentMethod::Cash)
                              ->latest()
                              ->first()
                ?? Payment::create([
                    'booking_id'       => $booking->id,
                    'user_id'          => $booking->user_id,
                    'amount'           => $booking->final_amount,
                    'method'           => PaymentMethod::Cash,
                    'status'           => PaymentStatus::Pending,
                    'gateway_order_id' => 'CASH-' . strtoupper(Str::random(10)),
                ]);

            $payment->update([
                'status'       => PaymentStatus::Success,
                'paid_at'      => now(),
                'collected_by' => $driverId,
            ]);

            $booking->update(['payment_status' => BookingPaymentStatus::Paid]);

            return $payment;
        });
    }

    private function verifyMomoSignature(array $payload): void
    {
        $secretKey = config('services.momo.secret_key');
        $accessKey = config('services.momo.access_key');

        $rawHash = "accessKey={$accessKey}&amount={$payload['amount']}&extraData={$payload['extraData']}&message={$payload['message']}&orderId={$payload['orderId']}&orderInfo={$payload['orderInfo']}&orderType={$payload['orderType']}&partnerCode={$payload['partnerCode']}&payType={$payload['payType']}&requestId={$payload['requestId']}&responseTime={$payload['responseTime']}&resultCode={$payload['resultCode']}&transId={$payload['transId']}";
        $expected = hash_hmac('sha256', $rawHash, $secretKey);

        if ($expected !== ($payload['signature'] ?? '')) {
            throw new PaymentVerificationException('Chữ ký MoMo không hợp lệ');
        }
    }

    private function verifyVnpaySignature(array $payload): void
    {
        $hashSecret  = config('services.vnpay.hash_secret');
        $secureHash  = $payload['vnp_SecureHash'] ?? '';
        $inputData   = array_filter($payload, fn($k) => !in_array($k, ['vnp_SecureHash', 'vnp_SecureHashType']), ARRAY_FILTER_USE_KEY);
        ksort($inputData);
        $hashData  = urldecode(http_build_query($inputData));
        $expected  = hash_hmac('sha512', $hashData, $hashSecret);

        if ($expected !== $secureHash) {
            throw new PaymentVerificationException('Chữ ký VNPay không hợp lệ');
        }
    }
}
