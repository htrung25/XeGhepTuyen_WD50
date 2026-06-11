<?php

namespace App\Services;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\SeatNotAvailableException;
use App\Exceptions\TripNotAvailableException;
use App\Jobs\ExpireUnpaidBookingJob;
use App\Jobs\GenerateQrCodeJob;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingService
{
    /** Mức bồi thường (đồng) khi nhà xe hủy/không thực hiện chuyến */
    private const COMPENSATION_AMOUNT = 20000;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly VoucherService $voucherService,
        private readonly WalletService $walletService,
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
        // Kiểm tra user không có quá 3 booking pending
        $pendingCount = $this->bookingRepo->countPendingByUser($user->id);
        if ($pendingCount >= 3) {
            throw new \InvalidArgumentException('Bạn đang có quá nhiều vé chờ thanh toán (tối đa 3 vé)');
        }

        return DB::transaction(function () use ($data, $user) {
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
                $lockedByMe = $seat->status === \App\Enums\SeatStatus::Locked
                    && $seat->locked_by === $user->id;
                if (!$seat->isAvailable() && !$lockedByMe) {
                    throw new SeatNotAvailableException("Ghế {$seat->seat_code} đã được đặt bởi người khác");
                }
            }

            // 3. Kiểm tra số ghế khớp số hành khách
            if ($seats->count() !== $data['passenger_count']) {
                throw new \InvalidArgumentException('Số ghế không khớp với số hành khách');
            }

            // 4. Kiểm tra chuyến còn hợp lệ
            $trip = Trip::lockForUpdate()->findOrFail($data['trip_id']);
            if (!$trip->canBeBooked()) {
                throw new TripNotAvailableException('Chuyến đi không còn nhận đặt vé');
            }

            // 5. Tính giá
            $subtotal = $seats->sum('price');
            $discount = 0;
            $voucher  = null;

            if (!empty($data['voucher_code'])) {
                $voucher  = $this->voucherService->validate($data['voucher_code'], $subtotal, $user, $trip->id);
                $discount = $voucher->calculateDiscount($subtotal);
            }

            $finalAmount = $subtotal - $discount;

            // 6. Tạo booking
            $booking = Booking::create([
                'booking_code'    => $this->generateCode($trip),
                'user_id'         => $user->id,
                'trip_id'         => $data['trip_id'],
                'pickup_stop_id'  => $data['pickup_stop_id'],
                'dropoff_stop_id' => $data['dropoff_stop_id'],
                'pickup_address'  => $data['pickup_address'] ?? null,
                'dropoff_address' => $data['dropoff_address'] ?? null,
                'passenger_count' => $data['passenger_count'],
                'contact_name'    => $data['contact_name'],
                'contact_phone'   => $data['contact_phone'],
                'note'            => $data['note'] ?? null,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'final_amount'    => $finalAmount,
                'payment_method'  => $data['payment_method'],
                'payment_status'  => BookingPaymentStatus::Unpaid,
                'booking_status'  => BookingStatus::Pending,
                'voucher_id'      => $voucher?->id,
                'qr_token'        => Str::random(32),
                'expires_at'      => now()->addMinutes(15),
            ]);

            // 7. Tạo booking_passengers
            foreach ($data['passengers'] as $i => $passenger) {
                BookingPassenger::create([
                    'booking_id'  => $booking->id,
                    'seat_map_id' => $seats[$i]->id,
                    'full_name'   => $passenger['full_name'],
                    'phone'       => $passenger['phone'] ?? null,
                    'is_primary'  => $i === 0,
                ]);
            }

            // 8. Cập nhật ghế → booked
            SeatMap::whereIn('id', $data['seat_ids'])->update([
                'status'     => SeatStatus::Booked,
                'locked_by'  => null,
                'locked_at'  => null,
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
        if (!$booking->canCancel()) {
            throw new \InvalidArgumentException('Vé này không thể hủy');
        }

        return DB::transaction(function () use ($booking, $user, $reason) {
            $refundPercent = $booking->refundPercent();
            $refundAmount  = $booking->refundAmount();

            // Cập nhật trạng thái booking
            $booking->update([
                'booking_status' => BookingStatus::Cancelled,
                'cancelled_at'   => now(),
                'cancel_reason'  => $reason,
            ]);

            // Giải phóng ghế
            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatus::Available]);

            // Tăng lại available_seats
            Trip::where('id', $booking->trip_id)
                ->increment('available_seats', $booking->passenger_count);

            // Dispatch hoàn tiền nếu đã thanh toán
            if ($booking->payment_status === BookingPaymentStatus::Paid && $refundAmount > 0) {
                \App\Jobs\ProcessRefundJob::dispatch($booking, $refundAmount)->onQueue('high');
            }

            return [
                'refund_percent' => $refundPercent,
                'refund_amount'  => $refundAmount,
            ];
        });
    }

    /**
     * Hủy booking hết hạn thanh toán (gọi từ ExpireUnpaidBookingJob)
     */
    public function expire(Booking $booking): void
    {
        if (!$booking->isExpired()) {
            return;
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'booking_status' => BookingStatus::Cancelled,
                'cancelled_at'   => now(),
                'cancel_reason'  => 'Hết hạn thanh toán',
            ]);

            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatus::Available]);
            Trip::where('id', $booking->trip_id)
                ->increment('available_seats', $booking->passenger_count);
        });
    }

    /**
     * Tất toán vé khi chuyến HOÀN TẤT:
     *  - checked_in → completed (khách đã đi)
     *  - confirmed (đã xác nhận nhưng không lên xe) → no_show (khách lỡ chuyến, KHÔNG hoàn tiền)
     *  - pending (chưa thanh toán, bỏ dở) → cancelled
     */
    public function finalizeOnTripComplete(Trip $trip): void
    {
        DB::transaction(function () use ($trip) {
            $trip->bookings()
                 ->where('booking_status', BookingStatus::CheckedIn->value)
                 ->update(['booking_status' => BookingStatus::Completed, 'completed_at' => now()]);

            $trip->bookings()
                 ->where('booking_status', BookingStatus::Confirmed->value)
                 ->update(['booking_status' => BookingStatus::NoShow]);

            $trip->bookings()
                 ->where('booking_status', BookingStatus::Pending->value)
                 ->update([
                     'booking_status' => BookingStatus::Cancelled,
                     'cancelled_at'   => now(),
                     'cancel_reason'  => 'Chuyến đã kết thúc, vé chưa thanh toán',
                 ]);
        });
    }

    /**
     * Hủy vé do NHÀ XE (hủy chuyến / không thực hiện chuyến):
     *  - hoàn 100% nếu đã thanh toán + bồi thường vào ví + giải phóng ghế.
     *
     * @param bool $compensate có cộng tiền bồi thường vào ví hay không
     */
    public function cancelByOperator(Booking $booking, string $reason, bool $compensate = true): void
    {
        if (in_array($booking->booking_status, [BookingStatus::Cancelled, BookingStatus::Completed, BookingStatus::NoShow], true)) {
            return; // đã chốt trạng thái, bỏ qua
        }

        $wasPaid = $booking->payment_status === BookingPaymentStatus::Paid;

        DB::transaction(function () use ($booking, $reason, $compensate, $wasPaid) {
            $booking->update([
                'booking_status' => BookingStatus::Cancelled,
                'cancelled_at'   => now(),
                'cancel_reason'  => $reason,
            ]);

            // Giải phóng ghế + trả lại available_seats
            $seatIds = $booking->passengers()->pluck('seat_map_id');
            SeatMap::whereIn('id', $seatIds)->update(['status' => SeatStatus::Available]);
            Trip::where('id', $booking->trip_id)
                ->increment('available_seats', $booking->passenger_count);

            // Chỉ vé ĐÃ THANH TOÁN mới hoàn tiền + bồi thường.
            // Vé chưa trả (pending / tiền mặt chưa thu) → chỉ hủy, không có gì để hoàn/bồi thường.
            if ($wasPaid) {
                \App\Jobs\ProcessRefundJob::dispatch($booking, (int) $booking->final_amount)->onQueue('high');

                if ($compensate) {
                    $this->walletService->credit(
                        $booking->user,
                        self::COMPENSATION_AMOUNT,
                        "Bồi thường hủy chuyến — vé {$booking->booking_code}",
                        $booking->id,
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
            $seats = SeatMap::whereIn('id', $seatIds)
                            ->where('trip_id', $tripId)
                            ->lockForUpdate()
                            ->get();

            foreach ($seats as $seat) {
                if (!$seat->isAvailable()) {
                    throw new SeatNotAvailableException("Ghế {$seat->seat_code} không còn trống");
                }
            }

            SeatMap::whereIn('id', $seatIds)->update([
                'status'    => SeatStatus::Locked,
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
        $route    = $trip->route;
        $origin   = $this->cityCode($route->origin_city);
        $dest     = $this->cityCode($route->dest_city);
        $date     = now()->format('ymd');
        $prefix   = "{$origin}{$dest}{$date}";

        $last = Booking::where('booking_code', 'like', "{$prefix}%")
                       ->orderByDesc('booking_code')
                       ->value('booking_code');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    private function cityCode(string $city): string
    {
        return match(true) {
            str_contains($city, 'Hà Nội')   => 'HN',
            str_contains($city, 'Hải Phòng') => 'HP',
            default                          => strtoupper(substr($city, 0, 2)),
        };
    }
}
