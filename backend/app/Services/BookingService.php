<?php

namespace App\Services;

use App\DTOs\GeoCoordinate;
use App\Enums\BookingPaymentStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SeatStatusEnum;
use App\Exceptions\SeatNotAvailableException;
use App\Exceptions\TripNotAvailableException;
use App\Jobs\ExpireUnpaidBookingJob;
use App\Jobs\GenerateQrCodeJob;
use App\Jobs\ProcessRefundJob;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Route;
use App\Models\SeatMap;
use App\Models\ServiceArea;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    /** Mức bồi thường (đồng) khi nhà xe hủy/không thực hiện chuyến */
    private const COMPENSATION_AMOUNT = 20000;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly VoucherService $voucherService,
        private readonly WalletService $walletService,
        private readonly ServiceAreaService $serviceAreaService,
        private readonly GeometryFactory $geometryFactory,
    ) {}

    /**
     * Tạo booking mới — có DB transaction + lockForUpdate chống race condition
     *
     * @throws SeatNotAvailableException
     * @throws TripNotAvailableException
     * @throws \InvalidArgumentException
     */
    public function create(array $data, User $user): Booking
    {
        // Chuẩn hóa tọa độ ngay tại điểm dữ liệu đi vào: GeoCoordinate validate
        // biên độ + chống đảo trục khi khởi tạo, mọi tầng dưới chỉ nhận VO này.
        $pickup = GeoCoordinate::fromLatLng((float) $data['pickup_lat'], (float) $data['pickup_lng']);
        $dropoff = GeoCoordinate::fromLatLng((float) $data['dropoff_lat'], (float) $data['dropoff_lng']);

        return DB::transaction(function () use ($data, $user, $pickup, $dropoff) {
            // Khóa trip + route + hai vùng để cấu hình geofencing không thể thay đổi
            // giữa lúc validate và lúc booking commit.
            $trip = Trip::lockForUpdate()->findOrFail($data['trip_id']);
            if (! $trip->canBeBooked()) {
                throw new TripNotAvailableException('Chuyến đi không còn nhận đặt vé');
            }

            $route = Route::lockForUpdate()->findOrFail($trip->route_id);
            $serviceAreas = ServiceArea::query()
                ->whereIn('id', array_filter([
                    $route->pickup_service_area_id,
                    $route->dropoff_service_area_id,
                ]))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $route->setRelation('pickupServiceArea', $serviceAreas->get($route->pickup_service_area_id));
            $route->setRelation('dropoffServiceArea', $serviceAreas->get($route->dropoff_service_area_id));
            $this->serviceAreaService->validateBookingLocations($route, $pickup, $dropoff);

            // Khóa theo user để đếm vé pending chính xác dưới đồng thời (tránh lách quá 3 vé)
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if ($this->bookingRepo->countPendingByUser($user->id) >= 3) {
                throw new \InvalidArgumentException('Bạn đang có quá nhiều vé chờ thanh toán (tối đa 3 vé)');
            }

            // 1. Lock ghế để tránh race condition
            $seats = SeatMap::whereIn('id', $data['seat_ids'])
                ->where('trip_id', $data['trip_id'])
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($data['seat_ids'])) {
                throw new SeatNotAvailableException('Một số ghế không tồn tại trên chuyến này');
            }

            // 2. Kiểm tra từng ghế còn available hoặc đang locked bởi chính user này
            foreach ($seats as $seat) {
                $lockedByMe = $seat->status === SeatStatusEnum::Locked
                    && $seat->locked_by === $user->id;
                if (! $seat->isAvailable() && ! $lockedByMe) {
                    throw new SeatNotAvailableException("Ghế {$seat->seat_code} đã được đặt bởi người khác");
                }
            }

            // 3. Kiểm tra số ghế khớp số hành khách
            if ($seats->count() !== $data['passenger_count']) {
                throw new \InvalidArgumentException('Số ghế không khớp với số hành khách');
            }

            // 4. Tính giá
            $subtotal = $seats->sum('price');
            $discount = 0;
            $voucher = null;

            if (! empty($data['voucher_code'])) {
                $voucher = $this->voucherService->reserve($data['voucher_code'], $subtotal, $user, $trip);
                $discount = $voucher->calculateDiscount($subtotal);
            }

            $finalAmount = $subtotal - $discount;

            // 6. Tạo booking
            $booking = Booking::create([
                'booking_code' => $this->generateCode($trip),
                'user_id' => $user->id,
                'trip_id' => $data['trip_id'],
                'pickup_stop_id' => $data['pickup_stop_id'] ?? null,
                'dropoff_stop_id' => $data['dropoff_stop_id'] ?? null,
                'pickup_address' => $data['pickup_address'],
                'dropoff_address' => $data['dropoff_address'],
                // Tọa độ ghi qua GeometryFactory: MySQL ghi cột POINT (lat/lng là
                // generated column tự suy ra), SQLite (test) ghi cặp lat/lng vật lý.
                ...$this->geometryFactory->coordinateAttributes('pickup', $pickup),
                ...$this->geometryFactory->coordinateAttributes('dropoff', $dropoff),
                'passenger_count' => $data['passenger_count'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_method' => $data['payment_method'],
                'payment_status' => BookingPaymentStatusEnum::Unpaid,
                'booking_status' => BookingStatusEnum::Pending,
                'voucher_id' => $voucher?->id,
                'qr_token' => Str::random(32),
                'expires_at' => now()->addMinutes(15),
            ]);

            // 7. Tạo booking_passengers
            foreach ($data['passengers'] as $i => $passenger) {
                BookingPassenger::create([
                    'booking_id' => $booking->id,
                    'seat_map_id' => $seats[$i]->id,
                    'full_name' => $passenger['full_name'],
                    'phone' => $passenger['phone'] ?? null,
                    'is_primary' => $i === 0,
                ]);
            }

            // 8. Cập nhật ghế → booked
            SeatMap::whereIn('id', $data['seat_ids'])->update([
                'status' => SeatStatusEnum::Booked,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // 9. Giảm available_seats trên trip
            Trip::where('id', $data['trip_id'])
                ->decrement('available_seats', $data['passenger_count']);

            // 10. Đánh dấu voucher đã dùng
            if ($voucher) {
                $this->voucherService->markUsed($voucher, $booking, $user, $discount);
            }

            // 11. Dispatch jobs bất đồng bộ (afterCommit: chỉ chạy sau khi booking đã commit)
            GenerateQrCodeJob::dispatch($booking)->onQueue('default')->afterCommit();
            ExpireUnpaidBookingJob::dispatch($booking)
                ->delay(now()->addMinutes(15))
                ->onQueue('high')
                ->afterCommit();

            return $booking;
        }, attempts: 3);
    }

    /**
     * Hủy booking theo chính sách hoàn tiền
     */
    public function cancel(Booking $booking, User $user, string $reason = ''): array
    {
        return DB::transaction(function () use ($booking, $user, $reason) {
            $trip = Trip::whereKey($booking->trip_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->setRelation('trip', $trip);

            if ($booking->user_id !== $user->id) {
                throw new \InvalidArgumentException('Bạn không có quyền hủy vé này');
            }
            if (! $booking->canCancel()) {
                throw new \InvalidArgumentException('Vé này không thể hủy');
            }

            $refundPercent = $booking->refundPercent();
            $policyRefundAmount = $booking->refundAmount();
            $alreadyRefunded = (int) ($booking->payment()->value('refund_amount') ?? 0);
            $refundAmount = max(0, $policyRefundAmount - $alreadyRefunded);
            $wasRefundable = in_array($booking->payment_status, [
                BookingPaymentStatusEnum::Paid,
                BookingPaymentStatusEnum::PartialRefund,
            ], true);

            // Cập nhật trạng thái booking
            $booking->update([
                'booking_status' => BookingStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            // Giải phóng ghế
            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatusEnum::Available]);

            // Tăng lại available_seats
            $trip->increment('available_seats', $booking->passenger_count);

            $this->voucherService->releaseUnpaid($booking);

            // Dispatch hoàn tiền nếu đã thanh toán
            if ($wasRefundable && $refundAmount > 0) {
                // afterCommit BẮT BUỘC: queue redis đặt after_commit=false, nếu dispatch
                // ngay trong transaction thì worker có thể hoàn tiền TRƯỚC khi commit —
                // transaction rollback là tiền đã ra mà vé vẫn chưa hủy.
                ProcessRefundJob::dispatch($booking, $refundAmount)->onQueue('high')->afterCommit();
            }

            return [
                'refund_percent' => $refundPercent,
                'refund_amount' => $refundAmount,
            ];
        });
    }

    /**
     * Hủy booking hết hạn thanh toán (gọi từ ExpireUnpaidBookingJob)
     */
    public function expire(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            // KHÓA hàng booking rồi mới đọc trạng thái — cùng hàng mà processCallback khóa,
            // nên hai luồng nối đuôi nhau thay vì đua:
            //  · callback commit trước → ở đây thấy Paid/Confirmed → thoát, KHÔNG hủy vé đã trả tiền.
            //  · hai job expire đồng thời → người sau thấy Cancelled → thoát, không increment 2 lần.
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            // Guard PHẢI gồm booking_status: isExpired() chỉ xét expires_at + payment_status,
            // mà các luồng hủy khác (khách tự hủy, cancelByOperator, finalizeOnTripComplete,
            // markRanCompleted) set 'cancelled' nhưng KHÔNG đổi payment_status/expires_at.
            // Thiếu guard này, job expire chạy lại trên vé đã hủy sẽ increment
            // available_seats lần hai → chuyến thừa ghế ảo (oversell).
            if (! $booking || $booking->booking_status !== BookingStatusEnum::Pending || ! $booking->isExpired()) {
                return;
            }

            $booking->update([
                'booking_status' => BookingStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => 'Hết hạn thanh toán',
            ]);

            $booking->payment()
                ->where('status', PaymentStatusEnum::Pending)
                ->update(['status' => PaymentStatusEnum::Failed]);

            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatusEnum::Available]);
            Trip::where('id', $booking->trip_id)
                ->increment('available_seats', $booking->passenger_count);

            $this->voucherService->releaseUnpaid($booking);
        });
    }

    /**
     * Tất toán vé khi chuyến HOÀN TẤT (Hướng 2 — thống nhất với markRanCompleted):
     *  - checked_in + confirmed → completed (ghi nhận doanh thu kể cả khi tài xế quên
     *    quét QR). no_show CHỈ đặt khi tài xế CHỦ ĐỘNG đánh vắng (check-in/absent).
     *  - pending (chưa thanh toán, bỏ dở) → cancelled
     */
    public function finalizeOnTripComplete(Trip $trip): void
    {
        DB::transaction(function () use ($trip) {
            $trip->bookings()
                ->whereIn('booking_status', [BookingStatusEnum::CheckedIn->value, BookingStatusEnum::Confirmed->value])
                ->update(['booking_status' => BookingStatusEnum::Completed, 'completed_at' => now()]);

            $pendingBookings = $trip->bookings()
                ->where('booking_status', BookingStatusEnum::Pending->value)
                ->lockForUpdate()
                ->get();

            foreach ($pendingBookings as $booking) {
                $booking->update([
                    'booking_status' => BookingStatusEnum::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => 'Chuyến đã kết thúc, vé chưa thanh toán',
                ]);
                $this->voucherService->releaseUnpaid($booking);
            }
        });
    }

    /**
     * Hủy vé do NHÀ XE (hủy chuyến / không thực hiện chuyến):
     *  - hoàn 100% nếu đã thanh toán + bồi thường vào ví + giải phóng ghế.
     *
     * @param  bool  $compensate  có cộng tiền bồi thường vào ví hay không
     */
    public function cancelByOperator(Booking $booking, string $reason, bool $compensate = true): void
    {
        DB::transaction(function () use ($booking, $reason, $compensate) {
            $trip = Trip::whereKey($booking->trip_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (in_array($booking->booking_status, [BookingStatusEnum::Cancelled, BookingStatusEnum::Completed, BookingStatusEnum::NoShow], true)) {
                return; // đã chốt trạng thái, bỏ qua
            }

            $wasPaid = in_array($booking->payment_status, [
                BookingPaymentStatusEnum::Paid,
                BookingPaymentStatusEnum::PartialRefund,
            ], true);
            $alreadyRefunded = (int) ($booking->payment()->value('refund_amount') ?? 0);
            $refundAmount = max(0, (int) $booking->final_amount - $alreadyRefunded);

            $booking->update([
                'booking_status' => BookingStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            // Giải phóng ghế + trả lại available_seats
            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatusEnum::Available]);
            $trip->increment('available_seats', $booking->passenger_count);

            $this->voucherService->releaseUnpaid($booking);

            // Chỉ vé ĐÃ THANH TOÁN mới hoàn tiền + bồi thường.
            // Vé chưa trả (pending / tiền mặt chưa thu) → chỉ hủy, không có gì để hoàn/bồi thường.
            if ($wasPaid && $refundAmount > 0) {
                // afterCommit BẮT BUỘC — xem giải thích ở cancel()
                ProcessRefundJob::dispatch($booking, $refundAmount)->onQueue('high')->afterCommit();

                if ($compensate) {
                    $this->walletService->credit(
                        $booking->user,
                        self::COMPENSATION_AMOUNT,
                        "Bồi thường hủy chuyến — vé {$booking->booking_code}",
                        $booking->id,
                        "compensation:{$booking->id}",
                    );
                }
            }
        });
    }

    /**
     * Khóa ghế tạm 10 phút trong Redis
     */
    public function lockSeats(array $seatIds, string $userId, string $tripId): void
    {
        DB::transaction(function () use ($seatIds, $userId, $tripId) {
            // Giải phóng toàn bộ ghế cũ đang được giữ bởi user này (tránh treo ghế rác và tự chặn chính mình)
            SeatMap::where('locked_by', $userId)
                ->where('status', SeatStatusEnum::Locked)
                ->update([
                    'status' => SeatStatusEnum::Available,
                    'locked_at' => null,
                    'locked_by' => null,
                ]);

            $seats = SeatMap::whereIn('id', $seatIds)
                ->where('trip_id', $tripId)
                ->lockForUpdate()
                ->get();

            foreach ($seats as $seat) {
                if (! $seat->isAvailable()) {
                    throw new SeatNotAvailableException("Ghế {$seat->seat_code} không còn trống");
                }
            }

            SeatMap::whereIn('id', $seatIds)->update([
                'status' => SeatStatusEnum::Locked,
                'locked_at' => now(),
                'locked_by' => $userId,
            ]);

            foreach ($seatIds as $seatId) {
                Cache::put("seat_lock:{$seatId}", $userId, 600);
            }
        });
    }

    /**
     * Sinh booking code: HNHP240615001
     */
    private function generateCode(Trip $trip): string
    {
        $route = $trip->route;
        $origin = $this->cityCode($route->origin_city);
        $dest = $this->cityCode($route->dest_city);
        $date = now()->format('ymd');
        $prefix = "{$origin}{$dest}{$date}";

        // Lấy vé mới nhất cùng prefix theo THỜI GIAN (không so chuỗi) rồi tăng số đuôi —
        // tránh vỡ khi seq vượt 999 (so chuỗi sẽ xếp "1000" < "999").
        $last = Booking::where('booking_code', 'like', "{$prefix}%")
            ->latest()
            ->value('booking_code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function cityCode(string $city): string
    {
        return match (true) {
            str_contains($city, 'Hà Nội') => 'HN',
            str_contains($city, 'Hải Phòng') => 'HP',
            default => strtoupper(substr($city, 0, 2)),
        };
    }
}
