# PRD – PRODUCT REQUIREMENTS DOCUMENT
# Xe Ghép Tuyến Hà Nội – Hải Phòng | v1.0 MVP
> Đây là nguồn sự thật duy nhất (single source of truth) về yêu cầu sản phẩm.
> AI Agent phải bám sát tài liệu này khi sinh code. KHÔNG tự ý thêm tính năng ngoài scope.

---

## 1. CUSTOMER FEATURES

### F-C01: Đăng ký / Đăng nhập
**Priority**: P0 (must have)
**Acceptance Criteria**:
- [x] Đăng ký bằng số điện thoại + OTP (6 chữ số, hết hạn 5 phút)
- [x] Đăng nhập bằng số điện thoại + mật khẩu
- [x] OTP gửi qua SMS (ESMS.vn), giới hạn 5 lần/giờ/SĐT
- [x] Token hết hạn sau 30 ngày (Sanctum)
- [x] Quên mật khẩu qua OTP SMS
- [x] Cập nhật hồ sơ: tên, email, avatar

**Error Cases**:
- SĐT đã tồn tại → "Số điện thoại đã được đăng ký"
- OTP sai → "Mã OTP không chính xác" (còn X lần)
- OTP hết hạn → "Mã OTP đã hết hạn, vui lòng gửi lại"
- Quá 5 lần gửi OTP → "Vui lòng thử lại sau 1 giờ"

---

### F-C02: Tìm kiếm chuyến đi
**Priority**: P0
**Input**: điểm đi, điểm đến, ngày, số hành khách (1–4)
**Acceptance Criteria**:
- [x] Hiển thị danh sách chuyến khả dụng (status=scheduled, available_seats >= passengers)
- [x] Thông tin hiển thị: giờ xuất phát, giờ đến dự kiến, giá vé, số ghế còn, loại xe, tên nhà xe, rating tài xế
- [x] Sắp xếp mặc định theo giờ xuất phát tăng dần
- [x] Lọc theo: giá (tăng/giảm), giờ đi (sáng/chiều/tối)
- [x] Cache kết quả 2 phút (Redis)
- [x] Không hiển thị chuyến xuất phát trong vòng 30 phút

**Performance**: Response < 300ms (nhờ cache)

---

### F-C03: Chọn ghế
**Priority**: P0
**Acceptance Criteria**:
- [x] Hiển thị sơ đồ xe với trạng thái từng ghế (available/locked/booked/disabled)
- [x] Lock ghế tạm 10 phút khi user click chọn
- [x] Tự động unlock nếu không hoàn tất đặt vé
- [x] Update trạng thái ghế real-time (WebSocket)
- [x] Chọn tối đa 4 ghế/lần

**Màu sắc ghế**:
- Xanh lá: available
- Vàng: locked (người khác đang giữ)
- Đỏ: booked
- Xám: disabled (hỏng)

---

### F-C04: Đặt vé & Thanh toán
**Priority**: P0
**Acceptance Criteria**:
- [x] Nhập thông tin hành khách (tên, SĐT cho mỗi người)
- [x] Chọn điểm đón/trả từ danh sách điểm cố định
- [x] Nhập địa chỉ chi tiết thêm (optional)
- [x] Nhập ghi chú cho tài xế (optional)
- [x] Áp mã giảm giá (hiện discount ngay lập tức)
- [x] Chọn phương thức thanh toán: MoMo, VNPay, tiền mặt
- [x] Booking hết hạn sau 15 phút nếu chưa thanh toán
- [x] Redirect sang cổng thanh toán MoMo/VNPay
- [x] Sau thanh toán thành công → hiển thị vé QR
- [x] Gửi SMS + email xác nhận

**Validation**:
```
contact_name   : required, min 2, max 100 ký tự
contact_phone  : required, format SĐT Việt Nam (10 số, bắt đầu 0)
pickup_stop_id : required, phải thuộc route của trip
passenger_count: required, 1–4
passengers[*].full_name: required
```

---

### F-C05: Quản lý vé
**Priority**: P0
**Acceptance Criteria**:
- [x] Danh sách vé đã đặt (paginate 10/trang)
- [x] Filter: upcoming / past / cancelled
- [x] Chi tiết vé: thông tin chuyến, ghế, điểm đón, QR code
- [x] Download QR code ảnh PNG
- [x] Hủy vé với thông báo % hoàn tiền trước khi confirm
- [x] Xem trạng thái hoàn tiền

---

### F-C06: Theo dõi chuyến đi real-time
**Priority**: P0
**Acceptance Criteria**:
- [x] Khi trip status = in_progress: hiển thị bản đồ với vị trí xe (Google Maps)
- [x] Cập nhật vị trí xe mỗi 15 giây
- [x] Hiển thị ETA (còn bao nhiêu phút đến điểm đón)
- [x] Hiển thị biển số xe, tên tài xế, SĐT tài xế (chỉ khi đã check-in)
- [x] Track bằng tracking_code mà không cần đăng nhập

**WebSocket channel**: `trips.{trip_id}`

---

### F-C07: Thông báo
**Priority**: P0
**Trigger & Content**:
```
Đặt vé thành công (SMS + Zalo):
"[XeGhep] Đặt vé thành công! Mã vé: HNHP240615001
Tuyến: Hà Nội → Hải Phòng
Ngày: 15/06/2024 06:00
Điểm đón: Mỹ Đình
Tài xế sẽ liên hệ trước khi đến. Theo dõi: xeghep.vn/track/HNHP240615001"

Nhắc nhở 2h trước (SMS):
"[XeGhep] Nhắc lịch: Chuyến Hà Nội → Hải Phòng lúc 06:00 hôm nay.
Tài xế: Nguyễn Văn Tài - 0923456789. Biển số: 30A-12345"

Xe đến (Push + SMS):
"[XeGhep] Tài xế đang đến điểm đón Mỹ Đình, ETA ~5 phút"

Hủy vé thành công:
"[XeGhep] Vé HNHP240615001 đã hủy. Hoàn tiền 150.000đ sẽ về ví trong 3-5 ngày làm việc"
```

---

### F-C08: Đánh giá
**Priority**: P1
**Acceptance Criteria**:
- [x] Hiển thị popup đánh giá sau khi chuyến completed (trong 24h)
- [x] Rating 1–5 sao cho: tài xế, xe, dịch vụ
- [x] Bình luận optional (max 500 ký tự)
- [x] Chỉ đánh giá được 1 lần/booking
- [x] Chỉ đánh giá được trong vòng 7 ngày sau chuyến

---

## 2. DRIVER FEATURES

### F-D01: Đăng ký tài xế
**Priority**: P0
**Acceptance Criteria**:
- [x] Đăng ký bằng SĐT + mật khẩu
- [x] Upload ảnh CMND mặt trước/sau
- [x] Upload ảnh GPLX mặt trước
- [x] Nhập số GPLX, hạng (B2/C/D/E), ngày hết hạn
- [x] Chọn nhà xe đang làm việc (operator_id)
- [x] Status = pending, chờ admin duyệt
- [x] Thông báo SMS khi được duyệt/từ chối

---

### F-D02: Xem lịch chạy
**Priority**: P0
**Acceptance Criteria**:
- [x] Tab "Hôm nay": danh sách chuyến ngày hiện tại
- [x] Tab "Sắp tới": 7 ngày tiếp theo
- [x] Tab "Lịch sử": chuyến đã hoàn thành
- [x] Mỗi chuyến hiển thị: giờ đi, tuyến, số khách, trạng thái

---

### F-D03: Thực hiện chuyến đi
**Priority**: P0
**Acceptance Criteria**:
- [x] Xem manifest: danh sách khách theo thứ tự điểm đón
- [x] Bấm "Bắt đầu chuyến" → trip status = boarding
- [x] Quét QR code hành khách để check-in
- [x] Mark khách là "No-show" nếu không lên sau 5 phút đợi
- [x] Bấm "Hoàn tất" khi tất cả khách đã được trả → trip = completed
- [x] Báo sự cố (tai nạn, hỏng xe, kẹt đường)

---

### F-D04: GPS Tracking
**Priority**: P0
**Acceptance Criteria**:
- [x] Tự động gửi vị trí mỗi 15 giây khi trip = in_progress
- [x] Dừng gửi khi trip = completed hoặc app ở background quá 5 phút
- [x] Battery-efficient: chỉ track khi đang chạy chuyến
- [x] Rate limit: 1 request / 10 giây (tránh spam server)

---

### F-D05: Thu nhập
**Priority**: P1
> ⚠️ ĐÃ ĐIỀU CHỈNH NGHIỆP VỤ (memory §4.7, Phương án A): tiền về NHÀ XE, nhà xe trả tài xế
> trực tiếp NGOÀI nền tảng. Nền tảng KHÔNG giữ ví tài xế ⇒ trang thu nhập CHỈ XEM (bảng kê),
> ĐÃ GỠ "rút tiền" và "số dư ví".
**Acceptance Criteria (thực tế):**
- [x] Xem thu nhập hôm nay, tuần này, tháng này (chỉ xem)
- [x] Biểu đồ doanh thu 7 ngày gần nhất
- [x] Lịch sử từng chuyến với số tiền ghi nhận
- [ ] ~~Số dư ví hiện tại~~ — ĐÃ GỠ (ví tài xế không dùng)
- [ ] ~~Yêu cầu rút tiền (tối thiểu 100.000đ)~~ — ĐÃ GỠ (route/controller/UI đều bỏ)

---

## 3. OPERATOR FEATURES

### F-O01: Đăng ký nhà xe
**Priority**: P0
**Acceptance Criteria**:
- [x] Đăng ký: tên công ty, số GPKD vận tải, MST, SĐT, email
- [x] Upload giấy phép kinh doanh vận tải
- [x] Nhập tài khoản ngân hàng nhận tiền
- [x] Status = pending, chờ admin duyệt

---

### F-O02: Quản lý tuyến
**Priority**: P0
**Acceptance Criteria**:
- [x] Tạo tuyến đường với tên, giá cơ bản
- [x] Thêm điểm dừng: tên, địa chỉ, tọa độ GPS, thứ tự, offset thời gian
- [x] Kích hoạt/tạm ngừng tuyến
- [x] Không xóa được tuyến đang có trip scheduled

---

### F-O03: Quản lý xe
**Priority**: P0
**Acceptance Criteria**:
- [x] Thêm xe: biển số, hãng, model, loại, số chỗ, năm SX
- [x] Upload ảnh xe
- [x] Cấu hình sơ đồ ghế (chọn template theo loại xe)
- [x] Đánh dấu ghế disabled (hỏng)
- [x] Nhắc nhở khi đăng kiểm sắp hết hạn (< 30 ngày)

---

### F-O04: Tạo lịch chạy
**Priority**: P0
**Acceptance Criteria**:
- [x] Tạo chuyến đơn: chọn tuyến, xe, tài xế, ngày giờ, giá
- [x] Tạo lịch cả tuần: chọn khung giờ + lặp theo ngày
- [x] Hủy chuyến với lý do (tự động hoàn tiền tất cả booking)
- [x] Chỉnh sửa chuyến chưa có booking
- [x] Xuất manifest PDF trước mỗi chuyến

---

### F-O05: Quản lý booking
**Priority**: P0
**Acceptance Criteria**:
- [x] Xem tất cả booking theo chuyến
- [x] Filter theo: ngày, tuyến, trạng thái
- [x] Xuất danh sách Excel
- [x] Xử lý hủy chuyến đột xuất → tự động hoàn tiền, gửi thông báo

---

### F-O06: Báo cáo doanh thu
**Priority**: P1
**Acceptance Criteria**:
- [x] Dashboard: tổng doanh thu, số chuyến, số khách, tỷ lệ lấp đầy
- [x] Báo cáo theo ngày/tuần/tháng
- [x] Doanh thu theo từng tuyến, từng tài xế
- [x] Yêu cầu quyết toán (2 lần/tháng: ngày 1 và ngày 15)

---

## 4. ADMIN FEATURES

### F-A01: Dashboard tổng quan
**Priority**: P0
**Acceptance Criteria**:
- [x] KPI real-time: booking hôm nay, doanh thu, xe đang chạy, user mới
- [x] Bản đồ vị trí tất cả xe đang chạy (Google Maps)
- [x] Cảnh báo: nhà xe/tài xế chờ duyệt, khiếu nại chưa xử lý

---

### F-A02: Duyệt hồ sơ
**Priority**: P0
**Acceptance Criteria**:
- [x] Xem ảnh GPLX, CMND của tài xế
- [x] Xem giấy phép kinh doanh của nhà xe
- [x] Duyệt / Từ chối với lý do
- [x] Gửi SMS thông báo kết quả
- [x] Lịch sử duyệt (ai duyệt, lúc nào)

---

### F-A03: Quản lý tài chính
**Priority**: P0
**Acceptance Criteria**:
- [x] Tổng doanh thu nền tảng (hoa hồng thu được)
- [x] Danh sách giao dịch với filter đầy đủ
- [x] Duyệt và thực hiện quyết toán cho nhà xe
- [x] Hoàn tiền thủ công khi cần
- [x] Phát hiện giao dịch bất thường (cùng SĐT đặt nhiều booking)

---

### F-A04: Quản lý voucher
**Priority**: P1
**Acceptance Criteria**:
- [x] Tạo voucher toàn sàn hoặc theo nhà xe
- [x] Giới hạn số lần dùng, thời hạn sử dụng
- [x] Xem ai đã dùng voucher
- [x] Tắt voucher tức thì

---

## 5. NON-FUNCTIONAL REQUIREMENTS

### Performance
```
API search trips     : < 300ms (P95)
API create booking   : < 1000ms (P95)
WebSocket latency    : < 500ms
Page load (FCP)      : < 2 giây trên 3G
```

### Security
```
- Tất cả API phải xác thực (trừ public routes)
- Webhook callback phải verify HMAC signature
- Upload file: chỉ chấp nhận jpg, png, pdf, max 10MB
- Rate limit: 60 requests/phút/IP cho public API
- Rate limit: 5 lần gửi OTP/giờ/SĐT
- SQL injection: dùng Eloquent/query builder (không raw SQL)
- XSS: escape tất cả output
```

### Reliability
```
- Uptime: 99.5% (downtime < 4h/tháng)
- Queue retry: 3 lần, delay 5 phút/lần
- Database backup: daily
- Idempotent webhook: không xử lý 2 lần cùng 1 payment
```

### Scalability (future)
```
- Thiết kế để horizontal scale được
- Không lưu state trên server (session trên Redis)
- Queue xử lý notification async
```