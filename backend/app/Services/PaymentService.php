<?php

namespace App\Services;

use App\Enums\BookingPaymentStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\TripStatusEnum;
use App\Events\BookingConfirmedEvent;
use App\Events\PaymentProcessedEvent;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\PaymentVerificationException;
use App\Exceptions\TripActionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    public function initiate(Booking $booking, PaymentMethodEnum $method): array
    {
        // Mọi guard + tạo payment nằm TRONG transaction có khóa hàng: chặn 2 lần initiate
        // song song cùng vé (tạo payment trùng → có thể trừ ví 2 lần) và đọc cờ
        // driver_unavailable của chuyến NHẤT QUÁN với report (report khóa trip). Khóa theo
        // thứ tự trip → booking, đồng bộ completeTrip/cancelTrip để tránh deadlock.
        // Gọi cổng thanh toán (HTTP) đặt NGOÀI txn — không giữ khóa DB khi chờ mạng.
        [$payment, $booking] = DB::transaction(function () use ($booking, $method) {
            $trip = Trip::whereKey($booking->trip_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($booking->isExpired()) {
                throw new BookingExpiredException;
            }

            // Chỉ vé đang CHỜ mới được thanh toán. Chặn thanh toán lại vé đã xác nhận/
            // đã thanh toán/đã hủy/hoàn tất → tránh tạo payment trùng, đặc biệt ví bị TRỪ 2 lần.
            if ($booking->booking_status !== BookingStatusEnum::Pending) {
                throw new \InvalidArgumentException('Vé này không ở trạng thái chờ thanh toán');
            }

            // Chuyến đã khởi hành → không cho thanh toán/confirm (tránh tạo vé "mồ côi").
            // Tài xế có thể bấm "bắt đầu chuyến" SỚM hơn depart_at (startTrip không giới hạn
            // thời gian) nên chỉ check depart_at->isPast() là chưa đủ — phải check cả status
            // thực tế của chuyến, vì booking có thể được tạo TỪ TRƯỚC lúc chuyến còn Scheduled.
            if ($trip->status !== TripStatusEnum::Scheduled || $trip->depart_at->isPast()) {
                throw new \InvalidArgumentException('Chuyến đã khởi hành, không thể thanh toán vé này');
            }

            // Chuyến đang chờ sắp xếp lại tài xế → chặn KHỞI TẠO thanh toán MỚI (§6.3).
            // (Callback in-flight của giao dịch đã bắt đầu trước đó vẫn xử lý an toàn ở processCallback.)
            if ($trip->driver_unavailable_at !== null) {
                throw TripActionException::awaitingReassignment();
            }

            // Đồng bộ phương thức thật do khách chọn ở bước thanh toán
            // (booking được tạo với method tạm thời ở bước checkout)
            if ($booking->payment_method !== $method) {
                $booking->update(['payment_method' => $method]);
            }

            // Một booking chỉ có một giao dịch pending tại một thời điểm. Request retry
            // dùng lại order/request id để cổng thanh toán xử lý idempotent.
            $payment = Payment::where('booking_id', $booking->id)
                ->where('status', PaymentStatusEnum::Pending)
                ->latest()
                ->first();

            if ($payment && $payment->method !== $method) {
                throw new \InvalidArgumentException('Vé đang có một giao dịch thanh toán khác chờ xử lý');
            }

            $payment ??= Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'amount' => $booking->final_amount,
                'method' => $method,
                'status' => PaymentStatusEnum::Pending,
                'gateway_order_id' => 'XEGHEP-'.strtoupper((string) Str::ulid()),
            ]);

            return [$payment, $booking];
        });

        return match ($method) {
            PaymentMethodEnum::Momo => $this->initiateMomo($payment, $booking),
            PaymentMethodEnum::Vnpay => $this->initiateSepay($payment, $booking),
            PaymentMethodEnum::Wallet => $this->initiateWallet($payment, $booking),
            PaymentMethodEnum::Cash => $this->initiateCash($payment, $booking),
            default => throw new \InvalidArgumentException('Phương thức thanh toán không hỗ trợ'),
        };
    }

    /**
     * Xử lý callback từ MoMo — verify HMAC + idempotency
     */
    public function handleMomoCallback(array $payload): bool
    {
        $this->verifyMomoSignature($payload);

        $payment = Payment::where('gateway_order_id', $payload['orderId'])->first();
        if (! $payment
            || $payment->method !== PaymentMethodEnum::Momo
            || ! hash_equals((string) $payment->id, (string) $payload['requestId'])
            || (int) $payload['amount'] !== $payment->amount
            || ! hash_equals((string) config('services.momo.partner_code'), (string) $payload['partnerCode'])) {
            throw new PaymentVerificationException('Số tiền, đối tác hoặc request MoMo không khớp');
        }

        // MoMo: resultCode === 0 nghĩa là giao dịch THÀNH CÔNG. Giá trị khác = thất bại/hủy
        // → không được confirm vé (tránh xác nhận vé chưa thanh toán). Tương tự VNPay '00'.
        $success = (int) ($payload['resultCode'] ?? -1) === 0;

        return $this->processCallback(
            $payload['orderId'],
            $payload['transId'] ?? null,
            $payload,
            $success
        );
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
    public function refund(Booking $booking, int $amount, ?string $idempotencyKey = null): bool
    {
        return DB::transaction(function () use ($booking, $amount, $idempotencyKey) {
            // KHÓA hàng payment rồi mới kiểm trạng thái: ProcessRefundJob có tries=3,
            // queue retry hoặc 2 job đồng thời sẽ cùng đọc status=Success nếu không khóa
            // ⇒ credit ví HAI LẦN. lockForUpdate serialize, người sau thấy Refunded → thoát.
            $payment = Payment::where('booking_id', $booking->id)
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $payment || (! $payment->isSuccessful() && $payment->status !== PaymentStatusEnum::Refunded)) {
                return false;
            }

            $idempotencyKey ??= "refund:{$booking->id}";
            if (PaymentRefund::where('idempotency_key', $idempotencyKey)->exists()) {
                return false;
            }

            $alreadyRefunded = (int) $payment->refund_amount;
            $remaining = $payment->amount - $alreadyRefunded;
            if ($amount <= 0 || $amount > $remaining) {
                throw new \InvalidArgumentException('Số tiền hoàn vượt quá số tiền còn có thể hoàn');
            }

            // Tiền mặt do tài xế/nhà xe giữ (Phương án A) — nền tảng KHÔNG hoàn từ quỹ/ví;
            // nhà xe hoàn tiền mặt trực tiếp cho khách. Chỉ đánh dấu trạng thái để đối soát.
            // Vé online: nền tảng đang giữ tiền ⇒ hoàn về ví nội bộ.
            if ($payment->method !== PaymentMethodEnum::Cash) {
                $this->walletService->credit(
                    $booking->user,
                    $amount,
                    "Hoàn tiền vé {$booking->booking_code}",
                    $booking->id,
                    $idempotencyKey,
                );
            }

            $totalRefunded = $alreadyRefunded + $amount;
            $fullyRefunded = $totalRefunded === $payment->amount;

            $payment->update([
                'status' => $fullyRefunded ? PaymentStatusEnum::Refunded : PaymentStatusEnum::Success,
                'refund_amount' => $totalRefunded,
                'refunded_at' => $fullyRefunded ? now() : null,
            ]);

            $booking->update([
                'payment_status' => $fullyRefunded
                    ? BookingPaymentStatusEnum::Refunded
                    : BookingPaymentStatusEnum::PartialRefund,
            ]);

            PaymentRefund::create([
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
                'processed_at' => now(),
            ]);

            return true;
        });
    }

    private function processCallback(string $orderId, ?string $gatewayTxnId, array $payload, bool $success = true): bool
    {
        // Toàn bộ đọc + idempotency + cập nhật nằm TRONG transaction có khóa payment (và
        // booking): webhook trùng đến song song sẽ nối đuôi nhau, người thứ 2 thấy payment
        // đã Success → thoát idempotent, KHÔNG confirm/gửi event lần 2. Khóa payment trước,
        // booking sau (đồng bộ refund/collectCash).
        [$result, $bookingId] = DB::transaction(function () use ($orderId, $gatewayTxnId, $payload, $success) {
            $payment = Payment::where('gateway_order_id', $orderId)->lockForUpdate()->first();

            if (! $payment) {
                Log::warning('Payment callback: không tìm thấy payment', ['order_id' => $orderId]);

                return [false, null];
            }

            // Idempotency check SAU khi khóa — chống xử lý 2 lần (webhook trùng).
            if ($payment->status === PaymentStatusEnum::Success) {
                return [true, null];
            }

            if (! $success) {
                $payment->update(['status' => PaymentStatusEnum::Failed, 'gateway_response' => $payload]);

                return [false, null];
            }

            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            // Callback đến sau khi booking hết hạn/đã bị hủy không được hồi sinh vé và
            // chiếm lại ghế đã trả cho người khác. Giao dịch đến muộn cần đối soát riêng.
            if ($booking->booking_status !== BookingStatusEnum::Pending || $booking->isExpired()) {
                throw new PaymentVerificationException('Booking đã hết hạn hoặc không còn chờ thanh toán');
            }

            if ($gatewayTxnId !== null) {
                $duplicateGatewayTransaction = Payment::where('method', $payment->method)
                    ->where('gateway_txn_id', $gatewayTxnId)
                    ->whereKeyNot($payment->id)
                    ->exists();
                if ($duplicateGatewayTransaction) {
                    throw new PaymentVerificationException('Mã giao dịch cổng thanh toán đã được xử lý');
                }
            }

            $payment->update([
                'status' => PaymentStatusEnum::Success,
                'gateway_txn_id' => $gatewayTxnId,
                'gateway_response' => $payload,
                'paid_at' => now(),
            ]);

            $booking->update([
                'payment_status' => BookingPaymentStatusEnum::Paid,
                'booking_status' => BookingStatusEnum::Confirmed,
                'confirmed_at' => now(),
            ]);

            event(new PaymentProcessedEvent($booking, $payment));

            return [true, $booking->id];
        });

        // Callback in-flight đến SAU khi chuyến đã có cờ chờ-sắp-xếp-lại (khách bấm thanh toán
        // TRƯỚC khi cờ được set) → KHÔNG chặn, đã ghi Paid+Confirmed, GIỮ ghế. Chỉ báo riêng
        // cho khách này biết chuyến đang đổi tài xế vì họ confirm sau đợt gửi hàng loạt (§6.3).
        // Chạy SAU commit + chỉ khi vừa confirm trong lần này ($bookingId != null) → không
        // gửi lại khi webhook trùng.
        if ($bookingId !== null) {
            $this->notifyIfAwaitingReassignment(Booking::find($bookingId));
        }

        return $result;
    }

    /**
     * Nếu vé vừa confirm nằm trên chuyến đang chờ sắp xếp lại tài xế → gửi thông báo
     * TripDriverUnavailable cho chính khách này. Không tự hoàn, không giải phóng ghế.
     */
    private function notifyIfAwaitingReassignment(?Booking $booking): void
    {
        if (! $booking) {
            return;
        }

        $trip = Trip::with('route')->find($booking->trip_id);
        if (! $trip || ! $trip->isAwaitingReassignment()) {
            return;
        }

        $when = $trip->depart_at->format('H:i d/m');
        $route = "{$trip->route->origin_city} → {$trip->route->dest_city}";
        $type = NotificationTypeEnum::TripDriverUnavailable;

        // Dedupe DÙNG CHUNG khóa incident với SendTripDriverUnavailableNotificationListener:
        // nếu listener hàng loạt đã (hoặc sẽ) gửi cho chính khách này thì bản callback này
        // trùng khóa → bỏ qua ở tầng DB, khách chỉ nhận 1 thông báo cho mỗi incident.
        $incident = $trip->driverIncidents()->open()->latest('reported_at')->first();
        $dedupeKey = $incident
            ? "tdu:{$incident->id}:{$booking->user_id}:{$type->value}"
            : null;

        $this->notificationService->send(
            $booking->user,
            $type,
            [
                'title' => 'Chuyến đang sắp xếp lại tài xế',
                'body' => "Chuyến {$route} {$when} đang được sắp xếp lại tài xế. Vé của bạn vẫn được giữ, chúng tôi sẽ cập nhật sớm.",
                'booking_id' => $booking->id,
            ],
            [],
            $dedupeKey,
        );
    }

    private function initiateMomo(Payment $payment, Booking $booking): array
    {
        $createUrl = config('services.momo.create_url');
        $partnerCode = config('services.momo.partner_code');
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');
        // Trang kết quả thanh toán nằm ở frontend (Vercel), không phải API origin.
        $redirectUrl = config('services.momo.redirect_url');
        $ipnUrl = config('services.momo.notify_url');

        $rawHash = "accessKey={$accessKey}&amount={$payment->amount}&extraData=&ipnUrl={$ipnUrl}&orderId={$payment->gateway_order_id}&orderInfo=Vé xe {$booking->booking_code}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$payment->id}&requestType=captureWallet";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        try {
            // Timeout tường minh: initiate chạy trong request đồng bộ của khách —
            // MoMo chậm không được treo cả luồng đặt vé.
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->post($createUrl, [
                    'partnerCode' => $partnerCode,
                    'requestId' => $payment->id,
                    'amount' => $payment->amount,
                    'orderId' => $payment->gateway_order_id,
                    'orderInfo' => "Vé xe {$booking->booking_code}",
                    'redirectUrl' => $redirectUrl,
                    'ipnUrl' => $ipnUrl,
                    'lang' => 'vi',
                    'requestType' => 'captureWallet',
                    'extraData' => '',
                    'signature' => $signature,
                ]);

            $response->throw();
            if ((int) $response->json('resultCode', -1) !== 0 || ! is_string($response->json('payUrl'))) {
                throw new \RuntimeException('MoMo từ chối khởi tạo giao dịch');
            }

            return ['payment_url' => $response->json('payUrl'), 'order_id' => $payment->gateway_order_id];
        } catch (\Exception $e) {
            $payment->update([
                'status' => PaymentStatusEnum::Failed,
                'gateway_response' => ['initiate_error' => $e->getMessage()],
            ]);
            Log::error('MoMo initiate failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Không thể kết nối cổng thanh toán MoMo');
        }
    }

    private function initiateSepay(Payment $payment, Booking $booking): array
    {
        $bankAcc = config('services.sepay.bank_acc');
        $bankName = config('services.sepay.bank_name');
        $accName = config('services.sepay.acc_name');
        $description = $payment->gateway_order_id;

        $qrUrl = "https://qr.sepay.vn/img?acc={$bankAcc}&bank={$bankName}&amount={$payment->amount}&des={$description}&template=compact2";

        return [
            'payment_url' => $qrUrl,
            'order_id' => $payment->gateway_order_id,
            'bank_info' => [
                'bank_name' => $bankName,
                'bank_acc' => $bankAcc,
                'acc_name' => $accName,
                'amount' => $payment->amount,
                'code' => $description,
            ],
        ];
    }

    public function handleSepayWebhook(array $payload): bool
    {
        // SePay POST thực tế dùng field "content" (không phải "transactionContent")
        // — https://docs.sepay.vn tích hợp Webhooks. Field sai khiến webhook thật
        // từ gateway luôn rơi vào nhánh rỗng này, KHÔNG BAO GIỜ xác nhận được
        // thanh toán chuyển khoản tự động dù mọi logic xác thực còn lại đều đúng.
        $content = $payload['content'] ?? '';
        if (empty($content)) {
            Log::warning('SePay Webhook: content rỗng');

            return false;
        }

        if (! preg_match('/XEGHEP-[A-Z0-9]+/i', $content, $matches)) {
            Log::warning('SePay Webhook: không tìm thấy mã giao dịch XEGHEP trong nội dung', ['content' => $content]);

            return false;
        }

        $orderId = strtoupper($matches[0]);
        // Field thật của SePay là "transferAmount" (không phải "amountIn").
        $amountIn = (int) ($payload['transferAmount'] ?? 0);
        $gatewayTxnId = $payload['id'] ?? null;

        if (($payload['transferType'] ?? null) !== 'in') {
            throw new PaymentVerificationException('SePay webhook không phải giao dịch tiền vào');
        }

        $expectedAccount = preg_replace('/\s+/', '', (string) config('services.sepay.bank_acc'));
        $actualAccount = preg_replace('/\s+/', '', (string) ($payload['accountNumber'] ?? ''));
        if ($expectedAccount === '' || ! hash_equals($expectedAccount, $actualAccount)) {
            throw new PaymentVerificationException('Tài khoản thụ hưởng SePay không khớp');
        }

        Log::info('SePay Webhook matched transaction', [
            'order_id' => $orderId,
            'amount_in' => $amountIn,
            'gateway_txn_id' => $gatewayTxnId,
        ]);

        $payment = Payment::where('gateway_order_id', $orderId)->first();
        if (! $payment) {
            Log::warning('SePay Webhook: không tìm thấy giao dịch tương ứng trong DB', ['order_id' => $orderId]);

            return false;
        }

        if ($payment->method !== PaymentMethodEnum::Vnpay || $gatewayTxnId === null || $gatewayTxnId === '') {
            throw new PaymentVerificationException('Thông tin giao dịch SePay không hợp lệ');
        }

        if ($amountIn !== $payment->amount) {
            Log::warning('SePay Webhook: số tiền chuyển khoản không khớp', [
                'expected' => $payment->amount,
                'actual' => $amountIn,
                'order_id' => $orderId,
            ]);
            throw new PaymentVerificationException('Số tiền thanh toán không khớp');
        }

        return $this->processCallback(
            $orderId,
            $gatewayTxnId,
            $payload,
            true
        );
    }

    private function initiateWallet(Payment $payment, Booking $booking): array
    {
        // Trừ ví + xác nhận vé phải ATOMIC: nếu confirm lỗi thì rollback luôn lệnh trừ ví
        // (tránh trừ tiền nhưng vé không được confirm).
        DB::transaction(function () use ($payment, $booking) {
            $this->walletService->debit(
                $booking->user,
                $payment->amount,
                "Thanh toán vé {$booking->booking_code}",
                $booking->id,
                "wallet_payment:{$booking->id}",
            );

            $this->processCallback($payment->gateway_order_id, 'WALLET-'.time(), [], true);
        });

        return ['payment_url' => null, 'status' => 'paid'];
    }

    private function initiateCash(Payment $payment, Booking $booking): array
    {
        // Vé tiền mặt: xác nhận giữ chỗ NGAY, thu tiền khi lên xe.
        // expires_at = null ⇒ isExpired() trả false ⇒ ExpireUnpaidBookingJob KHÔNG hủy vé.
        // payment_status vẫn 'unpaid' (nghĩa: chờ tài xế thu tiền mặt).
        DB::transaction(function () use ($payment, $booking) {
            $payment->update(['status' => PaymentStatusEnum::Pending]); // chờ tài xế thu
            $booking->update([
                'booking_status' => BookingStatusEnum::Confirmed,
                'confirmed_at' => now(),
                'expires_at' => null,
            ]);
        });

        // Thông báo xác nhận đặt vé (tương tự luồng online)
        event(new BookingConfirmedEvent($booking->fresh()));

        return [
            'payment_url' => null,
            'status' => 'confirmed_unpaid',
            'message' => 'Đặt vé thành công. Vui lòng thanh toán tiền mặt khi lên xe.',
        ];
    }

    /**
     * Tài xế thu tiền mặt khi đón khách → đánh dấu đã thanh toán.
     *
     * @throws \InvalidArgumentException nếu vé không phải tiền mặt hoặc đã thanh toán
     */
    public function collectCash(Booking $booking, string $driverId): Payment
    {
        if ($booking->payment_method !== PaymentMethodEnum::Cash) {
            throw new \InvalidArgumentException('Vé này không phải thanh toán tiền mặt');
        }
        if ($booking->payment_status === BookingPaymentStatusEnum::Paid) {
            throw new \InvalidArgumentException('Vé này đã được thanh toán');
        }

        return DB::transaction(function () use ($booking, $driverId) {
            // Lấy payment cash đang chờ; nếu chưa có thì tạo mới (an toàn)
            $payment = Payment::where('booking_id', $booking->id)
                ->where('method', PaymentMethodEnum::Cash)
                ->latest()
                ->first()
                ?? Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'amount' => $booking->final_amount,
                    'method' => PaymentMethodEnum::Cash,
                    'status' => PaymentStatusEnum::Pending,
                    'gateway_order_id' => 'CASH-'.strtoupper(Str::random(10)),
                ]);

            $payment->update([
                'status' => PaymentStatusEnum::Success,
                'paid_at' => now(),
                'collected_by' => $driverId,
            ]);

            $booking->update(['payment_status' => BookingPaymentStatusEnum::Paid]);

            return $payment;
        });
    }

    private function verifyMomoSignature(array $payload): void
    {
        $secretKey = config('services.momo.secret_key');
        $accessKey = config('services.momo.access_key');

        $required = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId', 'signature'];
        foreach ($required as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new PaymentVerificationException("Callback MoMo thiếu trường {$key}");
            }
        }

        if (! is_string($secretKey) || $secretKey === '' || ! is_string($accessKey) || $accessKey === '') {
            throw new PaymentVerificationException('Cấu hình xác thực MoMo chưa đầy đủ');
        }

        $rawHash = "accessKey={$accessKey}&amount={$payload['amount']}&extraData={$payload['extraData']}&message={$payload['message']}&orderId={$payload['orderId']}&orderInfo={$payload['orderInfo']}&orderType={$payload['orderType']}&partnerCode={$payload['partnerCode']}&payType={$payload['payType']}&requestId={$payload['requestId']}&responseTime={$payload['responseTime']}&resultCode={$payload['resultCode']}&transId={$payload['transId']}";
        $expected = hash_hmac('sha256', $rawHash, $secretKey);

        if (! hash_equals($expected, (string) $payload['signature'])) {
            throw new PaymentVerificationException('Chữ ký MoMo không hợp lệ');
        }
    }

    private function verifyVnpaySignature(array $payload): void
    {
        $hashSecret = config('services.vnpay.hash_secret');
        $secureHash = $payload['vnp_SecureHash'] ?? '';
        $inputData = array_filter($payload, fn ($k) => ! in_array($k, ['vnp_SecureHash', 'vnp_SecureHashType']), ARRAY_FILTER_USE_KEY);
        ksort($inputData);
        $hashData = urldecode(http_build_query($inputData));
        $expected = hash_hmac('sha512', $hashData, $hashSecret);

        if ($expected !== $secureHash) {
            throw new PaymentVerificationException('Chữ ký VNPay không hợp lệ');
        }
    }
}
