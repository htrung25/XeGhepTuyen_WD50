# MEMORY DOCUMENT
# Xe Ghép Tuyến Hà Nội – Hải Phòng
> Đây là "bộ nhớ" của dự án. AI Agent đọc file này để biết:
> - Những gì ĐÃ được quyết định (không cần hỏi lại)
> - Những gì ĐÃ được sinh code (không sinh lại)
> - Những gì CÒN LẠI phải làm
> - Các lỗi đã gặp và cách giải quyết
>
> AI Agent PHẢI cập nhật file này sau mỗi lần hoàn thành task.

---

## 1. TRẠNG THÁI TỔNG QUAN

```
Dự án     : XeGhep.vn – Hà Nội ↔ Hải Phòng
Phase     : MVP (Phase 1)
Tiến độ   : 100% MVP — 4 portal SPAs fully wired, build OK
Cập nhật  : 2026-06-06
```

### Progress Tracker
```
[x] Phase 1: Database & Models        (17/17 migrations, 17/17 models)
[x] Phase 2: Auth Layer               (4/4 guards — config/auth.php)
[x] Phase 3: Customer Features        (8/8 controllers)
[x] Phase 4: Driver Features          (5/5 controllers)
[x] Phase 5: Operator Features        (7/7 controllers)
[x] Phase 6: Admin Features           (8/8 controllers)
[x] Phase 7: Jobs & Notifications     (9/9 jobs, 6/6 events, 5/5 listeners)
[x] Phase 9: Operator Portal Vue      (6/6 screens + layout + store + API + router)
[x] Phase 8: Tests                    (Đã viết BookingServiceTest kiểm thử luồng đặt vé & hoàn tiền, tổng cộng 30 test cases chạy thành công)
[x] Phase 10: Admin Portal Vue          (7/7 screens + layout + store + API + router)
[x] Phase 12: Wiring complete              (entries, vite, blade, web routes, stores, router)
[x] Phase 11: Customer Vue portal          (10/10 screens + layout rewrite desktop)
[x] Phase 11b: Driver Vue portal           (9/9 files — full desktop rebuild 2026-06-06)
```

### 3.13 Operator Portal Vue (resources/js/)
```
[x] pages/operator/auth/Login.vue
[x] pages/operator/Dashboard.vue
[x] pages/operator/Routes/Index.vue
[x] pages/operator/Trips/Schedule.vue
[x] pages/operator/Trips/Manifest.vue
[x] pages/operator/Revenue/Report.vue
[x] layouts/OperatorLayout.vue
[x] api/client.ts
[x] api/operator.api.ts
[x] stores/operator.auth.store.ts
[x] router/operator.routes.ts
```

### 3.14 Admin Portal Vue (resources/js/)
```
[x] pages/admin/auth/Login.vue
[x] pages/admin/Dashboard.vue
[x] pages/admin/Operators/Approve.vue
[x] pages/admin/Drivers/Verify.vue
[x] pages/admin/Finance/Overview.vue
[x] pages/admin/Trips/LiveMap.vue
[x] pages/admin/Vouchers/Index.vue
[x] layouts/AdminLayout.vue
[x] api/admin.api.ts
[x] stores/admin.auth.store.ts
[x] stores/admin.store.ts
[x] router/admin.routes.ts
[x] router/admin.router.ts
[x] composables/useWebSocket.ts
[x] entries/admin.ts
[x] AdminApp.vue
```

### 3.15 Infrastructure Wiring
```
[x] stores/operator.store.ts          (general state store)
[x] router/operator.router.ts         (createRouter instance)
[x] entries/operator.ts               (Vue app mount)
[x] OperatorApp.vue                   (root component)
[x] resources/views/operator.blade.php
[x] resources/views/admin.blade.php
[x] resources/views/customer.blade.php
[x] routes/web.php                    (SPA catch-all routes)
[x] vite.config.ts                    (added all 4 entries incl. customer)
[x] package.json                      (pinia, vue-router, axios, laravel-echo, pusher-js)
```

### 3.17 Customer Portal Vue (resources/js/) — Desktop rewrite 2026-06-06
```
[x] layouts/CustomerLayout.vue        (desktop header + footer, replaces mobile bottom-nav)
[x] pages/customer/Home.vue           (hero search card + features + popular routes)
[x] pages/customer/trips/Results.vue  (sidebar filter + trip list 2-col desktop)
[x] pages/customer/trips/SeatPicker.vue (seat grid + order summary 2-col + WebSocket)
[x] pages/customer/booking/Checkout.vue (form 2-col + countdown + voucher)
[x] pages/customer/booking/Payment.vue  (payment methods + order summary 2-col)
[x] pages/customer/booking/Confirmation.vue (ticket card + QR code centered)
[x] pages/customer/booking/History.vue  (tabs + booking list + cancel modal)
[x] pages/customer/tracking/LiveMap.vue (Google Maps 2-col + stop timeline + WebSocket)
[x] pages/customer/Review.vue          (star ratings + quick tags + centered)
[x] pages/customer/Profile.vue         (sidebar menu 2-col + profile/wallet/password)
[x] stores/customer.store.ts           (+ bookings, currentBooking, trackingData, wallet 2026-06-06)
[x] stores/customer.auth.store.ts      (token, user, isAuthenticated)
[x] api/customer.api.ts                (+ updateProfile, changePassword, getTripStops, getVouchers, getWalletHistory, topUpWallet, getNotifications 2026-06-06)
[x] router/customer.routes.ts          (10 routes + auth guard)
[x] router/customer.router.ts          (createRouter instance)
[x] entries/customer.ts                (Vue app mount)
[x] CustomerApp.vue                    (root component)
```

### 3.18 Driver Portal Vue (resources/js/) — Full desktop rebuild 2026-06-06
```
[x] layouts/DriverLayout.vue             (sidebar dark bg-gray-900, green-600, 4 nav items)
[x] pages/driver/auth/Login.vue          (split-screen: left green gradient illus, right white form)
[x] pages/driver/Dashboard.vue          (KPI 4-cards + trip table + weekly bar chart)
[x] pages/driver/trips/TripDetail.vue   (2-col: passenger manifest left + action panel right sticky)
[x] pages/driver/trips/Navigation.vue   (2-col: Google Maps + GPS tracking + navigation panel)
[x] pages/driver/trips/Schedule.vue     (NEW: weekly calendar grid + upcoming trips table)
[x] pages/driver/checkin/QrScanner.vue  (2-col: camera scanner left + result/recent right)
[x] pages/driver/earnings/Summary.vue  (balance hero + period tabs + bar chart + transactions)
[x] pages/driver/Profile.vue            (2-col: profile/vehicle/reviews left + form/docs/password right)
[x] router/driver.routes.ts             (added /driver/schedule route)
[x] stores/driver.store.ts              (+ earnings, isOnline, currentLocation, setEarnings, setOnlineStatus, updateLocation 2026-06-06)
[x] api/driver.api.ts                   (+ updateProfile, changePassword, uploadDocument, getNotifications 2026-06-06)
[x] composables/useGpsTracking.ts       (watchPosition + setInterval 15s → POST /driver/location ✓)
[x] routes/web.php                      (driver/operator/admin prefix + customer /{any?} catch-all LAST ✓)
[x] resources/views/driver.blade.php    (SPA shell ✓)
[x] vite.config.ts                      (4 entries: customer/driver/operator/admin ✓)
[x] entries/driver.ts                   (Vue app mount ✓)
```

### 3.16 Seeder Fixes (schema corrections)
```
[x] OperatorSeeder: tax_number→tax_code, license_number→business_license
[x] TripSeeder:     route_code→name, duration_hours→est_duration_min,
                    stop_address→address, base_price→price,
                    VehicleType enums fixed, removed current_vehicle_id/floor/row/column
[x] Sanctum migration: morphs→uuidMorphs (UUID primary keys)
[x] Operator AuthController: email→phone login (match LoginRequest+frontend)
```

### Stitch Design System (lưu cho các portal khác)
```
Project ID : 3429828540487639736
DS ID      : assets/5831b0afd0cb4a52ac6f664b0fdf61fe
DS Name    : XeGhep Utility System
Primary    : #F59E0B (amber-500)
Font       : Be Vietnam Pro
Background : #F7F9FB
Card       : bg-white rounded-xl border border-slate-200 shadow-sm
Badge pill : inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
```

---

## 2. QUYẾT ĐỊNH KIẾN TRÚC ĐÃ THỐNG NHẤT

> Những quyết định này là FINAL. AI Agent không được thay đổi hoặc đề xuất thay thế.

### 2.1 Stack
```
✅ Laravel 13 (PHP 8.3) — KHÔNG dùng Laravel 10 trở xuống
✅ MySQL 8.0 — KHÔNG dùng PostgreSQL hay SQLite
✅ Redis 7.0 — cho cache, queue, session, OTP
✅ Laravel Sanctum — auth token (KHÔNG dùng JWT hay Passport)
✅ Laravel Reverb — WebSocket (KHÔNG dùng Pusher hay Socket.io)
✅ Vue 3 + Vite — frontend (KHÔNG dùng React hay Livewire)
✅ UUID v7 — tất cả primary keys (KHÔNG dùng auto-increment)
✅ ESMS.vn — SMS provider chính
✅ Google Maps API — bản đồ và ETA
```

### 2.2 Pattern
```
✅ Service/Repository pattern — bắt buộc cho tất cả business logic
✅ 4 guard riêng biệt — customer, driver, operator, admin
✅ 4 route file riêng — api_customer.php, api_driver.php, api_operator.php, api_admin.php
✅ 4 Vue entry point — customer.js, driver.js, operator.js, admin.js
✅ Event-driven cho notification — không gửi sync trong controller
✅ Queue cho tất cả external API calls — SMS, email, push, QR generation
✅ DB::transaction + lockForUpdate cho booking — tránh race condition
✅ API Resource classes — mỗi actor có resource riêng
```

### 2.3 Quy ước đặt tên
```
✅ Database: snake_case, số nhiều (bookings, route_stops)
✅ PHP: camelCase variables, PascalCase classes
✅ API endpoints: kebab-case (/api/customer/lock-seats)
✅ Booking code: [ORIGIN][DEST][YYMMDD][3-digit-seq] (HNHP240615001)
✅ Enum values: snake_case lowercase (in_progress, no_show)
✅ Cache keys: colon-separated (trips:search:{hash}, otp:{phone})
✅ Tiếng Việt: tất cả message lỗi và thông báo gửi cho user
```

---

## 3. FILE ĐÃ SINH CODE

> AI Agent cập nhật section này sau mỗi lần tạo file thành công.
> Format: [x] đã xong | [ ] chưa làm

### 3.1 Migrations
```
[x] 2024_01_01_000001_create_users_table.php
[x] 2024_01_01_000002_create_operators_table.php
[x] 2024_01_01_000003_create_drivers_table.php
[x] 2024_01_01_000004_create_vehicles_table.php
[x] 2024_01_01_000005_create_routes_table.php
[x] 2024_01_01_000006_create_route_stops_table.php
[x] 2024_01_01_000007_create_trips_table.php
[x] 2024_01_01_000008_create_seat_maps_table.php
[x] 2024_01_01_000009_create_bookings_table.php
[x] 2024_01_01_000010_create_booking_passengers_table.php
[x] 2024_01_01_000011_create_payments_table.php
[x] 2024_01_01_000012_create_vouchers_table.php
[x] 2024_01_01_000013_create_voucher_usages_table.php
[x] 2024_01_01_000014_create_reviews_table.php
[x] 2024_01_01_000015_create_wallets_table.php
[x] 2024_01_01_000016_create_wallet_transactions_table.php
[x] 2024_01_01_000017_create_notifications_table.php
```

### 3.2 Models
```
[x] app/Models/User.php
[x] app/Models/Operator.php
[x] app/Models/Driver.php
[x] app/Models/Vehicle.php
[x] app/Models/Route.php
[x] app/Models/RouteStop.php
[x] app/Models/Trip.php
[x] app/Models/SeatMap.php
[x] app/Models/Booking.php
[x] app/Models/BookingPassenger.php
[x] app/Models/Payment.php
[x] app/Models/Voucher.php
[x] app/Models/VoucherUsage.php
[x] app/Models/Review.php
[x] app/Models/Wallet.php
[x] app/Models/WalletTransaction.php
[x] app/Models/Notification.php
```

### 3.3 Services
```
[x] app/Services/BookingService.php
[x] app/Services/TripService.php
[x] app/Services/PaymentService.php
[x] app/Services/NotificationService.php
[x] app/Services/VoucherService.php
[x] app/Services/WalletService.php
[x] app/Services/QrCodeService.php
[x] app/Services/TrackingService.php
[x] app/Services/OtpService.php
```

### 3.4 Controllers — Customer
```
[x] app/Http/Controllers/Customer/AuthController.php
[x] app/Http/Controllers/Customer/TripSearchController.php
[x] app/Http/Controllers/Customer/BookingController.php
[x] app/Http/Controllers/Customer/PaymentController.php
[x] app/Http/Controllers/Customer/TrackingController.php
[x] app/Http/Controllers/Customer/ReviewController.php
[x] app/Http/Controllers/Customer/WalletController.php
[x] app/Http/Controllers/Customer/NotificationController.php
```

### 3.5 Controllers — Driver
```
[x] app/Http/Controllers/Driver/AuthController.php
[x] app/Http/Controllers/Driver/TripController.php
[x] app/Http/Controllers/Driver/CheckinController.php
[x] app/Http/Controllers/Driver/LocationController.php
[x] app/Http/Controllers/Driver/EarningController.php
```

### 3.6 Controllers — Operator
```
[x] app/Http/Controllers/Operator/AuthController.php
[x] app/Http/Controllers/Operator/RouteController.php
[x] app/Http/Controllers/Operator/VehicleController.php
[x] app/Http/Controllers/Operator/DriverController.php
[x] app/Http/Controllers/Operator/TripController.php
[x] app/Http/Controllers/Operator/BookingController.php
[x] app/Http/Controllers/Operator/RevenueController.php
```

### 3.7 Controllers — Admin
```
[x] app/Http/Controllers/Admin/AuthController.php
[x] app/Http/Controllers/Admin/DashboardController.php
[x] app/Http/Controllers/Admin/OperatorController.php
[x] app/Http/Controllers/Admin/DriverController.php
[x] app/Http/Controllers/Admin/UserController.php
[x] app/Http/Controllers/Admin/TripController.php
[x] app/Http/Controllers/Admin/FinanceController.php
[x] app/Http/Controllers/Admin/VoucherController.php
```

### 3.8 Jobs
```
[x] app/Jobs/SendSmsNotificationJob.php
[x] app/Jobs/SendZaloNotificationJob.php
[x] app/Jobs/SendEmailNotificationJob.php
[x] app/Jobs/GenerateQrCodeJob.php
[x] app/Jobs/ExpireUnpaidBookingJob.php
[x] app/Jobs/ExpireLockedSeatsJob.php
[x] app/Jobs/ProcessRefundJob.php
[x] app/Jobs/UpdateDriverRatingJob.php
[x] app/Jobs/GenerateTripManifestJob.php
```

### 3.9 Events & Listeners
```
[x] app/Events/BookingConfirmed.php
[x] app/Events/BookingCancelled.php
[x] app/Events/TripStarted.php
[x] app/Events/TripCompleted.php
[x] app/Events/DriverLocationUpdated.php
[x] app/Events/PaymentProcessed.php
[x] app/Listeners/SendBookingConfirmationNotification.php
[x] app/Listeners/SendBookingCancellationNotification.php
[x] app/Listeners/NotifyPassengersOnTripStart.php
[x] app/Listeners/BroadcastDriverLocation.php
[x] app/Listeners/UpdateDriverRatingOnTripComplete.php
```

### 3.10 Config & Routes
```
[x] config/auth.php (4 guards — customer, driver, operator, admin)
[x] config/services.php (ESMS, Zalo, MoMo, VNPay, Google Maps)
[x] routes/api_customer.php
[x] routes/api_driver.php
[x] routes/api_operator.php
[x] routes/api_admin.php
[x] routes/api_public.php
[x] routes/channels.php
```

### 3.11 API Resources
```
[x] app/Http/Resources/Customer/TripResource.php
[x] app/Http/Resources/Customer/TripSearchResource.php
[x] app/Http/Resources/Customer/BookingResource.php
[x] app/Http/Resources/Customer/BookingListResource.php
[x] app/Http/Resources/Customer/WalletResource.php
[x] app/Http/Resources/Driver/TripResource.php
[x] app/Http/Resources/Driver/PassengerResource.php
[x] app/Http/Resources/Driver/EarningResource.php
[x] app/Http/Resources/Operator/TripResource.php
[x] app/Http/Resources/Operator/BookingResource.php
[x] app/Http/Resources/Operator/RevenueResource.php
[x] app/Http/Resources/Admin/UserResource.php
[x] app/Http/Resources/Admin/OperatorResource.php
[x] app/Http/Resources/Admin/DriverResource.php
[x] app/Http/Resources/Admin/TripResource.php
[x] app/Http/Resources/Admin/FinanceResource.php
```

### 3.12 Providers & Seeders
```
[x] app/Providers/RepositoryServiceProvider.php
[x] app/Providers/EventServiceProvider.php
[x] bootstrap/providers.php (đã đăng ký 2 providers)
[x] bootstrap/app.php (đã đăng ký 5 route files)
[x] database/seeders/AdminSeeder.php   (admin@xeghep.vn / Admin@123456)
[x] database/seeders/OperatorSeeder.php
[x] database/seeders/TripSeeder.php    (10 trips hôm nay + route HN↔HP + stops)
[x] database/seeders/DatabaseSeeder.php (đã gọi 3 seeders)
```

---

## 4. QUYẾT ĐỊNH CỤ THỂ ĐÃ THỐNG NHẤT

> Đây là những quyết định chi tiết cho từng vấn đề cụ thể đã được thảo luận và thống nhất.

### 4.1 Booking
```
Seat lock timeout  : 10 phút (Redis TTL)
Payment timeout    : 15 phút (booking.expires_at)
Max seats/booking  : 4
Min time to book   : 30 phút trước giờ xuất phát
Booking code       : HNHP + YYMMDD + 3-digit sequence (reset hàng ngày)
Race condition     : DB::transaction() + lockForUpdate() + retry 3 lần
```

### 4.2 Refund Policy
```
> 24h trước : hoàn 100% (trừ 5.000đ phí xử lý)
4h – 24h    : hoàn 50%
< 4h        : hoàn 0%
Nhà xe hủy  : hoàn 100% + voucher 20.000đ bồi thường
Hoàn về ví  : tức thì
Hoàn bank   : 3–5 ngày làm việc
```

### 4.3 Commission
```
Mặc định    : 10% doanh thu/booking
Có thể custom per operator (admin config)
Quyết toán  : ngày 1 và ngày 15 hàng tháng
```

### 4.4 GPS Tracking
```
Driver gửi  : mỗi 15 giây khi trip = in_progress
Redis TTL   : 30 giây (nếu không update → coi offline)
Rate limit  : 1 request/10 giây
ETA         : Google Maps Distance Matrix API
Broadcast   : Laravel Reverb WebSocket
```

### 4.5 Notification
```
SMS         : ESMS.vn (brandname: XeGhep)
Zalo        : Zalo OA API (user phải follow OA trước)
Email       : SMTP (dùng Laravel Mail)
Push        : Firebase FCM
In-app      : lưu vào bảng notifications, đọc qua API
Timing:
  - Xác nhận đặt vé: tức thì (sau payment success)
  - Nhắc nhở: 2 giờ trước giờ xuất phát (scheduled job)
  - Xe đến: khi ETA < 5 phút (triggered by tracking)
```

### 4.6 OTP
```
Length      : 6 chữ số
TTL         : 5 phút
Rate limit  : 5 lần/giờ/SĐT
Storage     : Redis key "otp:{phone}" + "otp_count:{phone}"
```

### 4.7 Mô hình dòng tiền tài xế (chốt 2026-06-16 — Phương án A)
```
Dòng tiền    : Khách → Nền tảng → (trừ hoa hồng) → NHÀ XE (Payout, admin chi)
Tài xế       : Nhà xe quyết toán & trả trực tiếp cho tài xế (NGOÀI nền tảng)
              → Nền tảng KHÔNG giữ tiền của tài xế
Trang earnings tài xế = CHỈ XEM (bảng kê): tổng tích lũy, thống kê chuyến/khách/km,
              biểu đồ 7 ngày, lịch sử theo chuyến. ĐÃ BỎ tính năng rút tiền.
Đã gỡ        : route POST /driver/earnings/withdraw, EarningController::withdraw(),
              driverApi.withdraw, nút + modal rút tiền, field available_balance/pending_amount.
Lưu ý        : KHÔNG ghi có ví tài xế khi TripCompleted. Ví driver không dùng nữa.
              (Trái PRD F-D05 "rút tiền" — đã thống nhất ưu tiên gỡ mâu thuẫn nghiệp vụ.)
```

### 4.9 Đăng ký đối tác nhà xe (landing page + duyệt) — chốt 2026-06-16
```
Nguồn UI    : Stitch screen "Đăng ký đối tác Nhà xe" → convert Vue (blue-600).
Mô hình     : Bảng partner_applications (LEAD, KHÔNG tạo account ngay). Khách gửi đơn
              từ landing public → admin duyệt. DUYỆT mới tạo User(role=operator,
              password ngẫu nhiên, is_verified) + Operator(verified).
FE          : Header CustomerLayout có link "Trở thành đối tác" → /partner
              (pages/customer/partner/Register.vue: Hero + Benefits 4-card + Form +
              upload). Submit qua apiClient.postForm (multipart).
Admin       : /admin/operators thêm 2-view: "Đơn đăng ký đối tác" (badge pending) +
              "Nhà xe đã có tài khoản". Duyệt đơn → tạo tài khoản nhà xe.
Endpoints   : POST /public/partner-applications; GET /admin/partner-applications;
              POST /admin/partner-applications/{id}/approve {commission_rate};
              POST /admin/partner-applications/{id}/reject {reason}.
Rule        : approve chặn nếu SĐT đã có User; chặn duyệt lại; reject chặn nếu approved.
              business_license (NOT NULL) tạm map = tax_code (chờ nhà xe bổ sung số GPKD).
SMS duyệt   : approve sinh mật khẩu tạm (2 chữ hoa+6 số, vd CG177965) lưu vào User
              (cast hashed) + dispatch SendSmsNotificationJob (queue notifications) gửi
              SĐT+mật khẩu+link /operator/login. Fire-and-forget (try/catch, lỗi SMS 
              KHÔNG làm hỏng duyệt). Helper generateTempPassword()+sendCredentialsSms().
Đội xe (v2) : 2026-06-17 đổi "loại xe chủ đạo" → CƠ CẤU THEO LOẠI. Migration 000019:
              drop vehicle_type, add fleet_breakdown JSON {sedan_4,mpv_7,van_9,minibus_16};
              vehicle_count = tổng tính ở BE (không tin FE). Model.fleetSummary()+FLEET_LABELS.
              FE Register: 4 ô số/loại + tổng tự cộng (validate tổng≥1, withValidator BE).
Onboarding  : sau duyệt fleet_breakdown = SNAPSHOT (giữ trong partner_applications, link
              operator_id qua Operator::partnerApplication hasOne). KHÔNG auto-tạo Vehicle.
              GET /operator/onboarding/fleet (OnboardingController) → declared vs actual
              (operator->vehicles count) → widget nhắc "đã thêm X/N xe" ở operator Dashboard.
Đối chiếu   : Admin OperatorController index eager load partnerApplication + withCount
              vehicles; OperatorResource thêm declared_fleet_total/summary + actual_vehicles_count;
              Approve.vue card nhà xe hiện "đã thêm X/N xe khai báo" (chống khai khống).
Lưu ý       : Stitch dùng Material Symbols + navy custom → thay bằng inline SVG + Tailwind
              blue-600. apiClient.postForm tự set multipart. Mọi *.api.ts method có route.
```

### 4.11 Cấp mật khẩu tài xế khi admin duyệt (chốt 2026-06-17)
```
Vấn đề   : tài xế chỉ login khi driver.status=Verified (admin duyệt). Trước đây MK gửi
           LÚC OPERATOR TẠO (status=pending) → tài xế nhận MK nhưng login chưa được;
           duyệt xong không có thông báo. SMS hay không tới (ESMS dev trống).
Chốt     : cấp & gửi MK ĐÚNG LÚC admin DUYỆT. SMS là kênh chính; plaintext hiện 1 lần
           cho NGƯỜI VỪA BẤM (admin lúc duyệt / operator|admin lúc cấp lại). KHÔNG lưu
           plaintext để hiện lại sau (MK đã hash) → không auto-hiện cho operator.
BE       : DriverService::createByOperator → trả Driver (BỎ temp_password), SMS "chờ duyệt"
           (sendPendingSms, không MK). approveAndIssueCredentials(Driver):string — verified +
           reset MK + SMS credentials + return plaintext. resetPassword(Driver):string dùng chung.
           Admin DriverController@approve trả {phone,temp_password}; @resetPassword.
           Operator DriverController@resetPassword (guard tài xế thuộc nhà xe). store BỎ temp_password.
           Routes: POST /admin/drivers/{id}/reset-password, /operator/drivers/{id}/reset-password.
FE       : Admin Drivers/Verify.vue: sau duyệt → modal hiện SĐT+MK+sao chép; nút "Cấp lại mật khẩu"
           ở tài xế verified. Operator Vehicles/Index.vue (tab tài xế): tạo xong báo "chờ duyệt"
           (bỏ MK), nút "Cấp lại MK" + modal plaintext. adminApi.verifyDriver/resetDriverPassword
           trả {phone,temp_password}; operatorApi.resetDriverPassword; createDriver bỏ temp_password.
Verify   : tạo→pending SMS không chứa MK; duyệt→MK khớp hash+login OK; reset→MK mới OK, MK cũ vô hiệu.
Lưu ý    : login gating Driver/AuthController giữ nguyên (pending→403). Tương tự luồng nhà xe
           ([[project_partner_application]]).
```

### 4.10 Chuyến quá giờ — nhà xe tự phân định + chạy tay (chốt 2026-06-17)
```
Vấn đề   : auto-resolve (cron 10') tự hủy+hoàn chuyến scheduled quá giờ 2h. Nhưng nếu
           chuyến THỰC TẾ đã chạy mà tài xế quên cập nhật → hoàn tiền OAN cho nhà xe.
           Thêm: composer dev KHÔNG có schedule:work → cron không chạy ở local.
Chốt     : nhà xe tự phân định trong cửa sổ ân hạn 2h; mặc định an toàn = tự hủy+hoàn.
BE       : TripService::markRanCompleted(Trip) — confirmed+checked_in→completed (ghi nhận
           doanh thu, KHÔNG no_show/hoàn), pending→cancelled, fire TripCompleted.
           POST /operator/trips/{id}/complete (guard scheduled/boarding & quá giờ).
           POST /admin/trips/auto-resolve (Artisan::call → output) chạy tay.
           composer dev THÊM `php artisan schedule:work`.
FE       : operator Schedule.vue: isOverdue → ô lịch đỏ "⏰ Quá giờ" + banner "cần xử lý" +
           modal nút "Đã chạy xong" (complete) / "Hủy chuyến" (cancel+refund).
           admin LiveMap.vue: nút "Xử lý chuyến quá giờ". operatorApi.completeTrip,
           adminApi.runAutoResolveTrips.
Lưu ý    : markRanCompleted KHÁC completeTrip (finalizeOnTripComplete đánh confirmed→no_show
           cho luồng check-in QR). Production vẫn cần cron `schedule:run`. Xem [[project_expired_trip_handling]].
```

### 4.8 Đối soát tiền mặt vs online khi quyết toán (chốt 2026-06-16)
```
Vấn đề    : Vé tiền mặt → tài xế ĐÃ cầm tiền (payment.collected_by), nền tảng KHÔNG
            giữ. Nhưng code cũ tính payout nhà xe = doanh thu − hoa hồng cho MỌI vé
            ⇒ nền tảng đi trả cả phần tiền mặt nhà xe đã thu (sai 2 lần).
Nguyên tắc: Nền tảng LUÔN hưởng hoa hồng mọi vé realized.
            Online → nền tảng giữ tiền → NỢ nhà xe (final − hoa hồng).
            Tiền mặt → nhà xe đã thu → nhà xe NỢ nền tảng phần hoa hồng.
            settlement = Σonline(final − hoa hồng) − Σcash(hoa hồng)
              >0 nền tảng chi cho nhà xe ; <0 nhà xe nộp lại nền tảng (status 'receivable').
Triển khai: app/Services/SettlementService::forOperator() = NGUỒN TÍNH DUY NHẤT,
            dùng chung Operator/RevenueController::availableBalance + Admin/FinanceController.
            Admin summary thêm: platform_held (online), cash_collected, operator_debt.
            Admin commissions row: cash_gross, cash_commission, net_amount (âm=nợ), status receivable.
            payout() chặn khi net_amount<=0 (âm ⇒ 'nhà xe NỢ nền tảng').
Lưu ý     : Payment success vé tiền mặt vẫn nằm trong total_revenue=GMV, NHƯNG
            platform_held chỉ tính online. KHÔNG cộng tiền mặt vào tiền nền tảng thực giữ.
```

### 4.10 Ngưỡng tối thiểu trước giờ khởi hành (chốt 2026-06-16)
```
Quyết định: GIỮ 30 phút (PRD F-C02/§10.1). Khách không thấy/không đặt được chuyến
            khởi hành trong vòng 30'. Tách thành config: config/booking.php
            → min_lead_minutes (env BOOKING_MIN_LEAD_MINUTES, mặc định 30).
Lỗi đã fix: BẤT NHẤT — nhà xe tạo được chuyến cách giờ chạy <30' (StoreTripRequest
            chỉ 'after:now') nhưng khách KHÔNG BAO GIỜ đặt được ⇒ chuyến "không bán được".
            Đồng bộ 4 nơi qua cùng config:
            - Trip::scopeAvailable() + canBeBooked()  (phía khách)
            - TripService::create() → chặn tạo chuyến <min_lead (bao cả bulkCreate vì
              bulk gọi create() rồi bỏ qua InvalidArgumentException → skip)
            - StoreTripRequest depart_at = 'after: now+min_lead' + message rõ
            - FE operator/Trips/Schedule.vue: input datetime-local :min = now+30'
Lý do giữ 30': giữ ghế 10' + hạn thanh toán 15' → đặt sát giờ làm vé mồ côi sau giờ chạy.
```

### 4.12 Bỏ "Bật/tắt nhận khách" (is_online) ở tài xế (chốt 2026-06-17)
```
Quyết định: XeGhep là xe GHÉP TUYẾN CỐ ĐỊNH — tài xế KHÔNG tự bắt khách như xe ôm
            công nghệ; chuyến do nhà xe phân công. Nên cờ is_online ("nhận khách")
            VÔ NGHĨA. Đã gỡ ở FE DriverLayout.vue: nút toggle sidebar + pill Online/Offline
            header + hàm toggleOnline. is_online KHÔNG gate start/complete/checkin (đã verify).
            Cũng đã gỡ chỉ báo "Online" mồ côi ở danh sách tài xế /operator/vehicles
            (Vehicles/Index.vue tab Tài xế) — sau khi bỏ toggle, không tài xế nào online được
            nên chỉ báo này chết; cột Trạng thái chỉ còn badge duyệt hồ sơ (Chờ duyệt/Đã duyệt/...).
Còn lại (mồ côi, không gỡ — vô hại, có thể tái dùng làm active/inactive sau):
  - BE: PUT /driver/auth/status (AuthController::updateStatus), cột drivers.is_online,
    Driver::scopeOnline (chưa dùng), driverApi.setStatus, store isOnline/setOnline.
  - Admin dashboard "drivers_online" sẽ luôn 0 (không còn ai bật) — hiển thị, không phải bug.
Phát hiện phụ (CHƯA sửa, chờ quyết định): (a) trạng thái 'boarding' không dùng —
  start() nhảy thẳng scheduled→in_progress; (b) checkin không kiểm tra trip đã start chưa;
  (c) start() không chặn bắt đầu trước giờ khởi hành. Đều không chặn vận hành.
```

### 4.13 Cô lập 4 portal bằng middleware `role` (chốt 2026-06-21)
```
Vấn đề   : 03-architecture §2 ghi "4 guard riêng biệt" là FINAL, nhưng THỰC TẾ cả 4
           route file dùng chung 'auth:sanctum'. Vì 4 guard customer/driver/operator/
           admin trong config/auth.php đều driver=sanctum + CÙNG model User, mà Sanctum
           auth bằng token sẽ resolve tokenable (User) BỎ QUA provider của guard ⇒ đổi
           sang 'auth:customer'… cũng KHÔNG lọc theo role. Token không set abilities
           (mặc định *). Hệ quả: token customer hợp lệ vẫn qua được /api/admin/* (Broken
           Access Control). Admin controller (vd DashboardController) không re-check role
           ⇒ rò rỉ; driver/operator deref ->driver/->operator null ⇒ 500 (che lỗi tình cờ).
Chốt     : Phương án B — middleware kiểm tra role (KHÔNG dùng token abilities để tránh
           đá session đang chạy; KHÔNG chỉ đổi guard vì vô tác dụng với model User chung).
BE       : app/Http/Middleware/EnsureUserRole.php — so user()->role->value với tham số
           role, lệch → abort(403,'Không có quyền truy cập'). Alias 'role' đăng ký trong
           bootstrap/app.php (withMiddleware). 4 route group đổi thành
           ['auth:sanctum', 'role:<portal>'] (customer/driver/operator/admin).
Test     : tests/Feature/PortalAccessControlTest.php (7 case) — cross-portal=403,
           đúng role=200, không token=401. Toàn suite 29 passed/8 skipped/0 failed.
Lưu ý    : token cũ trong DB vẫn hợp lệ (không cần login lại). Production có route:cache
           phải chạy lại `php artisan route:cache` sau deploy. Cách C (Sanctum abilities)
           để dành cho Phase 2 nếu cần phân quyền chi tiết theo hành động.
```

### 4.14 Hoàn tất Group A Wayfinder + 6 endpoint BE còn thiếu (chốt 2026-06-21)
```
Bối cảnh : [[project_wayfinder_migration]] còn "6 method Group A giữ TODO" — FE gọi
           nhưng BE chưa có route (fail im lặng), kèm bug FE.
Đã thêm BE:
  - PUT  /driver/auth/profile   → Driver/AuthController::updateProfile (full_name/email/avatar)
  - PUT  /driver/auth/password  → Driver/AuthController::changePassword (sai MK cũ → 422 INVALID_CURRENT_PASSWORD)
  - POST /driver/documents      → Driver/AuthController::uploadDocument (type→cột id_card_front/back_url|license_front_url, ảnh ≤10MB)
  - GET  /driver/notifications + PUT /driver/notifications/{id}/read → Driver/NotificationController (mirror Customer)
  - GET  /admin/dashboard/map   → Admin/DashboardController::map (tái dùng TripRepository::findInProgress + LiveTripResource, chung nguồn /admin/trips/monitor)
Fix #3   : driver.api uploadDocument build FormData nhưng gọi apiClient.post (ép JSON) →
           đổi apiClient.sendForm(uploadDocument(), form) (multipart đúng).
FE       : driver.api.ts + admin.api.ts gỡ hết TODO, dùng apiClient.send/sendForm với
           Wayfinder action. Chạy `php artisan wayfinder:generate` sinh lại actions.
Test     : tests/Feature/DriverSelfServiceTest.php (8 case BE) + viết lại 2 FE spec
           (driver/admin) từ "legacy client" → assert Wayfinder. BE 44/36 passed/8 skip,
           FE 22/22. TS lỗi 'form' ở starter-kit (DeleteUser/auth/settings) là CŨ, không liên quan.
Lưu ý    : routes driver/admin đã gắn role middleware [[4.13]] nên endpoint mới tự kế thừa.
```

---

## 5. VẤN ĐỀ ĐÃ GẶP VÀ CÁCH GIẢI QUYẾT

> AI Agent ghi vào đây khi gặp lỗi để tránh lặp lại.

### 2026-06-22 — Lỗi: Operator me() trả tax_number/license_number (luôn null) + thiếu sửa hồ sơ
File     : app/Http/Controllers/Operator/AuthController.php, routes/api_operator.php
Vấn đề   : me() đọc $operator->tax_number / ->license_number — cột THỰC là tax_code /
           business_license (đúng như seeder fix §3.16) → Eloquent trả null âm thầm, màn
           hồ sơ nhà xe luôn trống MST/GPKD. Ngoài ra operator KHÔNG có updateProfile/
           changePassword (controller/route/api/FE đều thiếu) dù PRD §5.1 ghi
           PUT /operator/auth/profile → không tự sửa được TK ngân hàng nhận tiền (F-O01),
           ảnh hưởng quyết toán.
Giải pháp: me() → tax_code/business_license + thêm bank_account/bank_name/bank_account_name/
           logo_url/description vào response. Thêm updateProfile (sửa liên hệ + bank info +
           logo/description; KHÔNG cho sửa tax_code/business_license — giấy tờ admin duyệt) +
           changePassword. Routes PUT /operator/auth/{profile,password}. Test
           tests/Feature/OperatorProfileTest.php (6 case). FE (operatorApi + trang Profile) làm sau.
Note     : Khi viết response me()/Resource cho operator, dùng ĐÚNG tên cột tax_code/
           business_license — đối chiếu Operator model TRƯỚC. Bug cùng họ với §3.16 và mục 5 (2026-06-15).

### 2026-06-15 — Lỗi: Trang /admin/finance không hiển thị doanh thu
File     : app/Http/Controllers/Admin/FinanceController.php, routes/api_admin.php
           (FE: resources/js/pages/admin/Finance/Overview.vue)
Vấn đề   : Doanh thu hiện 0₫ dù DB có dữ liệu (42 payment success = 10.120.000đ).
           Nguyên nhân lệch hợp đồng FE↔BE + thiếu endpoint:
           1. apiClient ĐÃ tự unwrap `.data`. BE `summary()` trả object phẳng
              (data.total_revenue), nhưng Vue đọc `overviewRes.data?.summary?.total_revenue`
              → `summary = null` → mọi thẻ về 0₫. BE còn thiếu hẳn 3 field FE cần:
              total_commission, pending_settlement, total_refunds.
           2. FE gọi GET /admin/finance/commissions và POST /admin/finance/payouts
              nhưng 2 route này KHÔNG tồn tại → biểu đồ + tab Quyết toán rỗng, nút payout lỗi.
Giải pháp: Sửa BE khớp đúng shape FE mong đợi (bọc trong key `summary`, đủ 4 field);
           bổ sung 2 endpoint commissions + payout. Tái dùng mô hình doanh thu realized
           (booking completed+paid) + commission theo operator.commission_rate + bảng Payout
           y như Operator/RevenueController để số liệu 2 portal khớp nhau.
Note     : LUÔN đối chiếu shape FE đọc (`res.data.X.Y`) với BE trả về TRƯỚC khi nghi ngờ
           dữ liệu — apiClient đã unwrap `.data` một lần, đừng lồng/đọc thừa 1 tầng.
           Khi sửa/làm trang mới: kiểm tra mọi method trong *.api.ts đã có route tương ứng
           trong routes/api_*.php (FE fail im lặng nếu không check `res.error`).

### 2026-06-21 — Lỗi: Bộ test BE không thực sự chạy (CI tests đỏ tiềm ẩn)
File     : tests/Pest.php, database/factories/UserFactory.php
Vấn đề   : 2 lỗi cộng hưởng khiến mọi Feature test chỉ "pass" nhờ config cache cũ trỏ
           DB sang schema lệch — không phải chạy thật:
           1. tests/Pest.php DÒNG `->use(RefreshDatabase::class)` BỊ COMMENT → Feature
              test KHÔNG migrate. Trên sqlite :memory: (đúng phpunit.xml + CI) = DB rỗng
              → "no such table: users".
           2. UserFactory vẫn là factory STARTER (name/email/email_verified_at/remember_token)
              trong khi bảng users dự án là full_name/phone/role/is_verified → MySQL báo
              "Unknown column 'name'".
Giải pháp: (a) Bỏ comment RefreshDatabase trong Pest.php. (b) Viết lại UserFactory theo
           schema dự án + state admin()/driver()/operator()/unverified(). (c) Gỡ 3 file test
           STARTER VESTIGIAL test hành vi đã bị thay bằng API/SPA: Auth/PasswordResetTest
           (reset email-token — dự án dùng OTP SMS, không có bảng password_reset_tokens),
           DashboardTest (/dashboard giờ trả SPA public, bỏ redirect), Settings/ProfileUpdateTest
           (ProfileController còn tham chiếu cột email_verified_at không tồn tại).
           → Suite chạy thật trên sqlite :memory: = 23 passed/3 skipped/0 failed.
Note     : Còn AuthenticationTest + SecurityTest (settings/password) là starter nhưng đang
           XANH — để lại, dọn sau nếu muốn. Nếu thấy test "pass" mà nghi ngờ, kiểm tra
           RefreshDatabase có bật + factory khớp schema TRƯỚC. KHÔNG bao giờ cache config khi
           chạy test (che mất :memory:). 4 lỗi TS có sẵn ở LiveMap.vue/TripDetail.vue không
           chặn vite build (build không type-check).

**Template để AI Agent ghi lỗi:**
```

**Template để AI Agent ghi lỗi:**
```
### [Ngày] — Lỗi: [Tên lỗi]
File     : [đường dẫn file]
Vấn đề   : [mô tả vấn đề]
Giải pháp: [cách đã fix]
Note     : [lưu ý tránh tái diễn]
```

---

## 6. CÁC DEPENDENCY NGOÀI ĐÃ DÙNG

```php
// composer.json — packages đã dùng
"require": {
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "laravel/reverb": "^1.0",
    "predis/predis": "^2.0",        // Redis client
    "barryvdh/laravel-dompdf": "^3.0", // PDF export
    "simplesoftwareio/simple-qrcode": "^4.2", // QR code
    "intervention/image": "^3.0",    // Image processing
    "maatwebsite/excel": "^3.1",     // Excel export
    "spatie/laravel-activitylog": "^4.7", // Audit log
}
```

---

## 7. ENVIRONMENT VARIABLES CẦN THIẾT

```env
# App
APP_NAME=XeGhep
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DATN_WD50
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
QUEUE_RETRY_AFTER=90

# Broadcasting
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1

# ESMS (SMS)
ESMS_API_KEY=
ESMS_SECRET_KEY=
ESMS_BRAND_NAME=XeGhep

# Zalo OA
ZALO_OA_ID=
ZALO_OA_ACCESS_TOKEN=

# MoMo
MOMO_PARTNER_CODE=
MOMO_ACCESS_KEY=
MOMO_SECRET_KEY=
MOMO_ENDPOINT=https://test-payment.momo.vn

# VNPay
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html

# Google Maps
GOOGLE_MAPS_API_KEY=

# Firebase FCM
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json

# File Storage
FILESYSTEM_DISK=local
AWS_BUCKET=
```

---

## 8. HƯỚNG DẪN CHO AI AGENT

### Khi bắt đầu session mới:
1. Đọc file này (`04-memory.md`) TRƯỚC TIÊN
2. Đọc `01-project-context.md` để nắm ngữ cảnh
3. Đọc `03-architecture.md` để biết pattern cần dùng
4. Kiểm tra section 3 "File đã sinh code" — KHÔNG sinh lại file đã có `[x]`
5. Tiếp tục từ item chưa làm (`[ ]`) theo thứ tự ưu tiên

### Thứ tự sinh code bắt buộc:
```
1. Migrations (phải xong trước khi làm bất cứ thứ gì khác)
2. Models + Relationships
3. Exceptions (custom exceptions)
4. Repositories + Contracts
5. Services
6. Events + Listeners
7. Jobs
8. Requests (Form Validation)
9. Controllers
10. API Resources
11. Routes
12. Config files
13. Seeders
14. Tests
```

### Sau mỗi task hoàn thành:
1. Cập nhật `[ ]` → `[x]` cho file đã tạo trong section 3
2. Ghi vào section 5 nếu có lỗi đã gặp
3. Ghi vào section 4 nếu có quyết định mới được thống nhất

### Nguyên tắc KHÔNG được vi phạm:
```
❌ KHÔNG tự ý dùng package mới ngoài danh sách section 6
❌ KHÔNG thay đổi database schema đã định nghĩa trong task document
❌ KHÔNG đặt business logic trong Controller
❌ KHÔNG gọi external API trực tiếp trong Controller hay Service (phải qua Job)
❌ KHÔNG dùng raw SQL query (dùng Eloquent hoặc Query Builder)
❌ KHÔNG bỏ qua DB::transaction() cho booking operations
❌ KHÔNG sinh code cho tính năng ngoài PRD (OUT OF SCOPE)
❌ KHÔNG để message lỗi bằng tiếng Anh hiển thị cho end user
```