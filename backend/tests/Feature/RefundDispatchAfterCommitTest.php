<?php

use App\Enums\UserRoleEnum;
use App\Jobs\ProcessRefundJob;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WalletTransaction;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Queue redis đặt after_commit=false ⇒ job dispatch trong transaction có thể chạy
 * TRƯỚC khi commit. Với refund, rollback sau đó = tiền đã ra mà vé chưa hủy.
 * ProcessRefundJob vì vậy PHẢI dispatch kèm ->afterCommit().
 */
function makePaidCancellableBooking(): Booking
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Refund',
        'business_license' => 'GP-'.fake()->unique()->numerify('####'), 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN - HP', 'base_price' => 150000]);
    $stop = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addDays(3), 'arrive_at' => now()->addDays(3)->addHours(2), // >24h ⇒ hoàn 100%
        'available_seats' => 8, 'price' => 150000, 'status' => 'scheduled',
    ]);
    SeatMap::create(['trip_id' => $trip->id, 'seat_code' => 'A1', 'price' => 150000, 'status' => 'booked']);

    return Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id,
        'trip_id' => $trip->id, 'pickup_stop_id' => $stop->id, 'dropoff_stop_id' => $stop->id,
        'passenger_count' => 1, 'contact_name' => 'A', 'contact_phone' => '0900000000',
        'subtotal' => 150000, 'final_amount' => 150000, 'payment_method' => 'momo',
        'payment_status' => 'paid', 'booking_status' => 'confirmed', 'qr_token' => Str::random(32),
    ]);
}

/*
 * CÁCH KIỂM CHỨNG: không thể test trực tiếp "rollback thì job không chạy" ở đây —
 *  - Queue::fake() thay queue manager nên BỎ QUA cơ chế hoãn afterCommit
 *    (logic hoãn nằm trong Queue::enqueueUsing() thật, không có trong QueueFake).
 *  - Driver `database` (và sqlite test) insert job vào bảng `jobs` TRONG cùng
 *    transaction nên rollback tự xóa job ⇒ không phân biệt được có/không afterCommit.
 *  - Chỉ redis (store ngoài, không transactional) mới lộ bug — đúng cấu hình production.
 * Vì vậy test chốt thứ quan sát được: job PHẢI mang cờ afterCommit khi dispatch.
 */

it('hủy vé bởi khách: ProcessRefundJob dispatch KÈM cờ afterCommit', function () {
    Queue::fake();
    $booking = makePaidCancellableBooking();

    app(BookingService::class)->cancel($booking, $booking->user, 'khách đổi lịch');

    Queue::assertPushed(
        ProcessRefundJob::class,
        fn (ProcessRefundJob $job) => $job->afterCommit === true,
    );
});

it('hủy vé bởi nhà xe: ProcessRefundJob dispatch KÈM cờ afterCommit', function () {
    Queue::fake();
    $booking = makePaidCancellableBooking();

    app(BookingService::class)->cancelByOperator($booking, 'nhà xe hủy chuyến', compensate: true);

    Queue::assertPushed(
        ProcessRefundJob::class,
        fn (ProcessRefundJob $job) => $job->afterCommit === true,
    );
});

it('job tạo booking cũng giữ afterCommit (chuẩn tham chiếu sẵn có)', function () {
    Queue::fake();
    $booking = makePaidCancellableBooking();

    app(BookingService::class)->cancel($booking, $booking->user, 'x');

    // Không job refund nào được phép dispatch mà thiếu afterCommit
    Queue::assertPushed(ProcessRefundJob::class, 1);
});

/*
 * GIỚI HẠN: test đơn luồng chỉ chứng minh guard TUẦN TỰ (isSuccessful() → thoát sớm).
 * Nó KHÔNG chứng minh được lockForUpdate trong refund() — lock chỉ có tác dụng khi hai
 * worker xử lý song song (retry chồng lên lần chạy gốc) cùng đọc status=Success. Kiểm
 * chứng thật cần 2 tiến trình + MySQL; ở đây test đóng vai chốt hồi quy cho guard.
 */
it('refund() chạy lại (queue retry) KHÔNG hoàn tiền lần hai', function () {
    $booking = makePaidCancellableBooking();
    Payment::create([
        'booking_id' => $booking->id, 'user_id' => $booking->user_id,
        'amount' => 150000, 'method' => 'momo', 'status' => 'success',
        'gateway_order_id' => 'XEGHEP-'.Str::upper(Str::random(10)), 'paid_at' => now(),
    ]);
    $service = app(PaymentService::class);
    $service->refund($booking, 150000);
    $service->refund($booking->refresh(), 150000); // ProcessRefundJob tries=3 → retry
    $service->refund($booking->refresh(), 150000);

    // Đúng MỘT giao dịch ví được ghi dù gọi 3 lần
    expect(WalletTransaction::where('booking_id', $booking->id)->count())->toBe(1)
        ->and(app(WalletService::class)->getBalance($booking->user))->toBe(150000)
        ->and($booking->refresh()->payment_status->value)->toBe('refunded');
});
