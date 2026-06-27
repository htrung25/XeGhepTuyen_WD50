# Task Document: Website Đặt Xe Ghép Tuyến Hà Nội – Hải Phòng
> Dành cho AI Agent tự động sinh code Laravel 13 hoàn chỉnh  
> Version: 1.0 | Ngày: 2025 | Stack: Laravel 13 + Vue 3 + MySQL + Redis

---

## 1. TỔNG QUAN DỰ ÁN

### 1.1 Mô tả
Nền tảng đặt xe ghép tuyến cố định **Hà Nội ↔ Hải Phòng**, kết nối hành khách với các nhà xe ghép (xe 7–16 chỗ). Điểm đặc biệt: hành khách được đón/trả tận địa chỉ, không cần ra bến xe.

### 1.2 Thông tin tuyến đường
- **Tuyến**: Hà Nội ↔ Hải Phòng
- **Khoảng cách**: ~120 km
- **Thời gian di chuyển**: 1.5 – 2.5 giờ (tùy điểm đón/trả)
- **Giá vé cơ bản**: 120.000đ – 180.000đ/người
- **Loại xe**: 7 chỗ, 9 chỗ, 16 chỗ
- **Tần suất**: Mỗi 30–60 phút, 5:00 – 21:00 hàng ngày

### 1.3 Các điểm đón/trả cố định trên tuyến

**Phía Hà Nội (điểm xuất phát):**
| STT | Tên điểm | Địa chỉ | Thứ tự | Offset (phút) |
|-----|----------|---------|--------|---------------|
| 1 | Mỹ Đình | 20 Phạm Hùng, Nam Từ Liêm | 1 | 0 |
| 2 | Cầu Giấy | Ngã tư Cầu Giấy, Trần Duy Hưng | 2 | 10 |
| 3 | Trung Hòa | 234 Hoàng Quốc Việt | 3 | 15 |
| 4 | Giải Phóng | 487 Giải Phóng, Hoàng Mai | 4 | 25 |
| 5 | Gia Lâm | Bến xe Gia Lâm, Long Biên | 5 | 35 |

**Phía Hải Phòng (điểm đến):**
| STT | Tên điểm | Địa chỉ | Thứ tự | Offset (phút) |
|-----|----------|---------|--------|---------------|
| 6 | An Dương | Quốc lộ 5, An Dương | 6 | 100 |
| 7 | Cầu Rào | Ngã tư Cầu Rào, Lê Chân | 7 | 110 |
| 8 | Lạch Tray | 111 Lạch Tray, Ngô Quyền | 8 | 115 |
| 9 | Trung tâm HP | 1 Đinh Tiên Hoàng, Hồng Bàng | 9 | 120 |
| 10 | Máy Tơ | Chợ Máy Tơ, Ngô Quyền | 10 | 125 |

### 1.4 Tech Stack
```
Backend   : Laravel 13 (PHP 8.3)
Frontend  : Vue 3 + Inertia.js hoặc API-only + Vue SPA
Database  : MySQL 8.0
Cache     : Redis 7
Queue     : Laravel Queue (Redis driver)
Auth      : Laravel Sanctum (4 guards riêng biệt)
Realtime  : Laravel Reverb (WebSocket)
Storage   : Laravel Storage (S3 hoặc local)
Payment   : MoMo, VNPay, ZaloPay
Maps      : Google Maps API
SMS       : ESMS.vn
Notify    : Firebase FCM (push) + Zalo OA
```

---

## 2. DATABASE SCHEMA CHI TIẾT

### 2.1 Bảng `users`
```sql
CREATE TABLE users (
    id              CHAR(36) PRIMARY KEY,           -- UUID
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(15) NOT NULL UNIQUE,
    email           VARCHAR(100) UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('customer','driver','operator','admin') NOT NULL DEFAULT 'customer',
    avatar_url      VARCHAR(500),
    zalo_user_id    VARCHAR(100),                   -- Zalo OA binding
    fcm_token       VARCHAR(500),                   -- Firebase push token
    is_verified     BOOLEAN NOT NULL DEFAULT FALSE, -- OTP phone verified
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at   TIMESTAMP,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_role_active (role, is_active)
);
```

### 2.2 Bảng `operators` (nhà xe)
```sql
CREATE TABLE operators (
    id                  CHAR(36) PRIMARY KEY,
    user_id             CHAR(36) NOT NULL,
    company_name        VARCHAR(200) NOT NULL,
    business_license    VARCHAR(100) NOT NULL,      -- Số giấy phép kinh doanh
    tax_code            VARCHAR(20),
    bank_account        VARCHAR(30),
    bank_name           VARCHAR(100),
    bank_account_name   VARCHAR(100),
    commission_rate     DECIMAL(5,2) DEFAULT 10.00, -- % hoa hồng nền tảng
    logo_url            VARCHAR(500),
    description         TEXT,
    status              ENUM('pending','verified','suspended') DEFAULT 'pending',
    verified_at         TIMESTAMP,
    verified_by         CHAR(36),                   -- admin user_id
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status (status)
);
```

### 2.3 Bảng `drivers` (tài xế)
```sql
CREATE TABLE drivers (
    id                  CHAR(36) PRIMARY KEY,
    user_id             CHAR(36) NOT NULL,
    operator_id         CHAR(36) NOT NULL,
    license_number      VARCHAR(20) NOT NULL UNIQUE, -- Số GPLX
    license_class       VARCHAR(5) NOT NULL,          -- B2, C, D, E
    license_expiry      DATE NOT NULL,
    id_card_number      VARCHAR(20) NOT NULL,
    id_card_front_url   VARCHAR(500),
    id_card_back_url    VARCHAR(500),
    license_front_url   VARCHAR(500),
    rating_avg          DECIMAL(3,2) DEFAULT 5.00,
    total_trips         INT DEFAULT 0,
    is_online           BOOLEAN DEFAULT FALSE,        -- đang nhận chuyến
    current_lat         DECIMAL(10,8),                -- vị trí GPS hiện tại
    current_lng         DECIMAL(11,8),
    location_updated_at TIMESTAMP,
    status              ENUM('pending','verified','suspended') DEFAULT 'pending',
    verified_at         TIMESTAMP,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (operator_id) REFERENCES operators(id),
    INDEX idx_operator (operator_id),
    INDEX idx_status (status),
    INDEX idx_online (is_online)
);
```

### 2.4 Bảng `vehicles` (phương tiện)
```sql
CREATE TABLE vehicles (
    id                  CHAR(36) PRIMARY KEY,
    operator_id         CHAR(36) NOT NULL,
    plate_number        VARCHAR(15) NOT NULL UNIQUE, -- Biển số xe
    brand               VARCHAR(50) NOT NULL,         -- Toyota, Ford...
    model               VARCHAR(50) NOT NULL,         -- Innova, Transit...
    color               VARCHAR(30),
    year                YEAR,
    vehicle_type        ENUM('sedan_4','mpv_7','van_9','minibus_16') NOT NULL,
    seat_count          TINYINT NOT NULL,
    registration_number VARCHAR(50),                  -- Số đăng ký
    registration_expiry DATE,
    insurance_expiry    DATE,
    image_url           VARCHAR(500),
    amenities           JSON,                         -- ["wifi","usb","ac"]
    status              ENUM('active','maintenance','inactive') DEFAULT 'active',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(id),
    INDEX idx_operator_status (operator_id, status)
);
```

### 2.5 Bảng `routes` (tuyến đường)
```sql
CREATE TABLE routes (
    id              CHAR(36) PRIMARY KEY,
    operator_id     CHAR(36) NOT NULL,
    name            VARCHAR(200) NOT NULL,            -- "Hà Nội → Hải Phòng"
    origin_city     VARCHAR(100) NOT NULL DEFAULT 'Hà Nội',
    dest_city       VARCHAR(100) NOT NULL DEFAULT 'Hải Phòng',
    distance_km     SMALLINT NOT NULL DEFAULT 120,
    est_duration_min SMALLINT NOT NULL DEFAULT 120,
    base_price      DECIMAL(10,0) NOT NULL,           -- Giá cơ bản (đồng)
    is_active       BOOLEAN DEFAULT TRUE,
    is_round_trip   BOOLEAN DEFAULT TRUE,             -- Có chiều về không
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(id)
);
```

### 2.6 Bảng `route_stops` (điểm dừng trên tuyến)
```sql
CREATE TABLE route_stops (
    id              CHAR(36) PRIMARY KEY,
    route_id        CHAR(36) NOT NULL,
    stop_name       VARCHAR(100) NOT NULL,
    address         VARCHAR(300) NOT NULL,
    lat             DECIMAL(10,8) NOT NULL,
    lng             DECIMAL(11,8) NOT NULL,
    stop_order      TINYINT NOT NULL,                 -- Thứ tự dừng
    offset_minutes  SMALLINT NOT NULL DEFAULT 0,      -- Phút tính từ điểm xuất phát
    is_pickup       BOOLEAN DEFAULT TRUE,             -- Có đón khách không
    is_dropoff      BOOLEAN DEFAULT TRUE,             -- Có trả khách không
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    UNIQUE KEY uq_route_order (route_id, stop_order)
);
```

### 2.7 Bảng `trips` (chuyến đi)
```sql
CREATE TABLE trips (
    id              CHAR(36) PRIMARY KEY,
    route_id        CHAR(36) NOT NULL,
    vehicle_id      CHAR(36) NOT NULL,
    driver_id       CHAR(36) NOT NULL,
    depart_at       DATETIME NOT NULL,               -- Giờ xuất phát
    arrive_at       DATETIME NOT NULL,               -- Giờ đến dự kiến
    available_seats TINYINT NOT NULL,
    price           DECIMAL(10,0) NOT NULL,          -- Giá vé chuyến này
    note            TEXT,
    tracking_code   VARCHAR(20) UNIQUE,              -- Mã theo dõi public
    status          ENUM('scheduled','boarding','in_progress','completed','cancelled') DEFAULT 'scheduled',
    started_at      TIMESTAMP,
    completed_at    TIMESTAMP,
    cancelled_at    TIMESTAMP,
    cancel_reason   VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    INDEX idx_route_depart (route_id, depart_at, status),
    INDEX idx_driver_status (driver_id, status),
    INDEX idx_tracking (tracking_code)
);
```

### 2.8 Bảng `seat_maps` (sơ đồ ghế)
```sql
CREATE TABLE seat_maps (
    id          CHAR(36) PRIMARY KEY,
    trip_id     CHAR(36) NOT NULL,
    seat_code   VARCHAR(10) NOT NULL,                -- A1, A2, B1...
    seat_type   ENUM('standard','vip') DEFAULT 'standard',
    price       DECIMAL(10,0) NOT NULL,
    status      ENUM('available','locked','booked','disabled') DEFAULT 'available',
    locked_at   TIMESTAMP,                           -- Thời điểm lock (giữ ghế)
    locked_by   CHAR(36),                            -- user_id đang giữ
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    UNIQUE KEY uq_trip_seat (trip_id, seat_code),
    INDEX idx_trip_status (trip_id, status)
);
```

### 2.9 Bảng `bookings` (đặt chỗ)
```sql
CREATE TABLE bookings (
    id                  CHAR(36) PRIMARY KEY,
    booking_code        VARCHAR(20) NOT NULL UNIQUE,  -- VD: HNHP240615001
    user_id             CHAR(36) NOT NULL,
    trip_id             CHAR(36) NOT NULL,
    pickup_stop_id      CHAR(36) NOT NULL,            -- Điểm đón cố định
    dropoff_stop_id     CHAR(36) NOT NULL,            -- Điểm trả cố định
    pickup_address      VARCHAR(500),                 -- Địa chỉ đón linh hoạt
    dropoff_address     VARCHAR(500),                 -- Địa chỉ trả linh hoạt
    pickup_lat          DECIMAL(10,8),
    pickup_lng          DECIMAL(11,8),
    passenger_count     TINYINT NOT NULL DEFAULT 1,
    contact_name        VARCHAR(100) NOT NULL,
    contact_phone       VARCHAR(15) NOT NULL,
    note                TEXT,
    subtotal            DECIMAL(10,0) NOT NULL,
    discount_amount     DECIMAL(10,0) DEFAULT 0,
    final_amount        DECIMAL(10,0) NOT NULL,
    payment_method      ENUM('momo','vnpay','zalopay','wallet','cash') DEFAULT 'momo',
    payment_status      ENUM('unpaid','paid','refunded','partial_refund') DEFAULT 'unpaid',
    booking_status      ENUM('pending','confirmed','checked_in','completed','cancelled','no_show') DEFAULT 'pending',
    qr_code             VARCHAR(500),                -- URL ảnh QR code
    qr_token            VARCHAR(100) UNIQUE,          -- Token xác thực QR
    voucher_id          CHAR(36),
    expires_at          TIMESTAMP,                    -- Hết hạn thanh toán (15 phút)
    confirmed_at        TIMESTAMP,
    checked_in_at       TIMESTAMP,
    completed_at        TIMESTAMP,
    cancelled_at        TIMESTAMP,
    cancel_reason       VARCHAR(500),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (pickup_stop_id) REFERENCES route_stops(id),
    FOREIGN KEY (dropoff_stop_id) REFERENCES route_stops(id),
    INDEX idx_user (user_id, created_at DESC),
    INDEX idx_trip (trip_id, booking_status),
    INDEX idx_code (booking_code),
    INDEX idx_qr_token (qr_token),
    INDEX idx_expires (expires_at, payment_status)
);
```

### 2.10 Bảng `booking_passengers` (hành khách)
```sql
CREATE TABLE booking_passengers (
    id          CHAR(36) PRIMARY KEY,
    booking_id  CHAR(36) NOT NULL,
    seat_map_id CHAR(36) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    phone       VARCHAR(15),
    gender      ENUM('male','female','other'),
    is_primary  BOOLEAN DEFAULT FALSE,              -- Người đặt chính
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (seat_map_id) REFERENCES seat_maps(id)
);
```

### 2.11 Bảng `payments` (thanh toán)
```sql
CREATE TABLE payments (
    id                  CHAR(36) PRIMARY KEY,
    booking_id          CHAR(36) NOT NULL,
    user_id             CHAR(36) NOT NULL,
    amount              DECIMAL(10,0) NOT NULL,
    method              ENUM('momo','vnpay','zalopay','wallet','cash') NOT NULL,
    status              ENUM('pending','success','failed','refunded') DEFAULT 'pending',
    gateway_txn_id      VARCHAR(100),               -- Mã GD từ cổng thanh toán
    gateway_order_id    VARCHAR(100),               -- Order ID gửi lên cổng
    gateway_response    JSON,                        -- Raw response từ cổng
    refund_amount       DECIMAL(10,0) DEFAULT 0,
    refunded_at         TIMESTAMP,
    paid_at             TIMESTAMP,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_booking (booking_id),
    INDEX idx_gateway_txn (gateway_txn_id)
);
```

### 2.12 Bảng `vouchers` (mã giảm giá)
```sql
CREATE TABLE vouchers (
    id              CHAR(36) PRIMARY KEY,
    code            VARCHAR(20) NOT NULL UNIQUE,
    operator_id     CHAR(36),                        -- NULL = toàn sàn
    discount_type   ENUM('percent','fixed') NOT NULL,
    discount_value  DECIMAL(10,2) NOT NULL,          -- % hoặc số tiền
    min_order       DECIMAL(10,0) DEFAULT 0,
    max_discount    DECIMAL(10,0),                   -- Giảm tối đa
    usage_limit     INT DEFAULT 1,
    used_count      INT DEFAULT 0,
    valid_from      DATETIME NOT NULL,
    valid_until     DATETIME NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_valid (valid_from, valid_until, is_active)
);
```

### 2.13 Bảng `reviews` (đánh giá)
```sql
CREATE TABLE reviews (
    id              CHAR(36) PRIMARY KEY,
    booking_id      CHAR(36) NOT NULL UNIQUE,
    user_id         CHAR(36) NOT NULL,
    driver_id       CHAR(36) NOT NULL,
    operator_id     CHAR(36) NOT NULL,
    driver_rating   TINYINT NOT NULL CHECK (driver_rating BETWEEN 1 AND 5),
    vehicle_rating  TINYINT NOT NULL CHECK (vehicle_rating BETWEEN 1 AND 5),
    service_rating  TINYINT NOT NULL CHECK (service_rating BETWEEN 1 AND 5),
    comment         TEXT,
    is_published    BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX idx_driver (driver_id),
    INDEX idx_operator (operator_id)
);
```

### 2.14 Bảng `wallets` + `wallet_transactions`
```sql
CREATE TABLE wallets (
    id              CHAR(36) PRIMARY KEY,
    user_id         CHAR(36) NOT NULL UNIQUE,
    balance         DECIMAL(12,0) DEFAULT 0,
    pending_balance DECIMAL(12,0) DEFAULT 0,        -- Đang chờ quyết toán
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE wallet_transactions (
    id              CHAR(36) PRIMARY KEY,
    wallet_id       CHAR(36) NOT NULL,
    booking_id      CHAR(36),
    type            ENUM('topup','payment','refund','payout','commission') NOT NULL,
    amount          DECIMAL(12,0) NOT NULL,
    balance_after   DECIMAL(12,0) NOT NULL,
    description     VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id),
    INDEX idx_wallet (wallet_id, created_at DESC)
);
```

### 2.15 Bảng `notifications`
```sql
CREATE TABLE notifications (
    id          CHAR(36) PRIMARY KEY,
    user_id     CHAR(36) NOT NULL,
    booking_id  CHAR(36),
    type        ENUM('booking_confirmed','booking_cancelled','trip_reminder',
                     'driver_arriving','trip_started','trip_completed',
                     'payment_success','refund_processed','system') NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    data        JSON,                               -- Extra payload
    channel     ENUM('push','sms','zalo','email','in_app') NOT NULL,
    is_read     BOOLEAN DEFAULT FALSE,
    sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_read (user_id, is_read, sent_at DESC)
);
```

---

## 3. ACTOR 1: KHÁCH HÀNG (Customer)

### 3.1 Authentication
**File:** `app/Http/Controllers/Customer/AuthController.php`

**API Endpoints:**
```
POST /api/customer/auth/send-otp          Body: { phone }
POST /api/customer/auth/verify-otp        Body: { phone, otp }
POST /api/customer/auth/register          Body: { phone, full_name, password, otp }
POST /api/customer/auth/login             Body: { phone, password }
POST /api/customer/auth/logout            Header: Bearer token
GET  /api/customer/auth/me                Header: Bearer token
PUT  /api/customer/auth/profile           Body: { full_name, email, avatar }
POST /api/customer/auth/change-password   Body: { old_password, new_password }
```

**Business Logic:**
- OTP gồm 6 chữ số, hết hạn sau 5 phút, lưu Redis: `otp:{phone}` 
- Giới hạn gửi OTP: tối đa 5 lần/giờ/số điện thoại
- Token Sanctum với expiry 30 ngày
- Guard: `customer`

### 3.2 Tìm kiếm chuyến đi
**File:** `app/Http/Controllers/Customer/TripSearchController.php`

**API Endpoints:**
```
GET /api/customer/trips/search
    Query params:
        - from_city: string (Hà Nội|Hải Phòng)
        - to_city: string
        - date: date (Y-m-d)
        - passengers: int (1-4, default 1)
        - sort: price_asc|price_desc|depart_asc (default: depart_asc)

GET /api/customer/trips/{id}              Chi tiết chuyến + sơ đồ ghế

GET /api/customer/trips/{id}/seats        Danh sách ghế với trạng thái real-time

GET /api/customer/trips/{id}/track        Vị trí xe real-time (chỉ khi trip in_progress)
```

**Business Logic - Tìm kiếm:**
```
1. Filter trips theo: route (from_city → to_city), ngày (depart_at DATE), 
   status = 'scheduled', available_seats >= passengers
2. Eager load: route, vehicle, driver (chỉ: name, rating, avatar), route_stops
3. Cache kết quả 2 phút (Redis)
4. Trả về: trip info + available_seats + price + driver rating + vehicle type + amenities
5. Không trả: driver phone, driver ID card, internal notes
```

**Business Logic - Sơ đồ ghế:**
```
1. Lấy tất cả seat_maps của trip
2. Ghế locked quá 10 phút → tự động unlock (job: ExpireLockedSeats)
3. Real-time update qua WebSocket channel: trips.{trip_id}.seats
```

### 3.3 Đặt vé (Booking Flow)
**File:** `app/Http/Controllers/Customer/BookingController.php`
**Service:** `app/Services/BookingService.php`

**API Endpoints:**
```
POST /api/customer/bookings/lock-seats     Giữ ghế tạm 10 phút
POST /api/customer/bookings               Tạo booking
GET  /api/customer/bookings               Lịch sử đặt vé (paginate 10)
GET  /api/customer/bookings/{id}          Chi tiết booking + QR
POST /api/customer/bookings/{id}/cancel   Hủy booking
GET  /api/customer/bookings/{id}/qr       Ảnh QR code
```

**Business Logic - Tạo booking (QUAN TRỌNG):**
```php
// Luồng đặt vé hoàn chỉnh trong BookingService::create()

DB::transaction(function() use ($data) {
    // 1. Lock ghế để tránh race condition
    $seats = SeatMap::whereIn('id', $data['seat_ids'])
                    ->where('trip_id', $data['trip_id'])
                    ->lockForUpdate()  // QUAN TRỌNG
                    ->get();

    // 2. Kiểm tra ghế còn available
    foreach ($seats as $seat) {
        if ($seat->status !== 'available') {
            throw new SeatNotAvailableException("Ghế {$seat->seat_code} đã được đặt");
        }
    }

    // 3. Kiểm tra số ghế khớp với số hành khách
    if ($seats->count() !== $data['passenger_count']) {
        throw new ValidationException("Số ghế không khớp với số hành khách");
    }

    // 4. Tính giá (subtotal, discount, final)
    $subtotal = $seats->sum('price');
    $discount = $this->voucherService->calculate($data['voucher_code'], $subtotal);
    $finalAmount = $subtotal - $discount;

    // 5. Tạo booking record
    $booking = Booking::create([
        'booking_code'  => $this->generateBookingCode(), // HNHP + date + sequence
        'user_id'       => auth()->id(),
        'trip_id'       => $data['trip_id'],
        'pickup_stop_id'  => $data['pickup_stop_id'],
        'dropoff_stop_id' => $data['dropoff_stop_id'],
        'pickup_address'  => $data['pickup_address'],
        'passenger_count' => $data['passenger_count'],
        'contact_name'    => $data['contact_name'],
        'contact_phone'   => $data['contact_phone'],
        'subtotal'        => $subtotal,
        'discount_amount' => $discount,
        'final_amount'    => $finalAmount,
        'payment_method'  => $data['payment_method'],
        'expires_at'      => now()->addMinutes(15), // 15 phút thanh toán
    ]);

    // 6. Tạo booking_passengers
    foreach ($data['passengers'] as $i => $p) {
        BookingPassenger::create([
            'booking_id'  => $booking->id,
            'seat_map_id' => $seats[$i]->id,
            'full_name'   => $p['full_name'],
            'phone'       => $p['phone'] ?? null,
            'is_primary'  => $i === 0,
        ]);
    }

    // 7. Cập nhật trạng thái ghế → 'booked'
    SeatMap::whereIn('id', $data['seat_ids'])->update([
        'status' => 'booked',
        'locked_by' => null,
    ]);

    // 8. Cập nhật available_seats trên trip
    Trip::where('id', $data['trip_id'])
        ->decrement('available_seats', $data['passenger_count']);

    // 9. Dispatch job sinh QR code (async)
    GenerateQrCodeJob::dispatch($booking);

    // 10. Dispatch job expire booking nếu chưa thanh toán sau 15 phút
    ExpireUnpaidBookingJob::dispatch($booking)->delay(now()->addMinutes(15));

    return $booking;
});
```

**Business Logic - Hủy vé:**
```
Điều kiện hoàn tiền:
- Hủy trước 24h xuất phát: hoàn 100%
- Hủy trước 4h xuất phát: hoàn 50%
- Hủy trong 4h xuất phát: không hoàn tiền
- Trip đã in_progress: không được hủy

Sau khi hủy:
- Cập nhật booking_status = 'cancelled'
- Giải phóng ghế (seat_map.status = 'available')
- Tăng available_seats trên trip
- Tạo payment refund record
- Hoàn tiền vào ví nội bộ (tức thì) hoặc chuyển khoản (3-5 ngày)
- Gửi notification hủy vé
```

**Booking code format:**
```
HNHP + YYMMDD + 3 số thứ tự
Ví dụ: HNHP240615001, HNHP240615002...
Chiều ngược: HPHN240615001
```

### 3.4 Thanh toán
**File:** `app/Http/Controllers/Customer/PaymentController.php`
**Service:** `app/Services/PaymentService.php`

**API Endpoints:**
```
POST /api/customer/payments/initiate      Khởi tạo thanh toán
     Body: { booking_id, method: momo|vnpay|zalopay|wallet }
     Response: { payment_url, order_id, qr_code_url }

POST /api/customer/payments/momo/callback     Webhook MoMo (public, không auth)
POST /api/customer/payments/vnpay/callback    Webhook VNPay
POST /api/customer/payments/zalopay/callback  Webhook ZaloPay

GET  /api/customer/payments/{booking_id}/status   Kiểm tra trạng thái

POST /api/customer/wallet/topup           Nạp tiền vào ví
GET  /api/customer/wallet                 Số dư + lịch sử giao dịch
```

**Business Logic - Webhook callback:**
```
1. Verify chữ ký HMAC từ payment gateway
2. Idempotency check: nếu payment đã success → bỏ qua (tránh xử lý 2 lần)
3. Cập nhật payment.status = 'success'
4. Cập nhật booking.payment_status = 'paid'
5. Cập nhật booking.booking_status = 'confirmed'
6. Sinh QR code vé điện tử
7. Dispatch NotifyBookingConfirmedJob (gửi SMS + Email + Zalo)
8. Trả về HTTP 200 (bắt buộc, nếu không gateway sẽ retry)
```

### 3.5 Theo dõi chuyến đi
**File:** `app/Http/Controllers/Customer/TrackingController.php`

**API Endpoints:**
```
GET  /api/customer/bookings/{id}/track    Thông tin tracking chuyến
GET  /api/customer/trips/{code}/track     Track bằng tracking_code (public)
```

**WebSocket Events (Laravel Reverb):**
```
Channel: trips.{trip_id}
Events:
- TripStarted         { started_at, driver_name, plate_number }
- DriverLocationUpdated { lat, lng, updated_at, eta_minutes }
- TripCompleted       { completed_at }
- TripDelayed         { delay_minutes, reason }
```

### 3.6 Đánh giá
**File:** `app/Http/Controllers/Customer/ReviewController.php`

```
POST /api/customer/reviews
     Body: { booking_id, driver_rating(1-5), vehicle_rating(1-5), 
             service_rating(1-5), comment }
     Điều kiện: booking_status = 'completed', chưa review, trong vòng 7 ngày
```

---

## 4. ACTOR 2: TÀI XẾ (Driver)

### 4.1 Authentication
**Guard:** `driver` | **File:** `app/Http/Controllers/Driver/AuthController.php`

```
POST /api/driver/auth/login               Body: { phone, password }
POST /api/driver/auth/logout
GET  /api/driver/auth/me
PUT  /api/driver/auth/profile             Body: { full_name, avatar, fcm_token }
POST /api/driver/auth/register            Đăng ký + upload documents
PUT  /api/driver/status                   Body: { is_online: bool }
```

**Đăng ký tài xế (documents required):**
```
- id_card_front: image (jpg/png, max 5MB)
- id_card_back: image
- license_front: image
- license_number: string
- license_class: B2|C|D|E
- license_expiry: date
- operator_id: uuid (thuộc nhà xe nào)
→ status = 'pending', chờ admin duyệt
```

### 4.2 Quản lý chuyến đi
**File:** `app/Http/Controllers/Driver/TripController.php`

```
GET  /api/driver/trips                    Danh sách chuyến (today + upcoming)
GET  /api/driver/trips/{id}               Chi tiết chuyến
GET  /api/driver/trips/{id}/passengers    Danh sách khách + điểm đón từng người
POST /api/driver/trips/{id}/start         Bắt đầu chuyến
POST /api/driver/trips/{id}/complete      Kết thúc chuyến
POST /api/driver/trips/{id}/incident      Báo sự cố
GET  /api/driver/trips/history            Lịch sử các chuyến đã chạy
```

**Response - Danh sách khách (manifest):**
```json
{
  "trip_id": "uuid",
  "depart_at": "2024-06-15 06:00:00",
  "route": "Hà Nội → Hải Phòng",
  "vehicle": { "plate": "30A-12345", "type": "mpv_7" },
  "passengers": [
    {
      "order": 1,
      "booking_code": "HNHP240615001",
      "contact_name": "Nguyễn Văn A",
      "contact_phone": "0901234567",
      "seat_code": "A1",
      "pickup_stop": "Mỹ Đình",
      "pickup_address": "20 Phạm Hùng",
      "dropoff_stop": "Trung tâm HP",
      "passenger_count": 2,
      "qr_token": "xxx",
      "checked_in": false
    }
  ]
}
```

### 4.3 Check-in hành khách
**File:** `app/Http/Controllers/Driver/CheckinController.php`

```
POST /api/driver/checkin
     Body: { qr_token: string }
     Logic:
       1. Verify qr_token hợp lệ và thuộc đúng chuyến tài xế đang chạy
       2. Kiểm tra booking_status = 'confirmed'
       3. Cập nhật booking_status = 'checked_in', checked_in_at = now()
       4. Broadcast event BookingCheckedIn lên WebSocket
       5. Gửi notification "Tài xế đã xác nhận lên xe" cho khách
```

### 4.4 GPS Tracking
**File:** `app/Http/Controllers/Driver/LocationController.php`

```
POST /api/driver/location
     Body: { lat: decimal, lng: decimal, accuracy: float }
     Rate limit: 1 request/10 giây
     Logic:
       1. Cập nhật drivers.current_lat, current_lng, location_updated_at
       2. Lưu Redis: driver_location:{driver_id} với TTL 30s
       3. Broadcast DriverLocationUpdated event
       4. Tính ETA đến điểm đón tiếp theo (Google Maps Distance Matrix API)
```

### 4.5 Thu nhập
**File:** `app/Http/Controllers/Driver/EarningController.php`

```
GET /api/driver/earnings                  Tổng thu nhập + thống kê
    Query: { period: today|week|month|custom, from_date, to_date }
GET /api/driver/earnings/transactions     Lịch sử giao dịch chi tiết
POST /api/driver/earnings/withdraw        Yêu cầu rút tiền
```

**Công thức tính thu nhập:**
```
Doanh thu chuyến = final_amount của booking
Hoa hồng nền tảng = doanh thu × operator.commission_rate / 100
Thu nhập tài xế = (doanh thu - hoa hồng) × driver_share_rate (do nhà xe cấu hình)
```

---

## 5. ACTOR 3: NHÀ XE (Operator)

### 5.1 Authentication
**Guard:** `operator` | **File:** `app/Http/Controllers/Operator/AuthController.php`

```
POST /api/operator/auth/register          Đăng ký nhà xe + upload giấy phép
POST /api/operator/auth/login
POST /api/operator/auth/logout
GET  /api/operator/auth/me
PUT  /api/operator/auth/profile
```

### 5.2 Quản lý tuyến đường
**File:** `app/Http/Controllers/Operator/RouteController.php`

```
GET    /api/operator/routes               Danh sách tuyến của nhà xe
POST   /api/operator/routes               Tạo tuyến mới
GET    /api/operator/routes/{id}          Chi tiết tuyến + stops
PUT    /api/operator/routes/{id}          Cập nhật tuyến
DELETE /api/operator/routes/{id}          Xóa (chỉ khi không có trip nào)

GET    /api/operator/routes/{id}/stops    Danh sách điểm dừng
POST   /api/operator/routes/{id}/stops    Thêm điểm dừng
PUT    /api/operator/routes/{id}/stops/{stop_id}
DELETE /api/operator/routes/{id}/stops/{stop_id}
```

**Validation tạo tuyến:**
```
- name: required, max 200
- base_price: required, integer, min 50000, max 500000
- Phải có ít nhất 2 route_stops (điểm đi và điểm đến)
- stop_order phải unique trong route
- lat/lng hợp lệ (Việt Nam: lat 8-24, lng 102-110)
```

### 5.3 Quản lý xe
**File:** `app/Http/Controllers/Operator/VehicleController.php`

```
GET    /api/operator/vehicles
POST   /api/operator/vehicles
PUT    /api/operator/vehicles/{id}
DELETE /api/operator/vehicles/{id}         Chỉ khi không có trip scheduled

POST   /api/operator/vehicles/{id}/seat-map     Cấu hình sơ đồ ghế
GET    /api/operator/vehicles/{id}/seat-map     Xem sơ đồ ghế
```

**Sơ đồ ghế theo loại xe:**
```
mpv_7:    A1,A2 | B1,B2 | C1,C2 | Driver
van_9:    A1,A2 | B1,B2 | C1,C2 | D1,D2 | Driver  
minibus_16: A1-A4 | B1-B4 | C1-C4 | D1-D4
```

### 5.4 Quản lý tài xế
**File:** `app/Http/Controllers/Operator/DriverController.php`

```
GET    /api/operator/drivers              Danh sách tài xế thuộc nhà xe
POST   /api/operator/drivers/invite       Mời tài xế (gửi link qua SMS)
GET    /api/operator/drivers/{id}         Hồ sơ + lịch sử chuyến
DELETE /api/operator/drivers/{id}         Xóa khỏi nhà xe (không xóa tài khoản)
```

### 5.5 Quản lý lịch chạy (Trips)
**File:** `app/Http/Controllers/Operator/TripController.php`

```
GET    /api/operator/trips                Danh sách chuyến (filter theo ngày, tuyến, xe)
POST   /api/operator/trips               Tạo chuyến
PUT    /api/operator/trips/{id}           Sửa chuyến (chỉ khi chưa có booking)
DELETE /api/operator/trips/{id}           Hủy chuyến

GET    /api/operator/trips/{id}/manifest  Danh sách hành khách PDF/JSON
POST   /api/operator/trips/bulk-create    Tạo nhiều chuyến cùng lúc (lặp lịch)
```

**Bulk create trips (tạo lịch cả tuần):**
```json
POST /api/operator/trips/bulk-create
{
  "route_id": "uuid",
  "vehicle_id": "uuid",
  "driver_id": "uuid",
  "depart_times": ["06:00", "08:00", "10:00", "14:00", "16:00", "18:00"],
  "from_date": "2024-06-15",
  "to_date": "2024-06-30",
  "exclude_days": [0]  // 0=Sunday
}
```

### 5.6 Quản lý đặt chỗ
**File:** `app/Http/Controllers/Operator/BookingController.php`

```
GET  /api/operator/bookings               Tất cả booking theo nhà xe
GET  /api/operator/bookings/{id}          Chi tiết
POST /api/operator/bookings/{id}/cancel   Hủy chuyến (có lý do, hoàn tiền tự động)
GET  /api/operator/bookings/export        Xuất Excel danh sách booking
```

### 5.7 Doanh thu & Báo cáo
**File:** `app/Http/Controllers/Operator/RevenueController.php`

```
GET /api/operator/revenue/summary         Tổng quan doanh thu
    Query: { period: today|week|month|custom }
    Response:
    {
      total_revenue: 15000000,
      total_bookings: 125,
      total_passengers: 198,
      avg_seat_fill_rate: 78.5,
      top_route: "Hà Nội → Hải Phòng",
      commission_paid: 1500000,
      net_revenue: 13500000
    }

GET /api/operator/revenue/trips           Doanh thu theo từng chuyến
GET /api/operator/revenue/drivers         Doanh thu theo tài xế
POST /api/operator/revenue/payout-request Yêu cầu quyết toán
```

---

## 6. ACTOR 4: ADMIN (Platform)

### 6.1 Authentication
**Guard:** `admin` | **File:** `app/Http/Controllers/Admin/AuthController.php`

```
POST /api/admin/auth/login                Body: { email, password } (admin dùng email)
POST /api/admin/auth/logout
GET  /api/admin/auth/me
```

### 6.2 Dashboard KPI
**File:** `app/Http/Controllers/Admin/DashboardController.php`

```
GET /api/admin/dashboard
Response:
{
  "today": {
    "new_bookings": 45,
    "revenue": 5400000,
    "active_trips": 8,
    "new_users": 12
  },
  "mtd": { ... },  // Month-to-date
  "ytd": { ... },  // Year-to-date
  "live": {
    "trips_in_progress": 3,
    "drivers_online": 7,
    "pending_operators": 2,
    "pending_drivers": 5
  }
}

GET /api/admin/dashboard/map              Vị trí tất cả xe đang chạy (GPS)
```

### 6.3 Quản lý nhà xe
**File:** `app/Http/Controllers/Admin/OperatorController.php`

```
GET    /api/admin/operators               Danh sách + filter status
GET    /api/admin/operators/{id}          Chi tiết + documents
POST   /api/admin/operators/{id}/verify   Duyệt nhà xe
POST   /api/admin/operators/{id}/suspend  Tạm đình chỉ + lý do
PUT    /api/admin/operators/{id}/commission  Điều chỉnh hoa hồng
GET    /api/admin/operators/{id}/revenue  Doanh thu nhà xe
POST   /api/admin/operators/{id}/payout   Thực hiện quyết toán
```

### 6.4 Quản lý tài xế
**File:** `app/Http/Controllers/Admin/DriverController.php`

```
GET    /api/admin/drivers                 Danh sách + filter
GET    /api/admin/drivers/{id}            Hồ sơ + GPLX + lịch sử
POST   /api/admin/drivers/{id}/verify     Duyệt tài xế
POST   /api/admin/drivers/{id}/suspend    Đình chỉ
```

**Logic duyệt tài xế:**
```
1. Admin xem ảnh GPLX, CMND
2. Kiểm tra license_expiry > today + 6 tháng
3. Kiểm tra license_class phù hợp xe của nhà xe
4. Click "Duyệt" → driver.status = 'verified'
5. Gửi SMS thông báo tài xế được duyệt
```

### 6.5 Quản lý người dùng
**File:** `app/Http/Controllers/Admin/UserController.php`

```
GET    /api/admin/users                   Filter: role, status, search
GET    /api/admin/users/{id}              Profile + booking history
POST   /api/admin/users/{id}/ban          Khóa tài khoản
POST   /api/admin/users/{id}/unban        Mở khóa
GET    /api/admin/users/{id}/bookings     Lịch sử đặt vé của user
```

### 6.6 Quản lý tài chính
**File:** `app/Http/Controllers/Admin/FinanceController.php`

```
GET  /api/admin/finance/overview          Tổng quan doanh thu nền tảng
GET  /api/admin/finance/transactions      Tất cả giao dịch
GET  /api/admin/finance/commissions       Hoa hồng thu được theo nhà xe
GET  /api/admin/finance/payouts           Lịch sử quyết toán
POST /api/admin/finance/refund/{booking}  Hoàn tiền thủ công
GET  /api/admin/finance/export            Xuất báo cáo Excel
```

### 6.7 Quản lý chuyến đi
**File:** `app/Http/Controllers/Admin/TripController.php`

```
GET  /api/admin/trips                     Tất cả chuyến (filter date, status, operator)
GET  /api/admin/trips/{id}                Chi tiết + booking list
POST /api/admin/trips/{id}/cancel         Admin hủy chuyến (force cancel, hoàn tiền all)
GET  /api/admin/trips/live                Chuyến đang chạy + vị trí GPS
```

### 6.8 Voucher & Marketing
**File:** `app/Http/Controllers/Admin/VoucherController.php`

```
GET    /api/admin/vouchers
POST   /api/admin/vouchers
       Body: { code, discount_type, discount_value, min_order, max_discount,
               usage_limit, valid_from, valid_until, operator_id? }
PUT    /api/admin/vouchers/{id}
DELETE /api/admin/vouchers/{id}
GET    /api/admin/vouchers/{id}/usages    Ai đã dùng voucher này
```

### 6.9 Xử lý khiếu nại
**File:** `app/Http/Controllers/Admin/ComplaintController.php`

```
GET  /api/admin/complaints                Danh sách khiếu nại từ review thấp
GET  /api/admin/complaints/{id}
POST /api/admin/complaints/{id}/resolve   Giải quyết + ghi chú
POST /api/admin/complaints/{id}/warn-driver   Cảnh cáo tài xế
```

---

## 7. CÁC FILE CẦN SINH CODE

### 7.1 Migrations (theo thứ tự)
```
database/migrations/
├── 2024_01_01_000001_create_users_table.php
├── 2024_01_01_000002_create_operators_table.php
├── 2024_01_01_000003_create_drivers_table.php
├── 2024_01_01_000004_create_vehicles_table.php
├── 2024_01_01_000005_create_routes_table.php
├── 2024_01_01_000006_create_route_stops_table.php
├── 2024_01_01_000007_create_trips_table.php
├── 2024_01_01_000008_create_seat_maps_table.php
├── 2024_01_01_000009_create_bookings_table.php
├── 2024_01_01_000010_create_booking_passengers_table.php
├── 2024_01_01_000011_create_payments_table.php
├── 2024_01_01_000012_create_vouchers_table.php
├── 2024_01_01_000013_create_voucher_usages_table.php
├── 2024_01_01_000014_create_reviews_table.php
├── 2024_01_01_000015_create_wallets_table.php
├── 2024_01_01_000016_create_wallet_transactions_table.php
└── 2024_01_01_000017_create_notifications_table.php
```

### 7.2 Models
```
app/Models/
├── User.php              (role, guards, relationships)
├── Operator.php
├── Driver.php            (location, rating methods)
├── Vehicle.php           (seatMap relationship)
├── Route.php
├── RouteStop.php
├── Trip.php              (scopes: available, today, upcoming)
├── SeatMap.php           (scope: available)
├── Booking.php           (generateCode, canCancel, refundAmount)
├── BookingPassenger.php
├── Payment.php
├── Voucher.php           (isValid, calculateDiscount)
├── Review.php
├── Wallet.php            (credit, debit methods)
├── WalletTransaction.php
└── Notification.php
```

### 7.3 Services
```
app/Services/
├── BookingService.php       (create, cancel, expire)
├── TripService.php          (search, start, complete)
├── PaymentService.php       (initiate, handleCallback, refund)
├── NotificationService.php  (sendSms, sendZalo, sendEmail, sendPush)
├── VoucherService.php       (validate, calculate, use)
├── WalletService.php        (credit, debit, withdraw)
├── QrCodeService.php        (generate, verify)
├── TrackingService.php      (updateLocation, getEta)
└── OtpService.php           (send, verify)
```

### 7.4 Jobs (Queue)
```
app/Jobs/
├── SendSmsNotificationJob.php
├── SendZaloNotificationJob.php
├── SendEmailNotificationJob.php
├── GenerateQrCodeJob.php
├── ExpireUnpaidBookingJob.php
├── ExpireLockedSeatsJob.php
├── ProcessRefundJob.php
├── UpdateDriverRatingJob.php
└── GenerateTripManifestJob.php
```

### 7.5 Events & Listeners
```
app/Events/
├── BookingConfirmed.php
├── BookingCancelled.php
├── TripStarted.php
├── TripCompleted.php
├── DriverLocationUpdated.php
└── PaymentProcessed.php

app/Listeners/
├── SendBookingConfirmationNotification.php
├── SendBookingCancellationNotification.php
├── NotifyPassengersOnTripStart.php
├── BroadcastDriverLocation.php
└── UpdateDriverRatingOnTripComplete.php
```

### 7.6 API Resources
```
app/Http/Resources/
├── Customer/
│   ├── TripResource.php
│   ├── TripSearchResource.php
│   ├── BookingResource.php
│   ├── BookingListResource.php
│   └── WalletResource.php
├── Driver/
│   ├── TripResource.php
│   ├── PassengerResource.php
│   └── EarningResource.php
├── Operator/
│   ├── TripResource.php
│   ├── BookingResource.php
│   └── RevenueResource.php
└── Admin/
    ├── UserResource.php
    ├── OperatorResource.php
    ├── DriverResource.php
    └── FinanceResource.php
```

---

## 8. CẤU HÌNH AUTH (4 GUARDS)

```php
// config/auth.php
'guards' => [
    'customer' => ['driver' => 'sanctum', 'provider' => 'customers'],
    'driver'   => ['driver' => 'sanctum', 'provider' => 'drivers'],
    'operator' => ['driver' => 'sanctum', 'provider' => 'operators'],
    'admin'    => ['driver' => 'sanctum', 'provider' => 'admins'],
],
'providers' => [
    'customers' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'drivers'   => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'operators' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'admins'    => ['driver' => 'eloquent', 'model' => App\Models\User::class],
],
```

---

## 9. ROUTES FILE

```php
// routes/api_customer.php  → prefix: /api/customer, middleware: auth:customer
// routes/api_driver.php    → prefix: /api/driver,   middleware: auth:driver
// routes/api_operator.php  → prefix: /api/operator, middleware: auth:operator
// routes/api_admin.php     → prefix: /api/admin,    middleware: auth:admin
// routes/api_public.php    → prefix: /api/public    (không cần auth)
```

**Public routes (không cần đăng nhập):**
```
GET  /api/public/trips/search             Tìm kiếm chuyến
GET  /api/public/trips/{tracking_code}/track  Theo dõi xe public
POST /api/public/payments/*/callback      Webhook từ payment gateway
```

---

## 10. BUSINESS RULES ĐẶC THÙ

### 10.1 Giới hạn đặt vé
```
- Tối đa 4 ghế/lần đặt
- Phải thanh toán trong 15 phút (sau đó booking tự hủy, ghế giải phóng)
- Không được đặt vé cho chuyến xuất phát trong vòng 30 phút
- Mỗi user tối đa 3 booking pending cùng lúc
```

### 10.2 Chính sách hủy vé
```
- Trước 24h: hoàn 100% (trừ phí xử lý 5.000đ)
- Trước 4h:  hoàn 50%
- Trong 4h:  hoàn 0%
- Nhà xe hủy: hoàn 100% + voucher bồi thường 20.000đ
```

### 10.3 Hoa hồng nền tảng
```
- Mặc định: 10% doanh thu mỗi booking
- Có thể điều chỉnh riêng từng nhà xe (admin cấu hình)
- Quyết toán: 2 lần/tháng (ngày 1 và ngày 15)
```

### 10.4 Tài xế online
```
- Tài xế bật is_online = true → nhận thông báo chuyến mới
- Location update mỗi 15 giây khi đang chạy chuyến
- Sau 60 giây không update → coi như offline (Redis TTL)
```

### 10.5 Voucher
```
- 1 user chỉ dùng 1 voucher/lần đặt
- Không áp voucher cho booking dưới min_order
- Voucher nhà xe chỉ áp cho chuyến của nhà xe đó
```

---

## 11. SEEDER DATA (dữ liệu mẫu)

```php
// Admin mặc định
Email: admin@xeghep.vn
Password: Admin@123456

// Nhà xe mẫu
Tên: Xe Ghép Bắc Hà
Phone: 0912345678
Tuyến: Hà Nội ↔ Hải Phòng
Giá: 150.000đ/người

// Tài xế mẫu
Tên: Nguyễn Văn Tài
Phone: 0923456789
GPLX: B2, hết hạn 2028-12-31

// Chuyến mẫu (tạo 10 chuyến ngày hôm nay)
Giờ xuất phát: 06:00, 07:00, 08:00, 09:00, 10:00, 12:00, 14:00, 16:00, 18:00, 20:00

// Voucher mẫu
Code: WELCOME50 → giảm 50.000đ, đơn tối thiểu 100.000đ
Code: HNHP10   → giảm 10%, tối đa 30.000đ
```

---

## 12. LỆnh CHẠY SAU KHI AI AGENT SINH CODE

```bash
# 1. Chạy migrations
php artisan migrate

# 2. Chạy seeders
php artisan db:seed

# 3. Tạo storage link
php artisan storage:link

# 4. Cache config
php artisan config:cache
php artisan route:cache

# 5. Khởi động queue worker
php artisan queue:work redis --queue=notifications,payments,default

# 6. Khởi động WebSocket server
php artisan reverb:start

# 7. Tạo admin account
php artisan tinker
>>> User::create(['full_name'=>'Admin','email'=>'admin@xeghep.vn','phone'=>'0900000000','password'=>bcrypt('Admin@123456'),'role'=>'admin','is_verified'=>true])
```

---

*Document này đủ chi tiết để AI Agent sinh toàn bộ code Laravel mà không cần hỏi thêm.*  
*Tổng số file cần sinh: ~80 file PHP*  
*Ưu tiên sinh theo thứ tự: Migrations → Models → Services → Controllers → Resources → Jobs*