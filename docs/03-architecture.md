# ARCHITECTURE DOCUMENT
# Xe Ghép Tuyến Hà Nội – Hải Phòng
> Mọi quyết định kiến trúc đã được thống nhất. AI Agent phải tuân thủ các pattern này.

> ⚠️ ĐÍCH SẢN XUẤT vs TRIỂN KHAI HIỆN TẠI (cập nhật 2026-06-25): sơ đồ và các mục dưới mô tả
> kiến trúc ĐÍCH (Redis cache/queue/session, Laravel Reverb WebSocket). NHƯNG cấu hình chạy
> hiện tại là: `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `BROADCAST_CONNECTION=log`,
> và `laravel/reverb`/`predis` CHƯA cài (xem memory §6). Pattern (Service/Repo, event-driven,
> queue cho external call) vẫn áp dụng; chỉ khác driver hạ tầng. Muốn bật Redis/Reverb thật →
> cài package + đổi env trước.

---

## 1. SYSTEM ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENTS                              │
│  Customer SPA  │  Driver PWA  │  Operator SPA  │  Admin SPA │
└───────┬────────┴──────┬───────┴───────┬─────────┴─────┬─────┘
        │               │               │               │
        └───────────────┴───────────────┴───────────────┘
                                │
                    ┌───────────▼──────────┐
                    │    Nginx (Reverse    │
                    │    Proxy + SSL)      │
                    └───────────┬──────────┘
                                │
                    ┌───────────▼──────────┐
                    │   Laravel 13 App     │
                    │  (PHP-FPM, port 9000)│
                    │                      │
                    │  ┌────────────────┐  │
                    │  │ Route Layer    │  │
                    │  │ 4 guard files  │  │
                    │  └───────┬────────┘  │
                    │          │           │
                    │  ┌───────▼────────┐  │
                    │  │ Middleware     │  │
                    │  │ auth:customer  │  │
                    │  │ auth:driver    │  │
                    │  │ auth:operator  │  │
                    │  │ auth:admin     │  │
                    │  └───────┬────────┘  │
                    │          │           │
                    │  ┌───────▼────────┐  │
                    │  │ Controllers    │  │
                    │  │ (HTTP layer)   │  │
                    │  └───────┬────────┘  │
                    │          │           │
                    │  ┌───────▼────────┐  │
                    │  │ Services       │  │
                    │  │(Business Logic)│  │
                    │  └───────┬────────┘  │
                    │          │           │
                    │  ┌───────▼────────┐  │
                    │  │ Repositories   │  │
                    │  │ (Data Access)  │  │
                    │  └───────┬────────┘  │
                    │          │           │
                    └──────────┼───────────┘
                               │
              ┌────────────────┼───────────────────┐
              │                │                   │
    ┌─────────▼──────┐ ┌───────▼──────┐ ┌─────────▼──────┐
    │    MySQL 8.0   │ │  Redis 7.0   │ │ Laravel Reverb │
    │  (Primary DB)  │ │  Cache/Queue │ │  (WebSocket)   │
    └────────────────┘ └──────────────┘ └────────────────┘
```

---

## 2. AUTHENTICATION ARCHITECTURE

### 2.1 Multi-Guard Setup
```php
// config/auth.php — 4 guards, cùng model User nhưng tách biệt hoàn toàn

'defaults' => ['guard' => 'customer', 'passwords' => 'users'],

'guards' => [
    'customer' => [
        'driver'   => 'sanctum',
        'provider' => 'customers',
    ],
    'driver' => [
        'driver'   => 'sanctum',
        'provider' => 'drivers',
    ],
    'operator' => [
        'driver'   => 'sanctum',
        'provider' => 'operators',
    ],
    'admin' => [
        'driver'   => 'sanctum',
        'provider' => 'admins',
    ],
],

'providers' => [
    'customers' => ['driver' => 'eloquent', 'model' => User::class],
    'drivers'   => ['driver' => 'eloquent', 'model' => User::class],
    'operators' => ['driver' => 'eloquent', 'model' => User::class],
    'admins'    => ['driver' => 'eloquent', 'model' => User::class],
],
```

> ⚠️ TRẠNG THÁI THỰC TẾ (cập nhật 2026-06-25, xem memory §4.13): vì cả 4 guard đều dùng
> Sanctum + CHUNG model `User`, token sẽ resolve tokenable bỏ qua provider của guard ⇒ đổi
> `auth:customer/driver/operator/admin` KHÔNG lọc được theo vai trò. Cô lập 4 portal hiện
> dựa vào **middleware `role`** chứ không phải guard:
> - 4 route group dùng `['auth:sanctum', 'role:<portal>']` (KHÔNG dùng `auth:<guard>`).
> - `app/Http/Middleware/EnsureUserRole.php` so `user()->role` với tham số → lệch thì abort(403).
> - `config/auth.php` thực tế: `'defaults' => ['guard' => env('AUTH_GUARD','web')]` (web=session),
>   4 guard customer/driver/operator/admin vẫn khai báo nhưng việc phân quyền do `role:` đảm nhận.
> Phần "Token Naming" (§2.2) và "auth:customer" (§2.3) bên dưới là Ý ĐỊNH BAN ĐẦU — đọc kèm §4.13.

### 2.2 Token Naming Convention (Sanctum)
```php
// Mỗi guard tạo token với tên riêng để dễ debug
'customer_token'   // customers
'driver_token'     // drivers
'operator_token'   // operators
'admin_token'      // admins
```

### 2.3 Route Protection Pattern
```php
// routes/api_customer.php
Route::prefix('customer')->group(function () {
    // Public routes (auth không cần)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:customer')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::apiResource('/bookings', BookingController::class);
        // ... other protected routes
    });
});
```

---

## 3. SERVICE LAYER PATTERN

### 3.1 Nguyên tắc bắt buộc
```
Controller → Service → Repository → Model → Database

Controller : Chỉ xử lý HTTP (validate request, trả response, HTTP status codes)
Service    : Toàn bộ business logic, orchestrate nhiều repository
Repository : Query database, KHÔNG chứa business logic
Model      : Định nghĩa relationships, scopes, accessors
```

### 3.2 Service Pattern chuẩn
```php
<?php
namespace App\Services;

class BookingService
{
    public function __construct(
        private readonly BookingRepository  $bookingRepo,
        private readonly TripRepository     $tripRepo,
        private readonly SeatMapRepository  $seatRepo,
        private readonly VoucherService     $voucherService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Tạo booking mới
     *
     * @throws SeatNotAvailableException
     * @throws BookingValidationException
     */
    public function create(array $data, User $user): Booking
    {
        return DB::transaction(function () use ($data, $user) {
            // Business logic ở đây
            // Không có Request, không có Response
        });
    }
}
```

### 3.3 Repository Pattern chuẩn
```php
<?php
namespace App\Repositories\Contracts;

interface BookingRepositoryInterface
{
    public function findByCode(string $code): ?Booking;
    public function findByUser(User $user, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Booking;
    public function updateStatus(Booking $booking, string $status): bool;
}

// app/Repositories/BookingRepository.php
class BookingRepository implements BookingRepositoryInterface
{
    public function findByCode(string $code): ?Booking
    {
        return Booking::with(['trip.route', 'trip.driver', 'passengers'])
                      ->where('booking_code', $code)
                      ->first();
    }
}
```

### 3.4 Binding trong ServiceProvider
```php
// app/Providers/RepositoryServiceProvider.php
public function register(): void
{
    $bindings = [
        BookingRepositoryInterface::class => BookingRepository::class,
        TripRepositoryInterface::class    => TripRepository::class,
        UserRepositoryInterface::class    => UserRepository::class,
    ];

    foreach ($bindings as $interface => $implementation) {
        $this->app->bind($interface, $implementation);
    }
}
```

---

## 4. CONTROLLER PATTERN

```php
<?php
namespace App\Http\Controllers\Customer;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->create(
                $request->validated(),
                auth('customer')->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'data'    => new BookingResource($booking),
            ], 201);

        } catch (SeatNotAvailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => 'SEAT_NOT_AVAILABLE',
            ], 422);

        } catch (Exception $e) {
            Log::error('Booking creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra, vui lòng thử lại',
            ], 500);
        }
    }
}
```

---

## 5. EVENT-DRIVEN ARCHITECTURE

### 5.1 Event Flow
```
BookingService::create()
    → fire BookingConfirmed event
        → SendBookingConfirmationNotification listener
            → dispatch SendSmsNotificationJob (queue: notifications)
            → dispatch SendZaloNotificationJob (queue: notifications)
            → dispatch SendEmailNotificationJob (queue: notifications)
        → UpdateSeatAvailabilityListener
            → broadcast SeatStatusUpdated (WebSocket)
```

### 5.2 Queue Configuration
```php
// Các queue theo priority
'high'          => Thanh toán, booking expiry
'notifications' => SMS, Zalo, Email, Push
'default'       => QR code generation, reports
'low'           => Analytics, cleanup jobs
```

### 5.3 Event/Listener Map
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    BookingConfirmed::class => [
        SendBookingConfirmationNotification::class,
        UpdateSeatAvailability::class,
    ],
    BookingCancelled::class => [
        SendCancellationNotification::class,
        ProcessRefund::class,
        ReleaseSeatLock::class,
    ],
    TripStarted::class => [
        NotifyPassengersOnTripStart::class,
        StartGpsTracking::class,
    ],
    TripCompleted::class => [
        NotifyPassengersOnTripComplete::class,
        CalculateDriverEarnings::class,
        TriggerReviewPrompt::class,
    ],
    PaymentProcessed::class => [
        ConfirmBooking::class,
        GenerateQrCode::class,
        UpdateWalletBalance::class,
    ],
    DriverLocationUpdated::class => [
        BroadcastDriverLocation::class,   // WebSocket
        CalculateEtaForPassengers::class, // Google Maps API
    ],
];
```

---

## 6. REAL-TIME ARCHITECTURE (Laravel Reverb)

### 6.1 WebSocket Channels
```php
// routes/channels.php

// Private channel cho customer theo dõi booking của họ
Broadcast::channel('bookings.{bookingId}', function (User $user, string $bookingId) {
    return $user->bookings()->where('id', $bookingId)->exists();
});

// Presence channel cho trip (driver + passengers)
Broadcast::channel('trips.{tripId}', function (User $user, string $tripId) {
    $booking = $user->bookings()
                    ->where('trip_id', $tripId)
                    ->whereIn('booking_status', ['confirmed', 'checked_in'])
                    ->exists();
    return $booking ? ['id' => $user->id, 'name' => $user->full_name] : false;
});

// Private channel cho driver
Broadcast::channel('drivers.{driverId}', function (User $user, string $driverId) {
    return $user->driver?->id === $driverId;
});

// Private channel cho admin (monitor tất cả)
Broadcast::channel('admin.monitor', function (User $user) {
    return $user->role === 'admin';
});
```

### 6.2 Broadcast Events
```php
// DriverLocationUpdated — broadcast real-time GPS
class DriverLocationUpdated implements ShouldBroadcastNow
{
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("trips.{$this->trip->id}"),
            new PrivateChannel("admin.monitor"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'lat'         => $this->lat,
            'lng'         => $this->lng,
            'updated_at'  => now()->toIso8601String(),
            'eta_minutes' => $this->etaMinutes,
        ];
    }
}
```

---

## 7. CACHING STRATEGY

```php
// Redis Cache Keys (convention)
"trips:search:{hash_of_params}"         TTL: 2 phút  (search results)
"trip:{trip_id}:seats"                  TTL: 30 giây (seat map)
"driver_location:{driver_id}"           TTL: 30 giây (GPS)
"otp:{phone}"                           TTL: 5 phút  (OTP)
"otp_count:{phone}"                     TTL: 1 giờ   (rate limit)
"seat_lock:{seat_id}"                   TTL: 10 phút (seat lock)
"booking_expire:{booking_id}"           TTL: 15 phút (payment timeout)

// Cache helper pattern trong Service
$trips = Cache::remember(
    "trips:search:{$cacheKey}",
    now()->addMinutes(2),
    fn() => $this->tripRepo->search($filters)
);

// Invalidate khi có booking mới
Cache::forget("trips:search:{$cacheKey}");
```

---

## 8. DATABASE PATTERNS

### 8.1 UUID cho primary keys
```php
// Tất cả model dùng UUID thay vì auto-increment
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    use HasUuids;
    // id tự động được generate là UUID v7
}
```

### 8.2 Soft Delete
```php
// Chỉ áp dụng soft delete cho: users, operators, drivers, vehicles
// KHÔNG soft delete: bookings, payments, transactions (cần audit trail đầy đủ)
use SoftDeletes;
```

### 8.3 Query Optimization Rules
```php
// LUÔN eager load relationships
Trip::with(['route', 'driver:id,full_name,rating_avg', 'vehicle:id,plate_number,vehicle_type'])
    ->get();

// LUÔN paginate list queries
Booking::where('user_id', $userId)->paginate(10);

// Dùng select() để giới hạn columns khi không cần tất cả
Driver::select('id', 'full_name', 'rating_avg', 'avatar_url')
      ->where('operator_id', $operatorId)
      ->get();

// Dùng chunk() cho batch operations
Booking::where('expires_at', '<', now())
        ->where('payment_status', 'unpaid')
        ->chunk(100, function ($bookings) {
            // process each chunk
        });
```

### 8.4 Transaction Pattern
```php
// Luôn dùng transaction cho operations ảnh hưởng nhiều bảng
DB::transaction(function () {
    // booking + seat update + trip update phải atomic
}, attempts: 3); // retry 3 lần nếu deadlock
```

---

## 9. API RESPONSE FORMAT

### 9.1 Success Response
```json
{
    "success": true,
    "message": "Đặt vé thành công",
    "data": {
        "id": "uuid",
        "booking_code": "HNHP240615001"
    }
}
```

### 9.2 Paginated Response
```json
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 45,
        "last_page": 5
    }
}
```

### 9.3 Error Response
```json
{
    "success": false,
    "message": "Ghế A1 đã được đặt bởi người khác",
    "code": "SEAT_NOT_AVAILABLE",
    "errors": {
        "seat_ids": ["Ghế A1 không còn trống"]
    }
}
```

### 9.4 HTTP Status Codes
```
200 OK            : GET thành công
201 Created       : POST tạo mới thành công
204 No Content    : DELETE thành công
400 Bad Request   : Request không hợp lệ
401 Unauthorized  : Chưa đăng nhập / token hết hạn
403 Forbidden     : Không có quyền
404 Not Found     : Không tìm thấy resource
422 Unprocessable : Validation error / business rule violation
429 Too Many Req  : Rate limit
500 Server Error  : Lỗi server (luôn log)
```

---

## 10. NOTIFICATION ARCHITECTURE

```
Trigger (Event)
    │
    ▼
NotificationService::send(User $user, string $type, array $data)
    │
    ├──→ [SMS channel]   → SendSmsJob    → ESMS.vn API
    ├──→ [Zalo channel]  → SendZaloJob   → Zalo OA API
    ├──→ [Email channel] → SendEmailJob  → SMTP (Laravel Mail)
    ├──→ [Push channel]  → SendPushJob   → Firebase FCM
    └──→ [In-app]        → notifications table (DB)

// NotificationService không gửi trực tiếp — luôn dispatch Job
// Jobs chạy trên queue 'notifications'
// Retry 3 lần nếu thất bại, delay 60 giây
// Log kết quả vào notifications table
```

---

## 11. PAYMENT ARCHITECTURE

```
Customer → POST /api/customer/payments/initiate
    │
    ▼
PaymentService::initiate(Booking $booking, string $method)
    │
    ├── method=momo    → MoMoGateway::createPayment() → return payment_url
    ├── method=vnpay   → VNPayGateway::createPayment() → return payment_url
    └── method=wallet  → WalletService::debit() → return success immediately
    │
    ▼
Customer redirect → Payment Gateway → Complete → Callback URL

Payment Gateway → POST /api/public/payments/{method}/callback
    │
    ▼
PaymentService::handleCallback(array $payload, string $method)
    1. Verify HMAC signature (bắt buộc, từ chối nếu sai)
    2. Idempotency check (nếu đã xử lý → trả 200, bỏ qua)
    3. Update payment status
    4. Fire PaymentProcessed event
    5. Return HTTP 200 (bắt buộc với MoMo/VNPay)
```

---

## 12. ERROR HANDLING

### 12.1 Custom Exceptions
```php
app/Exceptions/
├── SeatNotAvailableException.php   → 422
├── BookingExpiredException.php     → 422
├── TripNotAvailableException.php   → 422
├── InsufficientBalanceException.php → 422
├── InvalidOtpException.php         → 422
├── PaymentVerificationException.php → 400
└── UnauthorizedActionException.php  → 403
```

### 12.2 Global Exception Handler
```php
// app/Exceptions/Handler.php
// Tất cả exception phải được catch và trả về format chuẩn
// Không để Laravel trả HTML error page cho API routes
// Log tất cả 5xx errors với context đầy đủ
```

---

## 13. TESTING STRATEGY

```
tests/
├── Unit/
│   ├── Services/BookingServiceTest.php      (unit test service logic)
│   ├── Services/VoucherServiceTest.php
│   └── Models/BookingTest.php               (test model methods)
├── Feature/
│   ├── Customer/
│   │   ├── AuthTest.php                     (API endpoint tests)
│   │   ├── BookingTest.php
│   │   └── PaymentTest.php
│   ├── Driver/
│   │   ├── TripTest.php
│   │   └── CheckinTest.php
│   ├── Operator/
│   │   └── RouteTest.php
│   └── Admin/
│       └── UserManagementTest.php
└── Integration/
    └── PaymentGatewayTest.php               (test với mock gateway)

// Test coverage target: 70% cho Services, 80% cho critical paths (booking, payment)
```