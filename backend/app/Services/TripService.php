<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\DriverStatusEnum;
use App\Enums\TripStatusEnum;
use App\Events\TripCompletedEvent;
use App\Events\TripDriverReassignedEvent;
use App\Events\TripDriverUnavailableEvent;
use App\Events\TripStartedEvent;
use App\Exceptions\TripActionException;
use App\Exceptions\TripNotAvailableException;
use App\Models\Driver;
use App\Models\Route;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\TripDriverIncident;
use App\Models\Vehicle;
use App\Repositories\Contracts\TripRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripService
{
    /** Bộ đếm "thế hệ" cache tìm chuyến — tăng khi TẬP chuyến đổi (tạo/hủy) để cache cũ tự hết hiệu lực. */
    private const SEARCH_GEN_KEY = 'trips:search:gen';

    public function __construct(
        private readonly TripRepositoryInterface $tripRepo,
        private readonly BookingService $bookingService,
    ) {}

    public function search(array $filters): array
    {
        // NOTE: không cache Eloquent model (cache store = database sẽ serialize model
        // và unserialize lỗi "incomplete object"). Cache danh sách ID thay thế.
        // Gắn "thế hệ" vào key: khi tập chuyến đổi (flushSearchCache) → key mới, cache cũ bỏ qua.
        $gen = Cache::get(self::SEARCH_GEN_KEY, 0);
        $cacheKey = 'trips:search:'.$gen.':'.md5(serialize($filters));

        $ids = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($filters) {
            return $this->tripRepo->search($filters)->pluck('id')->all();
        });

        if (empty($ids)) {
            return [];
        }

        return $this->tripRepo->findManyForSearch($ids);
    }

    /** Vô hiệu hoá cache tìm chuyến (gọi khi tạo/hủy chuyến — tập kết quả thay đổi). */
    private function flushSearchCache(): void
    {
        Cache::increment(self::SEARCH_GEN_KEY);
    }

    public function create(array $data): Trip
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $driver = Driver::findOrFail($data['driver_id']);

        // Xe & tài xế phải thuộc đúng nhà xe tạo chuyến
        if (! empty($data['operator_id'])) {
            if ($vehicle->operator_id !== $data['operator_id']) {
                throw new \InvalidArgumentException('Xe không thuộc nhà xe của bạn');
            }
            if ($driver->operator_id !== $data['operator_id']) {
                throw new \InvalidArgumentException('Tài xế không thuộc nhà xe của bạn');
            }
        }

        // Chỉ tài xế đã được admin duyệt GPLX (verified) mới được xếp vào chuyến.
        if ($driver->status !== DriverStatusEnum::Verified) {
            throw new \InvalidArgumentException('Tài xế chưa được admin duyệt, không thể xếp vào chuyến');
        }

        $route = Route::findOrFail($data['route_id']);
        if (! empty($data['operator_id']) && $route->operator_id !== $data['operator_id']) {
            throw new \InvalidArgumentException('Tuyến đường không thuộc nhà xe của bạn');
        }

        if (empty($data['arrive_at'])) {
            $data['arrive_at'] = Carbon::parse($data['depart_at'])
                ->addMinutes($route->est_duration_min);
        }

        $departAt = Carbon::parse($data['depart_at']);
        $arriveAt = Carbon::parse($data['arrive_at']);

        // Giờ xuất phát phải cách hiện tại tối thiểu min_lead_minutes — đồng bộ với
        // luật đặt vé của khách (Trip::scopeAvailable/canBeBooked), tránh tạo chuyến
        // không bao giờ bán được.
        $minLead = (int) config('booking.min_lead_minutes', 30);
        if ($departAt->lt(now()->addMinutes($minLead))) {
            throw new \InvalidArgumentException("Giờ xuất phát phải cách hiện tại ít nhất {$minLead} phút");
        }

        // Chặn trùng lịch: xe HOẶC tài xế đã có chuyến (chưa hủy) chồng khung giờ
        $overlap = Trip::whereIn('status', [TripStatusEnum::Scheduled, TripStatusEnum::Boarding, TripStatusEnum::InProgress])
            ->where(fn ($q) => $q->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $driver->id))
            ->where('depart_at', '<', $arriveAt)
            ->where('arrive_at', '>', $departAt)
            ->exists();
        if ($overlap) {
            throw new \InvalidArgumentException('Xe hoặc tài xế đã có chuyến trùng khung giờ này');
        }

        // Kiểm tra ràng buộc vị trí địa lý & thời gian nghỉ (Buffer Time 30 phút)
        $bufferMinutes = 30;

        // 1. Kiểm tra tài xế (Chuyến liền trước & liền sau)
        $prevDriverTrip = Trip::with('route')
            ->whereNotIn('status', [TripStatusEnum::Cancelled])
            ->where('driver_id', $driver->id)
            ->where('depart_at', '<', $departAt)
            ->orderBy('depart_at', 'desc')
            ->first();

        if ($prevDriverTrip) {
            $prevRoute = $prevDriverTrip->route;
            if ($prevRoute->dest_city !== $route->origin_city) {
                throw new \InvalidArgumentException("Tài xế đang ở {$prevRoute->dest_city} sau chuyến trước, không thể xuất phát từ {$route->origin_city}");
            }
            if ($departAt->lt($prevDriverTrip->arrive_at->copy()->addMinutes($bufferMinutes))) {
                throw new \InvalidArgumentException("Thời gian nghỉ của tài xế giữa 2 chuyến phải tối thiểu {$bufferMinutes} phút");
            }
        }

        $nextDriverTrip = Trip::with('route')
            ->whereNotIn('status', [TripStatusEnum::Cancelled])
            ->where('driver_id', $driver->id)
            ->where('depart_at', '>', $departAt)
            ->orderBy('depart_at', 'asc')
            ->first();

        if ($nextDriverTrip) {
            $nextRoute = $nextDriverTrip->route;
            if ($route->dest_city !== $nextRoute->origin_city) {
                throw new \InvalidArgumentException("Chuyến tiếp theo của tài xế bắt đầu từ {$nextRoute->origin_city}, nhưng chuyến này kết thúc tại {$route->dest_city}");
            }
            if ($nextDriverTrip->depart_at->lt($arriveAt->copy()->addMinutes($bufferMinutes))) {
                throw new \InvalidArgumentException("Thời gian nghỉ của tài xế trước chuyến tiếp theo phải tối thiểu {$bufferMinutes} phút");
            }
        }

        // 2. Kiểm tra xe (Chuyến liền trước & liền sau)
        $prevVehicleTrip = Trip::with('route')
            ->whereNotIn('status', [TripStatusEnum::Cancelled])
            ->where('vehicle_id', $vehicle->id)
            ->where('depart_at', '<', $departAt)
            ->orderBy('depart_at', 'desc')
            ->first();

        if ($prevVehicleTrip) {
            $prevRoute = $prevVehicleTrip->route;
            if ($prevRoute->dest_city !== $route->origin_city) {
                throw new \InvalidArgumentException("Xe đang ở {$prevRoute->dest_city} sau chuyến trước, không thể xuất phát từ {$route->origin_city}");
            }
            if ($departAt->lt($prevVehicleTrip->arrive_at->copy()->addMinutes($bufferMinutes))) {
                throw new \InvalidArgumentException("Thời gian xoay vòng của xe giữa 2 chuyến phải tối thiểu {$bufferMinutes} phút");
            }
        }

        $nextVehicleTrip = Trip::with('route')
            ->whereNotIn('status', [TripStatusEnum::Cancelled])
            ->where('vehicle_id', $vehicle->id)
            ->where('depart_at', '>', $departAt)
            ->orderBy('depart_at', 'asc')
            ->first();

        if ($nextVehicleTrip) {
            $nextRoute = $nextVehicleTrip->route;
            if ($route->dest_city !== $nextRoute->origin_city) {
                throw new \InvalidArgumentException("Chuyến tiếp theo của xe bắt đầu từ {$nextRoute->origin_city}, nhưng chuyến này kết thúc tại {$route->dest_city}");
            }
            if ($nextVehicleTrip->depart_at->lt($arriveAt->copy()->addMinutes($bufferMinutes))) {
                throw new \InvalidArgumentException("Thời gian xoay vòng của xe trước chuyến tiếp theo phải tối thiểu {$bufferMinutes} phút");
            }
        }

        $trip = Trip::create(array_merge($data, [
            'available_seats' => $vehicle->seat_count,
            'tracking_code' => strtoupper(Str::random(8)),
            'status' => $data['status'] ?? TripStatusEnum::Scheduled,
        ]));

        $this->generateSeatMap($trip, $vehicle);
        $this->flushSearchCache(); // chuyến mới xuất hiện trong kết quả tìm ngay (không chờ TTL 2')

        return $trip;
    }

    /**
     * Tạo hàng loạt chuyến (tạo lịch cả tuần).
     * FE đã bung sẵn từng chuyến: [{route_id, vehicle_id, driver_id, depart_at, base_price}, ...].
     * Best-effort: bỏ qua chuyến trùng giờ / không hợp lệ, tạo các chuyến còn lại.
     *
     * @return array{created:int, skipped:int}
     */
    public function bulkCreate(array $trips, string $operatorId): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($trips as $t) {
            try {
                $this->create([
                    'route_id' => $t['route_id'],
                    'vehicle_id' => $t['vehicle_id'],
                    'driver_id' => $t['driver_id'],
                    'depart_at' => $t['depart_at'],
                    'price' => $t['base_price'] ?? $t['price'] ?? null,
                    'operator_id' => $operatorId,
                ]);
                $created++;
            } catch (\InvalidArgumentException|TripNotAvailableException $e) {
                // Chuyến trùng khung giờ / xe-tài xế không thuộc nhà xe → bỏ qua, tạo tiếp
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Tài xế báo "không chạy được chuyến X" (§6.1). Đặt cờ chờ-sắp-xếp-lại + mở incident.
     * Mọi kiểm tra nằm TRONG transaction sau khi khóa trip (chống race — §6 nguyên tắc chung).
     * Idempotent: báo lần 2 không tạo incident/event mới (R4).
     *
     * @throws TripActionException 404 (không phải chuyến của mình) | 422 (R2 trạng thái, R3 cutoff)
     */
    public function reportDriverUnavailable(string $tripId, string $driverId, string $reason): void
    {
        $cutoff = (int) config('booking.driver_report_cutoff_minutes', 15);

        $event = DB::transaction(function () use ($tripId, $driverId, $reason, $cutoff) {
            $trip = Trip::whereKey($tripId)->lockForUpdate()->firstOrFail();

            // R1 — ownership (404, không tiết lộ chuyến người khác).
            if ($trip->driver_id !== $driverId) {
                throw TripActionException::notFound();
            }

            // R4 — đã báo trước đó → idempotent, không tạo incident/event lần 2.
            if ($trip->isAwaitingReassignment()) {
                return null;
            }

            // R2 — chỉ chuyến chưa chạy mới báo được.
            if (! in_array($trip->status, [TripStatusEnum::Scheduled, TripStatusEnum::Boarding], true)) {
                throw TripActionException::notReportable();
            }

            // R3 — phải còn > cutoff phút trước giờ chạy.
            if ($trip->depart_at->lte(now()->addMinutes($cutoff))) {
                throw TripActionException::reportCutoffPassed($cutoff);
            }

            $trip->update([
                'driver_unavailable_at' => now(),
                'driver_unavailable_reason' => $reason,
            ]);

            $incident = TripDriverIncident::create([
                'trip_id' => $trip->id,
                'reported_driver_id' => $driverId,
                'reason' => $reason,
                'reported_at' => now(),
                'status' => TripDriverIncident::STATUS_OPEN,
            ]);

            return new TripDriverUnavailableEvent($trip->id, $reason, $driverId, $incident->id);
        });

        if ($event === null) {
            return; // idempotent — đã báo rồi
        }

        // Chuyến biến mất khỏi kết quả tìm ngay (không chờ TTL cache).
        $this->flushSearchCache();

        // Dispatch SAU commit — tránh gửi thông báo khi transaction rollback.
        event($event);
    }

    /**
     * Nhà xe đổi tài xế cho chuyến (§6.2). Không chịu cutoff 15' nhưng bắt buộc depart_at > now.
     * Khóa trip + khóa record tài xế mới trong transaction, kiểm A1..A7 sau khi khóa.
     *
     * @throws TripActionException 404 (không thuộc nhà xe) | 422 (A2..A7)
     */
    public function reassignDriver(string $tripId, string $operatorId, string $newDriverId, string $actorUserId): Trip
    {
        // attempts: 3 — mạng lưới an toàn cho deadlock InnoDB (SQLSTATE 40001): khi 2 reassign
        // song song tranh khóa, MySQL có thể hủy 1 bên; Laravel chạy lại nguyên transaction.
        // Lần chạy lại thấy tài xế đã bị gán chuyến chồng giờ → ném DRIVER_SCHEDULE_CONFLICT
        // sạch sẽ (422) thay vì lỗi deadlock 500. Thứ tự khóa tài-xế-TRƯỚC-trip cũng đã giảm
        // hẳn khả năng deadlock (bên chờ chưa giữ khóa trip nào).
        [$trip, $event] = DB::transaction(function () use ($tripId, $operatorId, $newDriverId, $actorUserId) {
            // Khóa record tài xế mới TRƯỚC — điểm serialize mọi reassign tới cùng tài xế.
            // Khóa tài xế trước khi khóa trip: bên đang chờ tài xế CHƯA giữ khóa trip nào nên
            // không tạo được vòng chờ chéo (trip↔driver) → tránh deadlock có hệ thống.
            $newDriver = Driver::whereKey($newDriverId)->with('user')->lockForUpdate()->first();

            $trip = Trip::whereKey($tripId)->lockForUpdate()->firstOrFail();

            // A1 — trip thuộc nhà xe (404, không lộ chuyến khác).
            if ($trip->vehicle->operator_id !== $operatorId) {
                throw TripActionException::notFound();
            }

            // A2 — chỉ đổi khi chưa chạy.
            if (! in_array($trip->status, [TripStatusEnum::Scheduled, TripStatusEnum::Boarding], true)) {
                throw TripActionException::notReassignable();
            }

            // A3 — không đổi sau giờ khởi hành (chặn kẽ hở 2h grace của auto-resolve).
            if ($trip->depart_at->lte(now())) {
                throw TripActionException::alreadyDeparted();
            }

            // A4 — tồn tại + đúng nhà xe + chưa xóa mềm (SoftDeletes loại trừ mặc định)
            // + tài khoản user còn hoạt động (is_active). Tài xế bị vô hiệu hóa không được
            // xếp vào chuyến (không đăng nhập được → không nhận/không chạy chuyến).
            if (! $newDriver
                || $newDriver->operator_id !== $operatorId
                || ! $newDriver->user
                || ! $newDriver->user->is_active) {
                throw TripActionException::driverNotInOperator();
            }

            // A5 — đã verified + GPLX chưa hết hạn.
            if ($newDriver->status !== DriverStatusEnum::Verified
                || $newDriver->license_expiry->lt(today())) {
                throw TripActionException::driverNotEligible();
            }

            // A6 — khác tài xế hiện tại.
            $oldDriverId = $trip->driver_id;
            if ($newDriverId === $oldDriverId) {
                throw TripActionException::driverUnchanged();
            }

            // A7 — tài xế mới không trùng khung giờ với chuyến khác (chưa hủy).
            // lockForUpdate BẮT BUỘC: dưới MySQL REPEATABLE READ, read thường dùng snapshot
            // tạo lúc câu SELECT đầu txn (trước khi tài xế bị khóa) → có thể BỎ SÓT chuyến
            // mà một reassign song song vừa gán cho cùng tài xế này. Locking read luôn đọc
            // bản mới nhất đã commit → thấy xung đột, chặn double-book. (Đã serialize qua
            // khóa record tài xế phía trên; locking read đóng nốt kẽ hở snapshot.)
            $conflict = Trip::where('driver_id', $newDriverId)
                ->whereKeyNot($trip->id)
                ->whereIn('status', [TripStatusEnum::Scheduled, TripStatusEnum::Boarding, TripStatusEnum::InProgress])
                ->where('depart_at', '<', $trip->arrive_at)
                ->where('arrive_at', '>', $trip->depart_at)
                ->lockForUpdate()
                ->exists();
            if ($conflict) {
                throw TripActionException::driverScheduleConflict();
            }

            // Đổi tài xế + xóa cờ chờ-sắp-xếp-lại.
            $trip->update([
                'driver_id' => $newDriverId,
                'driver_unavailable_at' => null,
                'driver_unavailable_reason' => null,
            ]);

            // Đóng incident đang mở (nếu có) → resolved(reassigned).
            $trip->driverIncidents()->open()->update([
                'status' => TripDriverIncident::STATUS_RESOLVED,
                'resolution' => TripDriverIncident::RESOLUTION_REASSIGNED,
                'replacement_driver_id' => $newDriverId,
                'resolved_by_user_id' => $actorUserId,
                'resolved_at' => now(),
            ]);

            return [$trip, new TripDriverReassignedEvent($trip->id, $oldDriverId, $newDriverId)];
        }, attempts: 3);

        // Chuyến có thể xuất hiện lại trong tìm kiếm (cờ đã gỡ) → làm mới cache.
        $this->flushSearchCache();

        event($event);

        return $trip->fresh();
    }

    /**
     * Tài xế bắt đầu chuyến. Ownership + guard cờ kiểm TRONG transaction sau khi khóa (§6.4).
     *
     * @throws TripActionException 404 (không phải chuyến của mình) | 422 (trạng thái / đang chờ sắp xếp lại)
     */
    public function startTrip(string $tripId, string $driverId): void
    {
        $trip = DB::transaction(function () use ($tripId, $driverId) {
            $trip = Trip::whereKey($tripId)->lockForUpdate()->firstOrFail();

            // Ownership trong txn: chặn tài xế cũ start thay tài xế mới sau khi đã reassign.
            if ($trip->driver_id !== $driverId) {
                throw TripActionException::notFound();
            }

            if (! in_array($trip->status, [TripStatusEnum::Scheduled, TripStatusEnum::Boarding], true)) {
                throw TripActionException::notStartable();
            }

            // Chặn start khi đang chờ sắp xếp lại tài xế.
            if ($trip->isAwaitingReassignment()) {
                throw TripActionException::awaitingReassignment();
            }

            $trip->update([
                'status' => TripStatusEnum::InProgress,
                'started_at' => now(),
            ]);

            return $trip;
        });

        event(new TripStartedEvent($trip));
    }

    /**
     * Tài xế/hệ thống hoàn tất chuyến. Khi có $driverId → kiểm ownership trong txn (§6.4);
     * $driverId = null cho auto-resolve/system (bỏ qua ownership).
     *
     * @throws TripActionException 404 (không phải chuyến của mình) | 422 (chưa bắt đầu)
     */
    public function completeTrip(string $tripId, ?string $driverId = null): void
    {
        $trip = DB::transaction(function () use ($tripId, $driverId) {
            $trip = Trip::whereKey($tripId)->lockForUpdate()->firstOrFail();

            if ($driverId !== null && $trip->driver_id !== $driverId) {
                throw TripActionException::notFound();
            }

            if ($trip->status !== TripStatusEnum::InProgress) {
                throw TripActionException::notCompletable();
            }

            $trip->update([
                'status' => TripStatusEnum::Completed,
                'completed_at' => now(),
            ]);

            // Tất toán vé: checked_in+confirmed→completed, pending→cancelled
            $this->bookingService->finalizeOnTripComplete($trip);

            return $trip;
        });

        event(new TripCompletedEvent($trip));
    }

    /**
     * Nhà xe xác nhận chuyến QUÁ GIỜ thực tế ĐÃ CHẠY XONG (tài xế quên cập nhật app).
     *
     * Khác completeTrip(): vé 'confirmed' (chưa quét QR check-in) được coi là ĐÃ ĐI
     * → completed (ghi nhận doanh thu), KHÔNG đánh no_show / KHÔNG hoàn tiền oan.
     * Vé 'pending' (chưa thanh toán) → hủy, giải phóng ghế.
     */
    public function markRanCompleted(Trip $trip): void
    {
        if (in_array($trip->status, [TripStatusEnum::Completed, TripStatusEnum::Cancelled], true)) {
            return;
        }

        $trip = DB::transaction(function () use ($trip) {
            // Khóa + đọc lại bản mới nhất: model truyền vào có thể đã cũ (chuyến vừa bị
            // hủy/hoàn tất ở luồng khác). Guard lại TRONG txn để idempotent, tránh
            // ghi đè trạng thái cuối bằng dữ liệu stale.
            $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            if (in_array($trip->status, [TripStatusEnum::Completed, TripStatusEnum::Cancelled], true)) {
                return null;
            }

            $trip->update([
                'status' => TripStatusEnum::Completed,
                'started_at' => $trip->started_at ?? $trip->depart_at,
                'completed_at' => now(),
            ]);

            // Nếu chuyến còn treo cờ chờ-sắp-xếp-lại (hiếm): gỡ cờ + đóng incident với
            // resolution 'completed' (chuyến ĐÃ CHẠY — không phải cancelled), tránh dữ liệu
            // mâu thuẫn (chuyến completed nhưng incident ghi resolution=cancelled).
            $this->clearReassignmentFlag($trip, null, TripDriverIncident::RESOLUTION_COMPLETED);

            // confirmed + checked_in → completed (đã đi, ghi nhận doanh thu)
            $trip->bookings()
                ->whereIn('booking_status', [BookingStatusEnum::Confirmed->value, BookingStatusEnum::CheckedIn->value])
                ->update(['booking_status' => BookingStatusEnum::Completed, 'completed_at' => now()]);

            // pending (chưa thanh toán) → hủy
            $trip->bookings()
                ->where('booking_status', BookingStatusEnum::Pending->value)
                ->update([
                    'booking_status' => BookingStatusEnum::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => 'Chuyến đã kết thúc, vé chưa thanh toán',
                ]);

            return $trip;
        });

        if ($trip === null) {
            return; // đã chốt trạng thái ở luồng khác — idempotent, không phát event trùng
        }

        event(new TripCompletedEvent($trip));
    }

    /**
     * Hủy chuyến (do nhà xe/admin hoặc tự động khi quá giờ không thực hiện).
     * Hoàn 100% + bồi thường cho mọi vé còn hiệu lực + gỡ cờ + đóng incident (§6.5).
     *
     * Actor nullable: operator truyền $operatorId (kiểm ownership trong txn); admin/auto-resolve
     * truyền null (bỏ qua ownership). $actorUserId ghi vào incident.resolved_by_user_id.
     *
     * @throws TripActionException 404 khi $operatorId có nhưng chuyến không thuộc nhà xe đó
     */
    public function cancelTrip(string $tripId, ?string $operatorId, ?string $actorUserId, string $reason, bool $compensate = true): void
    {
        DB::transaction(function () use ($tripId, $operatorId, $actorUserId, $reason, $compensate) {
            $trip = Trip::whereKey($tripId)->lockForUpdate()->firstOrFail();

            // Ownership (chỉ khi operator gọi) — 404, không lộ chuyến nhà xe khác.
            if ($operatorId !== null && $trip->vehicle->operator_id !== $operatorId) {
                throw TripActionException::notFound();
            }

            if (in_array($trip->status, [TripStatusEnum::Completed, TripStatusEnum::Cancelled], true)) {
                return; // đã chốt trạng thái — idempotent
            }

            $trip->update([
                'status' => TripStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            // Gỡ cờ + đóng incident đang mở → resolved(cancelled), tránh chuyến cancelled
            // mà vẫn is_awaiting_reassignment (§6.5).
            $this->clearReassignmentFlag($trip, $actorUserId);

            $bookings = $trip->bookings()
                ->whereIn('booking_status', [
                    BookingStatusEnum::Pending->value,
                    BookingStatusEnum::Confirmed->value,
                    BookingStatusEnum::CheckedIn->value,
                ])
                ->with('user')
                ->get();

            foreach ($bookings as $booking) {
                $this->bookingService->cancelByOperator($booking, $reason, $compensate);
            }
        });

        $this->flushSearchCache(); // chuyến bị hủy biến mất khỏi kết quả tìm ngay
    }

    /**
     * Gỡ cờ chờ-sắp-xếp-lại + đóng incident đang mở thành resolved.
     * Gọi trong transaction hủy/hoàn tất chuyến. No-op nếu chuyến không có cờ.
     * $resolution: cancelled (hủy chuyến) | completed (chuyến vẫn chạy xong).
     */
    private function clearReassignmentFlag(Trip $trip, ?string $actorUserId, string $resolution = TripDriverIncident::RESOLUTION_CANCELLED): void
    {
        if (! $trip->isAwaitingReassignment()) {
            return;
        }

        $trip->update([
            'driver_unavailable_at' => null,
            'driver_unavailable_reason' => null,
        ]);

        $trip->driverIncidents()->open()->update([
            'status' => TripDriverIncident::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolved_by_user_id' => $actorUserId,
            'resolved_at' => now(),
        ]);
    }

    private function generateSeatMap(Trip $trip, Vehicle $vehicle): void
    {
        $templates = self::getSeatTemplate($vehicle);

        foreach ($templates as $seatCode) {
            SeatMap::create([
                'trip_id' => $trip->id,
                'seat_code' => $seatCode,
                'seat_type' => 'standard',
                'price' => $trip->price,
                'status' => 'available',
            ]);
        }
    }

    /**
     * Sơ đồ ghế theo MẶT CẮT NGANG THẬT của từng loại xe (mỗi chữ cái = đúng
     * một hàng ghế vật lý, theo thứ tự từ đầu xe xuống đuôi xe):
     *  - sedan_4:   đầu xe (tài xế + A1) → hàng ghế sau 3 chỗ (B1-B3)
     *  - mpv_7:     đầu xe (tài xế + A1) → 2 hàng ghế sau, mỗi hàng 3 chỗ
     *  - van_9:     đầu xe (tài xế + A1,A2) → 2 hàng ghế đơn có lối đi giữa
     *               (B1|B2, C1|C2) → hàng ghế cuối 3 chỗ (D1-D3) — đúng bố
     *               trí limousine 9 chỗ thực tế
     *  - minibus_16: đầu xe (tài xế + A1) → 4 hàng ghế đơn+đôi có lối đi giữa
     *               (B-E, mỗi hàng 3 chỗ) → hàng ghế cuối 3 chỗ (F1-F3)
     * Dùng chung cho cả TripService (sinh ghế lúc tạo chuyến) lẫn seeder demo,
     * tránh lệch bố trí giữa 2 nơi như trước đây (frontend suy hàng theo tiền
     * tố chữ cái nên một chữ cái PHẢI ứng với đúng một hàng ghế vật lý).
     */
    public static function getSeatTemplate(Vehicle $vehicle): array
    {
        return match ($vehicle->vehicle_type->value) {
            'sedan_4' => ['A1', 'B1', 'B2', 'B3'],
            'mpv_7' => ['A1', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3'],
            'van_9' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2', 'D3'],
            'minibus_16' => array_merge(
                ['A1'],
                ...array_map(fn ($r) => ["{$r}1", "{$r}2", "{$r}3"], ['B', 'C', 'D', 'E', 'F'])
            ),
            default => ['A1', 'B1', 'B2', 'B3'],
        };
    }
}
