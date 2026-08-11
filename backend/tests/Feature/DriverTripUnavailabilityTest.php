<?php

use App\Enums\DriverStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Events\PaymentProcessedEvent;
use App\Exceptions\TripActionException;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\TripDriverIncident;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\TripService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Chức năng: tài xế báo không chạy được chuyến → nhà xe đổi tài xế (giữ chuyến, không hoàn tiền).
 * Spec: docs/superpowers/specs/2026-07-19-driver-trip-unavailability-design.md
 */

/** Dựng ngữ cảnh: nhà xe + tuyến khứ hồi HN↔HP + xe + 2 tài xế đã verified. */
function unavailCtx(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Test', 'business_license' => 'GP-'.Str::random(6), 'status' => 'verified',
    ]);

    $routeHnHp = Route::create([
        'operator_id' => $operator->id, 'name' => 'HN→HP', 'origin_city' => 'Hà Nội', 'dest_city' => 'Hải Phòng',
        'distance_km' => 105, 'est_duration_min' => 120, 'base_price' => 120000, 'is_active' => true,
    ]);
    $stop = RouteStop::create(['route_id' => $routeHnHp->id, 'stop_name' => 'Nước Ngầm', 'address' => 'HN', 'lat' => 21, 'lng' => 105.8, 'stop_order' => 1]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '29A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $mkDriver = fn () => Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'D',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'),
        'status' => DriverStatusEnum::Verified,
    ]);

    return [
        'operator' => $operator, 'opUser' => $opUser, 'route' => $routeHnHp, 'stop' => $stop,
        'vehicle' => $vehicle, 'driver' => $mkDriver(), 'driver2' => $mkDriver(),
    ];
}

/** Tạo trip trực tiếp (bỏ qua validate của TripService::create) để kiểm soát depart_at/status. */
function mkTrip(array $c, array $overrides = []): Trip
{
    return Trip::create(array_merge([
        'route_id' => $c['route']->id, 'vehicle_id' => $c['vehicle']->id, 'driver_id' => $c['driver']->id,
        'depart_at' => now()->addHours(3), 'arrive_at' => now()->addHours(5),
        'available_seats' => 9, 'price' => 120000, 'status' => TripStatusEnum::Scheduled,
    ], $overrides));
}

/** Vé confirmed+paid (hành khách đã cam kết chỗ) trên chuyến. */
function mkConfirmedBooking(Trip $trip, array $c, string $status = 'confirmed'): Booking
{
    return Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $c['stop']->id, 'dropoff_stop_id' => $c['stop']->id,
        'passenger_count' => 1, 'contact_name' => 'Khach', 'contact_phone' => '0900000000',
        'subtotal' => 120000, 'final_amount' => 120000, 'payment_method' => 'momo',
        'payment_status' => 'paid', 'booking_status' => $status, 'qr_token' => Str::random(32),
    ]);
}

// ─────────────────────────── Driver report ────────────────────────────────

it('tài xế báo nghỉ: set cờ + tạo incident open + gửi thông báo', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $pax = mkConfirmedBooking($trip, $c);

    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'Xe hỏng dọc đường');

    $trip->refresh();
    expect($trip->driver_unavailable_at)->not->toBeNull();
    expect($trip->driver_unavailable_reason)->toBe('Xe hỏng dọc đường');
    expect($trip->isAwaitingReassignment())->toBeTrue();

    $incident = TripDriverIncident::where('trip_id', $trip->id)->first();
    expect($incident->status)->toBe('open');
    expect($incident->reported_driver_id)->toBe($c['driver']->id);

    // Nhà xe + hành khách nhận thông báo TripDriverUnavailable (in-app)
    expect(Notification::where('user_id', $c['opUser']->id)->where('type', NotificationTypeEnum::TripDriverUnavailable->value)->exists())->toBeTrue();
    expect(Notification::where('user_id', $pax->user_id)->where('type', NotificationTypeEnum::TripDriverUnavailable->value)->exists())->toBeTrue();
});

it('operator xem thông báo in-app và đánh dấu đã đọc', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'Xe hỏng');
    Sanctum::actingAs($c['opUser'], ['*'], 'sanctum');
    Sanctum::actingAs($c['opUser'], ['*'], 'operator');

    $response = $this->getJson('/api/operator/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.unread_count', 1)
        ->assertJsonPath('data.0.data.link', "/operator/trips?trip={$trip->id}");

    $this->putJson('/api/operator/notifications/'.$response->json('data.0.id').'/read')->assertOk();
    $this->getJson('/api/operator/notifications')->assertJsonPath('meta.unread_count', 0);
});

it('operator pending-count phản ánh chuyến thực tế đang chờ phân lại tài xế', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'Bị ốm');
    Sanctum::actingAs($c['opUser'], ['*'], 'sanctum');
    Sanctum::actingAs($c['opUser'], ['*'], 'operator');

    $this->getJson('/api/operator/pending-counts')
        ->assertOk()
        ->assertJsonPath('data./operator/trips', 1);

    app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id);

    $this->getJson('/api/operator/pending-counts')
        ->assertOk()
        ->assertJsonPath('data./operator/trips', 0);
});

it('notification tài xế nghỉ chứa deep link tới đúng chuyến operator', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'Xe hỏng');

    $notification = Notification::query()
        ->forUser($c['opUser']->id)
        ->inApp()
        ->where('type', NotificationTypeEnum::TripDriverUnavailable)
        ->firstOrFail();

    expect($notification->data['kind'])->toBe('trip_driver_unavailable')
        ->and($notification->data['trip_id'])->toBe($trip->id)
        ->and($notification->data['link'])->toBe("/operator/trips?trip={$trip->id}");
});

it('báo nghỉ chuyến in_progress → TRIP_NOT_REPORTABLE (422)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c, ['status' => TripStatusEnum::InProgress]);

    expect(fn () => app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'lý do'))
        ->toThrow(function (TripActionException $e) {
            expect($e->errorCode)->toBe('TRIP_NOT_REPORTABLE');
            expect($e->status)->toBe(422);
        });
});

it('báo nghỉ chuyến không phải của mình → 404 (không lộ chuyến người khác)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);

    expect(fn () => app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver2']->id, 'lý do'))
        ->toThrow(function (TripActionException $e) {
            expect($e->status)->toBe(404);
        });
});

it('báo nghỉ 2 lần: idempotent, chỉ 1 incident', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $svc = app(TripService::class);

    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'lần 1');
    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'lần 2');

    expect(TripDriverIncident::where('trip_id', $trip->id)->count())->toBe(1);
    expect($trip->fresh()->driver_unavailable_reason)->toBe('lần 1'); // không ghi đè
});

it('cutoff biên: depart = now+15 bị chặn, +15m1s được báo, +14m59s bị chặn', function () {
    Carbon::setTestNow('2026-08-01 08:00:00');
    $c = unavailCtx();
    $svc = app(TripService::class);

    // Đúng biên 15' → chặn (cần > 15')
    $t1 = mkTrip($c, ['depart_at' => now()->addMinutes(15), 'arrive_at' => now()->addMinutes(135)]);
    expect(fn () => $svc->reportDriverUnavailable($t1->id, $c['driver']->id, 'x'))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('REPORT_CUTOFF_PASSED'));

    // 14'59" → chặn
    $t2 = mkTrip($c, ['depart_at' => now()->addMinutes(14)->addSeconds(59), 'arrive_at' => now()->addHours(3)]);
    expect(fn () => $svc->reportDriverUnavailable($t2->id, $c['driver']->id, 'x'))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('REPORT_CUTOFF_PASSED'));

    // 15'01" → được báo
    $t3 = mkTrip($c, ['depart_at' => now()->addMinutes(15)->addSeconds(1), 'arrive_at' => now()->addHours(3)]);
    $svc->reportDriverUnavailable($t3->id, $c['driver']->id, 'ok');
    expect($t3->fresh()->isAwaitingReassignment())->toBeTrue();

    Carbon::setTestNow();
});

// ─────────────────────────── Operator reassign ─────────────────────────────

it('nhà xe đổi tài xế: driver đổi + gỡ cờ + incident resolved(reassigned) + thông báo', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $pax = mkConfirmedBooking($trip, $c);
    $svc = app(TripService::class);

    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'ốm');
    $svc->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id);

    $trip->refresh();
    expect($trip->driver_id)->toBe($c['driver2']->id);
    expect($trip->driver_unavailable_at)->toBeNull();
    expect($trip->isAwaitingReassignment())->toBeFalse();

    $incident = TripDriverIncident::where('trip_id', $trip->id)->first();
    expect($incident->status)->toBe('resolved');
    expect($incident->resolution)->toBe('reassigned');
    expect($incident->replacement_driver_id)->toBe($c['driver2']->id);
    expect($incident->resolved_by_user_id)->toBe($c['opUser']->id);

    // Hành khách + tài xế mới + tài xế cũ nhận thông báo TripDriverReassigned
    $type = NotificationTypeEnum::TripDriverReassigned->value;
    expect(Notification::where('user_id', $pax->user_id)->where('type', $type)->exists())->toBeTrue();
    expect(Notification::where('user_id', $c['driver2']->user_id)->where('type', $type)->exists())->toBeTrue();
    expect(Notification::where('user_id', $c['driver']->user_id)->where('type', $type)->exists())->toBeTrue();
});

it('reassign bởi nhà xe khác → 404', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, 'other-op-id', $c['driver2']->id, 'u'))
        ->toThrow(fn (TripActionException $e) => expect($e->status)->toBe(404));
});

it('reassign sau giờ khởi hành → TRIP_ALREADY_DEPARTED', function () {
    $c = unavailCtx();
    $trip = mkTrip($c, ['depart_at' => now()->subMinutes(5), 'arrive_at' => now()->addHour()]);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('TRIP_ALREADY_DEPARTED'));
});

it('reassign về tài xế chưa verified / GPLX hết hạn → DRIVER_NOT_ELIGIBLE', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $c['driver2']->update(['license_expiry' => now()->subDay()]);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('DRIVER_NOT_ELIGIBLE'));
});

it('reassign về tài xế nhà xe khác → DRIVER_NOT_IN_OPERATOR', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $otherOp = Operator::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Operator])->id,
        'company_name' => 'NX Khác', 'business_license' => 'GP-X', 'status' => 'verified',
    ]);
    $foreignDriver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $otherOp->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'D',
        'license_expiry' => now()->addYears(2), 'id_card_number' => fake()->numerify('############'), 'status' => DriverStatusEnum::Verified,
    ]);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $foreignDriver->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('DRIVER_NOT_IN_OPERATOR'));
});

it('reassign về chính tài xế hiện tại → DRIVER_UNCHANGED', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver']->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('DRIVER_UNCHANGED'));
});

it('reassign khi tài xế mới trùng khung giờ chuyến khác → DRIVER_SCHEDULE_CONFLICT', function () {
    $c = unavailCtx();
    $trip = mkTrip($c, ['depart_at' => now()->addHours(3), 'arrive_at' => now()->addHours(5)]);
    // driver2 đã có chuyến chồng khung giờ
    mkTrip($c, ['driver_id' => $c['driver2']->id, 'depart_at' => now()->addHours(4), 'arrive_at' => now()->addHours(6)]);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('DRIVER_SCHEDULE_CONFLICT'));
});

it('reassign chuyến CHƯA có cờ vẫn được (đổi chủ động), không tạo incident', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);

    app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id);

    expect($trip->fresh()->driver_id)->toBe($c['driver2']->id);
    expect(TripDriverIncident::where('trip_id', $trip->id)->count())->toBe(0);
});

// ─────────────────────────── startTrip guard ──────────────────────────────

it('start chuyến đang chờ sắp xếp lại → TRIP_AWAITING_REASSIGNMENT', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    expect(fn () => app(TripService::class)->startTrip($trip->id, $c['driver']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('TRIP_AWAITING_REASSIGNMENT'));
});

it('start bởi tài xế không phải chủ chuyến → 404 (ownership trong txn)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);

    expect(fn () => app(TripService::class)->startTrip($trip->id, $c['driver2']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->status)->toBe(404));
});

it('sau reassign, tài xế CŨ start → 404; tài xế MỚI start → OK', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $svc = app(TripService::class);
    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');
    $svc->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id);

    expect(fn () => $svc->startTrip($trip->id, $c['driver']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->status)->toBe(404));

    $svc->startTrip($trip->id, $c['driver2']->id);
    expect($trip->fresh()->status)->toBe(TripStatusEnum::InProgress);
});

// ─────────────────────────── Booking / payment guard ───────────────────────

it('chuyến có cờ bị loại khỏi scopeAvailable + canBeBooked false', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    expect(Trip::available()->whereKey($trip->id)->exists())->toBeFalse();
    expect($trip->fresh()->canBeBooked())->toBeFalse();
});

it('PaymentService::initiate từ chối khởi tạo thanh toán mới khi có cờ', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $c['stop']->id, 'dropoff_stop_id' => $c['stop']->id,
        'passenger_count' => 1, 'contact_name' => 'K', 'contact_phone' => '0900000000',
        'subtotal' => 120000, 'final_amount' => 120000, 'payment_method' => 'momo',
        'payment_status' => 'unpaid', 'booking_status' => 'pending', 'qr_token' => Str::random(32),
        'expires_at' => now()->addMinutes(15),
    ]);

    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    expect(fn () => app(PaymentService::class)->initiate($booking->fresh(), PaymentMethodEnum::Momo))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('TRIP_AWAITING_REASSIGNMENT'));
});

// ─────────────────────────── cancel + incident #2 ──────────────────────────

it('cancelTrip gỡ cờ + đóng incident(cancelled)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $svc = app(TripService::class);
    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    $svc->cancelTrip($trip->id, $c['operator']->id, $c['opUser']->id, 'Không có tài xế thay', true);

    $trip->refresh();
    expect($trip->status)->toBe(TripStatusEnum::Cancelled);
    expect($trip->driver_unavailable_at)->toBeNull();

    $incident = TripDriverIncident::where('trip_id', $trip->id)->first();
    expect($incident->status)->toBe('resolved');
    expect($incident->resolution)->toBe('cancelled');
});

it('incident thứ 2 trên cùng trip vẫn gửi notification (không bị dedupe nuốt)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $pax = mkConfirmedBooking($trip, $c);
    $svc = app(TripService::class);

    // Incident 1: driver báo → reassign sang driver2
    $svc->reportDriverUnavailable($trip->id, $c['driver']->id, 'lần 1');
    $svc->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id);

    // Incident 2: driver2 (nay là chủ chuyến) lại báo
    $svc->reportDriverUnavailable($trip->id, $c['driver2']->id, 'lần 2');

    expect(TripDriverIncident::where('trip_id', $trip->id)->count())->toBe(2);

    // Khách nhận >= 2 thông báo "unavailable" (incident 1 + incident 2), không bị dedupe nhầm.
    $unavailCount = Notification::where('user_id', $pax->user_id)
        ->where('type', NotificationTypeEnum::TripDriverUnavailable->value)
        ->where('channel', 'in_app')
        ->count();
    expect($unavailCount)->toBe(2);
});

it('không gửi thông báo cho vé pending khi báo nghỉ', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $pending = mkConfirmedBooking($trip, $c, 'pending');

    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    expect(Notification::where('user_id', $pending->user_id)->exists())->toBeFalse();
});

// ─────────────────────────── HTTP contracts ───────────────────────────────

it('POST /api/driver/trips/{id}/report-unavailable trả 200', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $headers = ['Authorization' => 'Bearer '.$c['driver']->user->createToken('t')->plainTextToken];

    $this->postJson("/api/driver/trips/{$trip->id}/report-unavailable", ['reason' => 'Kẹt xe cấp cứu'], $headers)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($trip->fresh()->isAwaitingReassignment())->toBeTrue();
});

it('POST report-unavailable thiếu reason → 422', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    $headers = ['Authorization' => 'Bearer '.$c['driver']->user->createToken('t')->plainTextToken];

    $this->postJson("/api/driver/trips/{$trip->id}/report-unavailable", [], $headers)->assertStatus(422);
});

it('POST /api/operator/trips/{id}/reassign-driver trả 200 + đổi tài xế', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');
    $headers = ['Authorization' => 'Bearer '.$c['opUser']->createToken('t')->plainTextToken];

    $this->postJson("/api/operator/trips/{$trip->id}/reassign-driver", ['driver_id' => $c['driver2']->id], $headers)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($trip->fresh()->driver_id)->toBe($c['driver2']->id);
});

// ─────────────────────────── A4: tài xế bị vô hiệu hóa ─────────────────────

it('reassign về tài xế có user is_active=false → DRIVER_NOT_IN_OPERATOR', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    // Tài xế 2 hợp lệ nhưng tài khoản user đã bị vô hiệu hóa.
    $c['driver2']->user->update(['is_active' => false]);

    expect(fn () => app(TripService::class)->reassignDriver($trip->id, $c['operator']->id, $c['driver2']->id, $c['opUser']->id))
        ->toThrow(fn (TripActionException $e) => expect($e->errorCode)->toBe('DRIVER_NOT_IN_OPERATOR'));

    expect($trip->fresh()->driver_id)->toBe($c['driver']->id); // không đổi
});

// ─────────────────────────── markRanCompleted + incident ───────────────────

it('markRanCompleted khi còn cờ: hoàn tất + incident resolved(completed), KHÔNG cancelled', function () {
    $c = unavailCtx();
    $trip = mkTrip($c, ['status' => TripStatusEnum::InProgress]);
    $svc = app(TripService::class);

    // Chuyến in_progress vẫn treo cờ báo nghỉ (edge case). Đặt cờ + incident open thủ công
    // vì reportDriverUnavailable chỉ nhận scheduled/boarding.
    $trip->update(['driver_unavailable_at' => now(), 'driver_unavailable_reason' => 'x']);
    TripDriverIncident::create([
        'trip_id' => $trip->id, 'reported_driver_id' => $c['driver']->id, 'reason' => 'x',
        'reported_at' => now(), 'status' => TripDriverIncident::STATUS_OPEN,
    ]);

    $svc->markRanCompleted($trip);

    $trip->refresh();
    expect($trip->status)->toBe(TripStatusEnum::Completed);
    expect($trip->driver_unavailable_at)->toBeNull();

    $incident = TripDriverIncident::where('trip_id', $trip->id)->first();
    expect($incident->status)->toBe('resolved');
    expect($incident->resolution)->toBe('completed'); // chuyến ĐÃ chạy, không phải cancelled
});

it('markRanCompleted idempotent với model cũ: chuyến đã Cancelled thì không ghi đè', function () {
    $c = unavailCtx();
    $trip = mkTrip($c, ['status' => TripStatusEnum::InProgress]);
    $stale = Trip::find($trip->id); // bản sao "cũ" còn thấy in_progress

    // Luồng khác hủy chuyến trước.
    $trip->update(['status' => TripStatusEnum::Cancelled, 'cancelled_at' => now()]);

    app(TripService::class)->markRanCompleted($stale); // dùng model cũ

    expect($trip->fresh()->status)->toBe(TripStatusEnum::Cancelled); // vẫn Cancelled, không bị ghi đè Completed
});

// ─────────────────────────── Callback: webhook trùng + in-flight ───────────

/** Vé pending + payment pending (chờ SePay xác nhận). */
function mkPendingBookingWithPayment(Trip $trip, array $c): array
{
    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $c['stop']->id, 'dropoff_stop_id' => $c['stop']->id,
        'passenger_count' => 1, 'contact_name' => 'K', 'contact_phone' => '0900000000',
        'subtotal' => 120000, 'final_amount' => 120000, 'payment_method' => 'vnpay',
        'payment_status' => 'unpaid', 'booking_status' => 'pending', 'qr_token' => Str::random(32),
        'expires_at' => now()->addMinutes(15),
    ]);
    $payment = Payment::create([
        'booking_id' => $booking->id, 'user_id' => $booking->user_id, 'amount' => 120000,
        'method' => PaymentMethodEnum::Vnpay, 'status' => 'pending',
        'gateway_order_id' => 'XEGHEP-'.strtoupper(Str::random(10)),
    ]);

    return [$booking, $payment];
}

it('webhook SePay trùng: idempotent — không confirm/không phát PaymentProcessed 2 lần', function () {
    Event::fake([PaymentProcessedEvent::class]);
    $c = unavailCtx();
    $trip = mkTrip($c);
    [$booking, $payment] = mkPendingBookingWithPayment($trip, $c);
    $svc = app(PaymentService::class);

    $payload = fn (string $txn) => [
        'transactionContent' => "Thanh toan {$payment->gateway_order_id}", 'amountIn' => 120000, 'id' => $txn,
        'transferType' => 'in', 'accountNumber' => config('services.sepay.bank_acc'),
    ];

    expect($svc->handleSepayWebhook($payload('TXN-1')))->toBeTrue();
    expect($svc->handleSepayWebhook($payload('TXN-2')))->toBeTrue(); // trùng → idempotent

    $payment->refresh();
    expect($payment->status->value)->toBe('success');
    expect($payment->gateway_txn_id)->toBe('TXN-1'); // lần 2 KHÔNG ghi đè
    expect($booking->fresh()->booking_status->value)->toBe('confirmed');
    Event::assertDispatchedTimes(PaymentProcessedEvent::class, 1); // chỉ 1 lần
});

it('callback in-flight trên chuyến đã có cờ: giữ ghế + báo khách đúng 1 lần (dedupe)', function () {
    $c = unavailCtx();
    $trip = mkTrip($c);
    [$booking, $payment] = mkPendingBookingWithPayment($trip, $c);
    $svc = app(PaymentService::class);

    // Chuyến bị báo nghỉ khi khách đang thanh toán dở (vé còn pending → bulk listener bỏ qua).
    app(TripService::class)->reportDriverUnavailable($trip->id, $c['driver']->id, 'x');

    // Callback đến sau: confirm vé, GIỮ ghế (không tự hủy), báo riêng cho khách.
    $svc->handleSepayWebhook([
        'transactionContent' => "Thanh toan {$payment->gateway_order_id}", 'amountIn' => 120000, 'id' => 'TXN-1',
        'transferType' => 'in', 'accountNumber' => config('services.sepay.bank_acc'),
    ]);

    expect($booking->fresh()->booking_status->value)->toBe('confirmed'); // giữ ghế

    $unavailCount = Notification::where('user_id', $booking->user_id)
        ->where('type', NotificationTypeEnum::TripDriverUnavailable->value)
        ->where('channel', 'in_app')
        ->count();
    expect($unavailCount)->toBe(1); // đúng 1 (dedupe theo incident)

    // Webhook trùng lần 2 KHÔNG tạo thêm thông báo (idempotent + dedupe).
    $svc->handleSepayWebhook([
        'transactionContent' => "Thanh toan {$payment->gateway_order_id}", 'amountIn' => 120000, 'id' => 'TXN-2',
        'transferType' => 'in', 'accountNumber' => config('services.sepay.bank_acc'),
    ]);
    expect(Notification::where('user_id', $booking->user_id)
        ->where('type', NotificationTypeEnum::TripDriverUnavailable->value)
        ->where('channel', 'in_app')->count())->toBe(1);
});

// ─────────────────────────── NotificationService: dedupe vs FK ─────────────

it('NotificationService KHÔNG nuốt lỗi FK (chỉ nuốt trùng dedupe_key)', function () {
    // User "ma" có id hợp lệ nhưng KHÔNG tồn tại trong DB → insert notification vi phạm FK
    // (SQLSTATE 23000 nhưng KHÔNG phải trùng unique). Phải ném ra, không được nuốt lặng.
    $ghost = User::factory()->make(['role' => UserRoleEnum::Customer]);
    $ghost->id = (string) Str::uuid();

    expect(fn () => app(NotificationService::class)->send(
        $ghost,
        NotificationTypeEnum::TripDriverUnavailable,
        ['title' => 't', 'body' => 'b'],
        [NotificationChannelEnum::InApp],
        'dedupe-key-fk-test',
    ))->toThrow(QueryException::class);
});
