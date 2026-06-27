# PROJECT CONTEXT
# Xe Ghép Tuyến Hà Nội – Hải Phòng
> Tài liệu này cung cấp toàn bộ ngữ cảnh dự án cho AI Agent.
> Đọc toàn bộ file này TRƯỚC KHI thực hiện bất kỳ task nào.

---

## 1. ĐỊNH DANH DỰ ÁN

```
Tên dự án    : XeGhep.vn
Loại         : Web platform + Mobile API
Phiên bản    : 1.0.0 (MVP)
Ngôn ngữ UI  : Tiếng Việt (vi_VN)
Tiền tệ      : VND (đồng Việt Nam), không có số thập phân
Múi giờ      : Asia/Ho_Chi_Minh (UTC+7)
Locale       : vi_VN
```

---

## 2. VẤN ĐỀ ĐANG GIẢI QUYẾT

Hiện tại hành khách tuyến Hà Nội – Hải Phòng phải:
1. Tự tìm số điện thoại nhà xe trên mạng hoặc hỏi người quen
2. Gọi điện đặt chỗ thủ công, không biết còn chỗ không
3. Ra bến xe hoặc điểm cố định để lên xe
4. Không biết xe đến lúc nào, phải đứng đợi
5. Thanh toán tiền mặt, không có hóa đơn

**XeGhep.vn giải quyết**: đặt vé online, đón tận nơi, theo dõi xe real-time, thanh toán điện tử.

---

## 3. NGƯỜI DÙNG MỤC TIÊU

### Customer (Khách hàng)
- **Chân dung**: Nhân viên văn phòng, sinh viên, người đi làm xa 25–45 tuổi
- **Nỗi đau**: Mất thời gian ra bến xe, không biết xe còn chỗ không
- **Kỳ vọng**: Đặt vé nhanh < 2 phút, được đón tận nhà, biết xe đến bao giờ

### Driver (Tài xế)
- **Chân dung**: Tài xế 28–50 tuổi, chạy xe ghép là nghề chính hoặc tay trái
- **Nỗi đau**: Phải gọi điện confirm từng khách, quản lý danh sách giấy tờ
- **Kỳ vọng**: App đơn giản, xem được danh sách khách và điểm đón, không bỏ sót ai

### Operator (Nhà xe)
- **Chân dung**: Chủ nhà xe nhỏ 3–10 xe, quen quản lý thủ công hoặc Excel
- **Nỗi đau**: Khó quản lý lịch chạy nhiều xe, doanh thu không minh bạch
- **Kỳ vọng**: Dashboard đơn giản như Excel, biết chuyến nào đầy/vắng, rút tiền dễ

### Admin (Quản trị nền tảng)
- **Chân dung**: Nhóm vận hành nội bộ 2–5 người
- **Kỳ vọng**: Kiểm soát được toàn bộ hoạt động, phát hiện gian lận, xử lý khiếu nại

---

## 4. PHẠM VI MVP (Phase 1)

### ✅ IN SCOPE – Bắt buộc phải có
- Tuyến duy nhất: Hà Nội ↔ Hải Phòng
- Đặt vé + chọn ghế + chọn điểm đón/trả
- Thanh toán: MoMo, VNPay, tiền mặt
- Vé QR điện tử
- Theo dõi xe real-time (GPS driver → map khách)
- Thông báo: SMS + Zalo OA
- App tài xế: xem lịch + check-in QR + gửi GPS
- Portal nhà xe: quản lý tuyến + xe + tài xế + doanh thu
- Admin: duyệt hồ sơ + dashboard KPI + xử lý hoàn tiền

### ❌ OUT OF SCOPE – Phase 2 trở đi
- Nhiều tuyến đường khác
- ZaloPay (thêm sau)
- AI ghép lộ trình tối ưu (hiện tại dùng điểm cố định)
- App native iOS/Android riêng (hiện tại dùng PWA)
- Đặt lịch cố định hàng tuần
- Chương trình loyalty/điểm thưởng

---

## 5. DOMAIN KNOWLEDGE ĐẶC THÙ

### 5.1 Cách hoạt động của xe ghép
```
1. Nhà xe tạo chuyến (trip) với giờ xuất phát và xe cụ thể
2. Khách đặt vé → chọn điểm đón từ danh sách điểm cố định trên tuyến
3. Hệ thống ghi nhận điểm đón/trả, tạo manifest cho tài xế
4. Tài xế đến từng điểm đón theo thứ tự, quét QR check-in
5. Hoàn tất chuyến, khách đánh giá
```

### 5.2 Điểm đón/trả
- **Không phải door-to-door thực sự** (MVP): khách chọn từ ~10 điểm cố định trên tuyến
- **Có thể nhập địa chỉ thêm** vào field `pickup_address` để tài xế biết vị trí cụ thể hơn
- **Phase 2**: tích hợp Google Maps để đón tận nhà thực sự

### 5.3 Quy tắc nghiệp vụ quan trọng nhất
```
RACE CONDITION: Khi 2 người cùng đặt ghế A1 cùng lúc
→ PHẢI dùng DB::transaction() + lockForUpdate()
→ Người thua sẽ nhận lỗi "Ghế đã được đặt"

BOOKING EXPIRY: Booking chưa thanh toán sau 15 phút
→ Tự động hủy, ghế được giải phóng
→ Implement bằng: ExpireUnpaidBookingJob + Redis TTL

SEAT LOCK: Khi khách vào trang chọn ghế
→ Lock ghế 10 phút (cho user đang xem)
→ Nếu không thanh toán → tự động unlock
```

### 5.4 Booking Code Format
```
[ORIGIN_CODE][DEST_CODE][YYMMDD][SEQUENCE]
Hà Nội → Hải Phòng: HNHP240615001
Hải Phòng → Hà Nội: HPHN240615001
Sequence reset về 001 mỗi ngày mới
```

### 5.5 Trạng thái chuyến đi (Trip Status Flow)
```
scheduled → boarding → in_progress → completed
                     ↓
                  cancelled
```

### 5.6 Trạng thái đặt vé (Booking Status Flow)
```
pending → confirmed → checked_in → completed
        ↓
     cancelled (từ khách hoặc nhà xe)
confirmed → no_show (nếu tài xế không check-in được)
```

---

## 6. CONSTRAINTS & ASSUMPTIONS

### Technical Constraints
```
- PHP >= 8.3
- Laravel >= 11.0
- MySQL >= 8.0 (không dùng PostgreSQL)
- Redis >= 7.0 (queue + cache + session)
- Server: Ubuntu 22.04 LTS
- Không dùng Docker trong production (deploy truyền thống)
- File upload: tối đa 10MB mỗi file
- API response time: < 500ms cho 95% requests
```

### Business Constraints
```
- Tiếng Việt là ngôn ngữ duy nhất (không cần i18n phức tạp)
- Không có thanh toán ngoại tệ
- Dữ liệu khách hàng phải lưu tại Việt Nam (PDPA compliance)
- Tài xế phải xác minh GPLX thật trước khi nhận chuyến
```

### Assumptions
```
- Mỗi driver chỉ thuộc 1 operator
- 1 vehicle chỉ có 1 trip active cùng lúc
- Giá vé tính bằng số nguyên (đồng), không có xu
- Múi giờ server và database đều là UTC+7
- SMS provider chính: ESMS.vn (backup: Twilio)
- Zalo OA đã được phê duyệt và có access token
```

---

## 7. INTEGRATION MAP

```
Laravel App ──→ MySQL          (primary database)
            ──→ Redis          (cache, queue, session, OTP, seat lock)
            ──→ Laravel Reverb (WebSocket, real-time GPS & booking updates)
            ──→ ESMS.vn API    (SMS OTP + thông báo)
            ──→ Zalo OA API    (Zalo notification)
            ──→ MoMo API       (payment gateway)
            ──→ VNPay API      (payment gateway)
            ──→ Google Maps API (geocoding, distance matrix cho ETA)
            ──→ Firebase FCM   (push notification mobile)
            ──→ AWS S3 / Local (file storage: ảnh GPLX, avatar, QR)
```

---

## 8. CODING CONVENTIONS

```php
// Tên biến: camelCase
$bookingCode, $tripId, $finalAmount

// Tên method: camelCase, bắt đầu bằng động từ
createBooking(), cancelBooking(), calculateRefund()

// Tên class: PascalCase
BookingService, TripSearchController

// Tên bảng DB: snake_case, số nhiều
bookings, route_stops, wallet_transactions

// Tên cột DB: snake_case
created_at, booking_status, final_amount

// Constants: UPPER_SNAKE_CASE
const BOOKING_EXPIRE_MINUTES = 15;
const MAX_SEATS_PER_BOOKING = 4;

// Enum values: snake_case
'in_progress', 'no_show', 'partial_refund'

// API response format chuẩn:
{
  "success": true,
  "message": "Đặt vé thành công",
  "data": { ... },
  "meta": { "pagination": { ... } }  // nếu có
}

// Error response:
{
  "success": false,
  "message": "Ghế A1 đã được đặt bởi người khác",
  "errors": { "seat_id": ["Ghế không còn trống"] },
  "code": "SEAT_NOT_AVAILABLE"
}
```

---

## 9. FILE STRUCTURE (đã thống nhất)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Customer/    ← guard: customer
│   │   ├── Driver/      ← guard: driver
│   │   ├── Operator/    ← guard: operator
│   │   └── Admin/       ← guard: admin
│   ├── Middleware/
│   ├── Requests/
│   │   ├── Customer/
│   │   ├── Driver/
│   │   ├── Operator/
│   │   └── Admin/
│   └── Resources/
│       ├── Customer/
│       ├── Driver/
│       ├── Operator/
│       └── Admin/
├── Models/
├── Services/
├── Repositories/
│   └── Contracts/
├── Events/
├── Listeners/
├── Jobs/
├── Exceptions/
│   ├── SeatNotAvailableException.php
│   ├── BookingExpiredException.php
│   └── InsufficientBalanceException.php
└── Providers/
    ├── AppServiceProvider.php
    ├── AuthServiceProvider.php
    └── RepositoryServiceProvider.php

routes/
├── api_customer.php
├── api_driver.php
├── api_operator.php
├── api_admin.php
├── api_public.php
└── channels.php

resources/js/
├── entries/
│   ├── customer.js
│   ├── driver.js
│   ├── operator.js
│   └── admin.js
├── pages/
│   ├── customer/
│   ├── driver/
│   ├── operator/
│   └── admin/
└── components/
```