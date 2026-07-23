# Xử lý tài xế không chạy được chuyến — Design Spec (v3, sau review 2)

> Trạng thái: chờ review v3. Phạm vi: backend (Laravel). Chưa sinh code.
> v3 tiếp thu review 2: actor identity kiểm trong transaction cho mọi transition; chính sách payment in-flight (initiate chặn mới, callback xử lý an toàn); dedupe_key notification theo incident/event; cancelTrip xóa cờ + đóng incident; FK incident rõ ràng.

## 1. Bối cảnh & vấn đề

Khi một chuyến sắp khởi hành mà tài xế gặp sự cố không thể chạy, hệ thống hiện **không có cách xử lý phù hợp**:

- Tài xế chỉ có `start`/`complete`, check-in khách, bật/tắt `is_online`. Không có cách báo "tôi không chạy được chuyến X".
- Nhà xe chỉ có `create`/`cancel`/`complete`. **Không có** endpoint đổi tài xế cho chuyến đã tạo.
- Hệ quả: công cụ duy nhất khi tài xế rớt là **hủy nguyên chuyến** → hoàn 100% + bồi thường 20.000đ cho **mọi** khách, dù đáng lẽ chỉ cần thay tài xế là chuyến vẫn chạy.

## 2. Mục tiêu / Phi mục tiêu

**Mục tiêu (V1):**
- Tài xế tự báo "không chạy được chuyến X" kèm lý do — chỉ khi còn **> 15 phút** trước giờ khởi hành.
- Chuyến vào trạng thái "chờ sắp xếp lại": **ngừng bán vé mới + chặn khởi tạo thanh toán mới + chặn tài xế start** + thông báo nhà xe & hành khách đã đặt (callback thanh toán in-flight vẫn xử lý an toàn — §6.3).
- Nhà xe **đổi tài xế** (ràng buộc thiết yếu) — chỉ **trước giờ khởi hành** — giữ nguyên xe/ghế đã đặt → khách vẫn đi, không hoàn tiền.
- **Ghi nhật ký sự cố** (bảng `trip_driver_incidents`) phục vụ khiếu nại/KPI/đối soát.
- Fallback: nhà xe **hủy chuyến** (luồng sẵn có), đóng incident đang mở.

**Phi mục tiêu (để sau):**
- **Đổi xe** (breakdown xe): seat_map/ghế gắn theo xe gốc; đổi xe khác số ghế sẽ lệch chỗ. V1 chỉ đổi tài xế.
- Tự động gợi ý tài xế thay thế; cơ chế "tài xế xác nhận nhận chuyến".

## 3. Quyết định thiết kế (chốt sau review)

| # | Quyết định |
|---|---|
| Hướng xử lý chính | Ưu tiên **thay tài xế**, giữ chuyến; chỉ hủy khi không có ai thay |
| Phía tài xế | Tài xế **tự báo trong app** (lý do + thời điểm), chịu cutoff **15 phút** trước `depart_at` |
| Phía nhà xe | Reassign **không** chịu cutoff 15', nhưng **bắt buộc `depart_at > now()`** (không đổi sau giờ chạy) |
| Mức kiểm tra khi đổi tài xế | Luật **thiết yếu**: thuộc nhà xe + `verified` + còn hoạt động (chưa xóa mềm, GPLX chưa hết hạn) + không trùng khung giờ. Bỏ lead-time & buffer địa lý liên hoàn |
| Lúc chờ sắp xếp | Ngừng bán vé mới + chặn khởi tạo thanh toán mới + chặn start + báo khách |
| Nhật ký sự cố | **Có trong V1** — bảng `trip_driver_incidents` |
| Notification type | **Thêm 2 case mới** `TripDriverUnavailable`, `TripDriverReassigned` (không tái dùng `System`) |
| Biểu diễn trạng thái | **Cờ** trên trip cho query nhanh + bảng incident cho lịch sử |
| Chống race | Mọi transition Trip **đọc lại + `lockForUpdate()` trong transaction**; **actor identity (driverId/operatorId) truyền vào service và kiểm ownership SAU khi khóa**; reassign khóa cả record tài xế mới |
| Payment in-flight | `initiate()` **chặn khởi tạo thanh toán MỚI** khi có cờ; callback/webhook hợp lệ phát sinh **trước** incident vẫn xử lý an toàn (ghi Paid+Confirmed, giữ ghế, báo khách chuyến đang đổi tài xế) — **không làm mất tiền khách, không tự hoàn** |

## 4. Data model

### 4.1 Cột cờ trên `trips` (migration mới)
| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `driver_unavailable_at` | `timestamp` nullable | `NOT NULL` = đang chờ sắp xếp lại; `NULL` = bình thường |
| `driver_unavailable_reason` | `string(255)` nullable | Lý do tài xế nêu (bản sao nhanh của incident đang mở) |

- `Trip::$fillable` thêm 2 cột; `casts()` thêm `driver_unavailable_at => 'datetime'`.
- Cờ là **trạng thái hiện tại** để query nhanh; **không** thay thế nhật ký (bảng dưới).

### 4.2 Bảng nhật ký `trip_driver_incidents` (migration mới)
| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | uuid PK | |
| `trip_id` | uuid FK→trips cascade | |
| `reported_driver_id` | uuid FK→drivers | tài xế báo nghỉ |
| `reason` | string(255) | |
| `reported_at` | timestamp | thời điểm báo |
| `status` | string(20) | `open` \| `resolved` |
| `resolution` | string(20) nullable | `reassigned` \| `cancelled` (null khi còn open) |
| `replacement_driver_id` | uuid FK→drivers nullable | tài xế thay (khi reassigned) |
| `resolved_by_user_id` | uuid FK→users nullable | operator/actor xử lý |
| `resolved_at` | timestamp nullable | |
| timestamps | | |

- Index: `(trip_id, status)` để tìm incident đang mở nhanh.
- Một chuyến có thể có **nhiều** incident theo thời gian (đổi tài xế nhiều lần) → đếm được số lần đổi.
- Bất biến: tại một thời điểm mỗi trip có **tối đa 1 incident `open`** (đảm bảo bằng khóa + kiểm tra trong transaction).
- **Khóa ngoại (chốt theo review):**
  - `trip_id` → `cascadeOnDelete` (xóa trip thì xóa incident).
  - `reported_driver_id` → `restrictOnDelete` (giữ record, không cho xóa tài xế đang có incident — bảo toàn bằng chứng).
  - `replacement_driver_id`, `resolved_by_user_id` → `nullable` + `nullOnDelete`.

### 4.3 Cột `dedupe_key` trên `notifications` (migration mới)
- Thêm `dedupe_key` `string(191)` nullable + **UNIQUE index**.
- Chống gửi trùng khi job retry **ở tầng DB** (unique constraint), không dựa `exists()`+`create()` (vẫn race).
- Notification thường (không từ 2 event này) để `dedupe_key = null` (MySQL cho phép nhiều NULL trong unique index).

### 4.4 Enum & config
- `NotificationTypeEnum` thêm: `TripDriverUnavailable`, `TripDriverReassigned`.
- `config/booking.php` thêm `driver_report_cutoff_minutes = env('DRIVER_REPORT_CUTOFF_MINUTES', 15)`.

## 5. Trạng thái & luồng nghiệp vụ

### 5.1 Sơ đồ (cờ + incident chồng lên status hiện có)
```
[scheduled|boarding]  ──report (tài xế, >15', depart>now)──►  [scheduled|boarding] + CỜ + incident(open)
        ▲                                                                  │
        │ reassign (operator, depart>now): đổi driver, xóa cờ,             │
        │           incident→resolved(reassigned)                          │
        └───────────────────────────────────────────────────────────────  │
                                                                           │ cancel (operator/auto-resolve):
                                                                           │   incident→resolved(cancelled)
                                                                           ▼
                                                                     [cancelled]
```
- Cờ **không** đổi `status`. `in_progress`/`completed`/`cancelled` không nhận report/reassign.
- **`startTrip` bị chặn khi cờ `!= null`** (mục 6.4) — không cho chuyển in_progress khi đang chờ sắp xếp.

### 5.2 Luồng chính
1. **Tài xế báo nghỉ** → khóa trip, kiểm R1–R5 trong transaction → set cờ + tạo `incident(open)` → (sau commit) event → ẩn khỏi tìm kiếm, chặn vé mới + chặn khởi tạo thanh toán mới + chặn start, báo nhà xe + khách.
2. **Nhà xe đổi tài xế** → khóa trip + khóa record tài xế mới, kiểm A1–A7 trong transaction → `driver_id = new`, xóa cờ, incident đang mở → `resolved(reassigned)` → (sau commit) event → chuyến bình thường trở lại, báo khách + tài xế mới + tài xế cũ.
3. **Hủy (fallback)** → `cancelTrip` sẵn có + đóng incident đang mở thành `resolved(cancelled)`.

## 6. Business rules & validations

> **Nguyên tắc chung (chống race):** mọi method transition (`report`, `reassign`, `startTrip`, `completeTrip`, `cancelTrip`) **nhận `tripId` + actor identity** (`driverId`/`operatorId`/`actorUserId`, không nhận model đọc sẵn), mở transaction, `Trip::whereKey($id)->lockForUpdate()->firstOrFail()`, rồi **kiểm lại toàn bộ** ownership + status + cutoff + cờ **bên trong** transaction. **Controller chỉ lấy actor từ auth và truyền id xuống — KHÔNG kiểm ownership ở controller** (tránh check ngoài khóa rồi state đổi giữa chừng).

### 6.1 `reportDriverUnavailable` (mọi kiểm tra trong transaction sau khi khóa trip)
- **R1** Sau khi khóa, `trip.driver_id === driverId` (actor) → nếu không: 404 (không tiết lộ chuyến người khác).
- **R2** `status ∈ {scheduled, boarding}` → nếu không: 422 `TRIP_NOT_REPORTABLE`.
- **R3** `depart_at > now() + driver_report_cutoff_minutes` → nếu trễ hơn: 422 `REPORT_CUTOFF_PASSED` ("còn dưới 15 phút, vui lòng liên hệ nhà xe").
- **R4** Cờ đang null (chưa có incident open) → nếu đã có: **idempotent** 200 (không tạo incident/event lần 2).
- **R5** `reason` bắt buộc, 1..255 ký tự (validate ở FormRequest).

### 6.2 `reassignDriver` (mọi kiểm tra trong transaction sau khi khóa trip + khóa driver mới)
- **A1** Sau khi khóa, trip thuộc nhà xe (`operatorId` actor) → nếu không: 404.
- **A2** `status ∈ {scheduled, boarding}` → nếu không: 422 `TRIP_NOT_REASSIGNABLE`.
- **A3** `depart_at > now()` → nếu đã tới/quá giờ: 422 `TRIP_ALREADY_DEPARTED` ("không thể đổi tài xế sau giờ khởi hành"). *(Chặn kẽ hở 2h grace của auto-resolve.)*
- **A4** Tài xế mới tồn tại, `operator_id` = nhà xe, **chưa xóa mềm**, tài khoản còn hoạt động → nếu không: 422 `DRIVER_NOT_IN_OPERATOR`.
- **A5** Tài xế mới `status = verified` **và GPLX chưa hết hạn** (`license_expiry >= today`) → nếu không: 422 `DRIVER_NOT_ELIGIBLE`.
- **A6** Tài xế mới ≠ tài xế hiện tại → nếu trùng: 422 `DRIVER_UNCHANGED`.
- **A7** Tài xế mới **không trùng khung giờ**: sau khi `lockForUpdate` record tài xế mới, không tồn tại trip khác (`whereKeyNot($trip->id)`, `status ∈ {scheduled,boarding,in_progress}`) mà `depart_at < this.arrive_at AND arrive_at > this.depart_at` → nếu trùng: 422 `DRIVER_SCHEDULE_CONFLICT`.
- **Cố ý bỏ:** lead-time tối thiểu, buffer 30', liên hoàn địa lý — vì tình huống khẩn cấp sát giờ.
- **Khóa tài xế mới trước khi kiểm A7**: serialize hai reassign đồng thời cùng gán một tài xế (tránh cả hai cùng thấy "không trùng").

### 6.3 Chặn bán vé & chính sách thanh toán khi có cờ  🔴 (chốt theo review 2)
Phân biệt **khởi tạo thanh toán mới** vs **callback/webhook của giao dịch đã bắt đầu trước incident**:

- **Bán vé mới:** `Trip::scopeAvailable()` + `Trip::canBeBooked()` thêm `driver_unavailable_at IS NULL` → chặn tìm kiếm public + `BookingService::create`/`lockSeats`.
- **Khởi tạo thanh toán MỚI — chặn:** `PaymentService::initiate()` **khóa booking + trip**, nếu `trip.driver_unavailable_at != null` → 422 `TRIP_AWAITING_REASSIGNMENT` ("chuyến đang sắp xếp lại tài xế, tạm chưa thanh toán"). Áp cho cả wallet/cash (đều đi qua `initiate`).
- **Callback/webhook đã in-flight — KHÔNG chặn, xử lý an toàn:** `PaymentService::processCallback()` **khóa payment + booking + trip**. Nếu cổng đã xác nhận tiền (khách bấm thanh toán MoMo/SePay TRƯỚC khi cờ được set, webhook đến sau):
  - **Vẫn ghi nhận giao dịch thành công** → `payment=Success`, `booking = Paid + Confirmed`, **giữ ghế**. Không tự hoàn, không làm mất tiền khách (đúng mục tiêu "giữ nguyên vé đã đặt").
  - Nếu tại thời điểm callback trip **đang có cờ** → gửi cho chính khách này thông báo `TripDriverUnavailable` ("chuyến đang được sắp xếp lại tài xế") vì họ confirm sau khi event hàng loạt đã phát.
- **Diễn đạt đúng:** "không cho **khởi tạo** thanh toán mới khi có cờ", KHÔNG phải "chặn mọi thanh toán pending". Vé/ghế đã đặt giữ nguyên, không giải phóng.

### 6.4 Chặn `startTrip` khi có cờ + kiểm actor  🔴 (chốt theo review 2)
- `startTrip(string $tripId, string $driverId)` — guard **trong service transition**, không chỉ controller:
  - Sau khi `lockForUpdate` trip: **kiểm `trip.driver_id === driverId`** → nếu không: `UnauthorizedActionException` → 404. *(Ngăn: tài xế cũ vượt ownership ở controller, operator reassign xong, request start cũ vẫn khóa trip và start thay cho tài xế mới.)*
  - Nếu `driver_unavailable_at != null` → `TripAwaitingReassignmentException` → 422 `TRIP_AWAITING_REASSIGNMENT`.
- `completeTrip(string $tripId, string $driverId)` — tương tự: kiểm `trip.driver_id === driverId` trong transaction sau khóa.
- `cancelTrip(string $tripId, ?string $operatorId, ?string $actorUserId, string $reason, bool $compensate)` — actor nullable: operator truyền `operatorId` (kiểm ownership trong txn); auto-resolve/system truyền `null` (bỏ qua ownership).

### 6.5 `cancelTrip` xóa cờ + đóng incident  🟠 (chốt theo review 2)
Trong cùng transaction hủy chuyến, **BẮT BUỘC** cả hai để tránh dữ liệu mâu thuẫn (trip `cancelled` mà vẫn `is_awaiting_reassignment=true`):
- Xóa cờ: `driver_unavailable_at = null`, `driver_unavailable_reason = null`.
- Đóng incident đang mở (nếu có): `status=resolved`, `resolution=cancelled`, `resolved_at=now`, `resolved_by_user_id = actorUserId` (null khi auto-resolve/system).
- Áp cho mọi lối gọi `cancelTrip` (operator, admin, auto-resolve).

## 7. API contracts

### 7.1 Driver — báo không chạy được
```
POST /api/driver/trips/{id}/report-unavailable   (auth:sanctum + role:driver)
FormRequest: ReportDriverUnavailableRequest { reason: string 1..255 required }
200 { success:true, message:"Đã báo nghỉ chuyến. Nhà xe sẽ sắp xếp lại." }
200 (idempotent) nếu đã báo trước đó (R4)
404 chuyến không thuộc tài xế (R1)
422 code ∈ { TRIP_NOT_REPORTABLE (R2), REPORT_CUTOFF_PASSED (R3) }
```

### 7.2 Operator — đổi tài xế
```
POST /api/operator/trips/{id}/reassign-driver   (auth:sanctum + role:operator)
FormRequest: ReassignDriverRequest { driver_id: uuid required }
  — CHỈ validate định dạng UUID, KHÔNG dùng exists:drivers,id (tránh lộ khác biệt
    "không tồn tại" vs "khác nhà xe"). Service trả lỗi nghiệp vụ A4 thống nhất.
200 { success:true, message:"Đã đổi tài xế cho chuyến.", data:<trip tóm tắt> }
404 chuyến không thuộc nhà xe (A1)
422 code ∈ { TRIP_NOT_REASSIGNABLE (A2), TRIP_ALREADY_DEPARTED (A3),
            DRIVER_NOT_IN_OPERATOR (A4), DRIVER_NOT_ELIGIBLE (A5),
            DRIVER_UNCHANGED (A6), DRIVER_SCHEDULE_CONFLICT (A7) }
```

### 7.3 Fallback hủy — dùng `POST /api/operator/trips/{id}/cancel` sẵn có (nay đóng incident).

### 7.4 Resource bổ sung
- **Operator trip resource** (`index`/`show`): `is_awaiting_reassignment` (bool), `driver_unavailable_reason`, `driver_unavailable_at`.
- **Driver trip resource**: `is_awaiting_reassignment` (bool) — để app tài xế phản ánh "đã báo nghỉ" (tránh báo lại/hiểu nhầm).

## 8. Service & Request layer

- FormRequest riêng: `ReportDriverUnavailableRequest`, `ReassignDriverRequest` (không validate thẳng trong controller).
- `TripService` (mọi method: transaction → lock trip → kiểm actor+điều kiện → mutate → dispatch event **sau commit**):
  - `reportDriverUnavailable(string $tripId, string $driverId, string $reason): void` — R1..R5; set cờ + incident(open).
  - `reassignDriver(string $tripId, string $operatorId, string $newDriverId, string $actorUserId): Trip` — A1..A3 → lock driver mới → A4..A7; `driver_id=new`, xóa cờ, incident open→resolved(reassigned, replacement, resolved_by, resolved_at).
  - `startTrip(string $tripId, string $driverId): void` — kiểm ownership + guard cờ (6.4).
  - `completeTrip(string $tripId, string $driverId): void` — kiểm ownership (6.4).
  - `cancelTrip(string $tripId, ?string $operatorId, ?string $actorUserId, string $reason, bool $compensate = true): void` — xóa cờ + đóng incident (6.5); actor nullable cho system.
- **Đổi chữ ký là breaking** cho caller hiện tại (Driver/Operator/Admin TripController, AutoResolveTripsCommand) → plan phải cập nhật hết. Controller chỉ lấy actor id từ `auth()` rồi truyền xuống.
- Không chứa logic thanh toán/ghế (giữ nguyên tắc phân tách).

## 9. Events & Notifications  🟠

- **Dispatch event CHỈ sau khi transaction commit** (`DB::afterCommit` hoặc `event()` gọi ngoài `DB::transaction`) — tránh gửi khi rollback.
- **Listener gửi hàng loạt cho hành khách = queued** (`ShouldQueue`, queue `notifications`) — không chặn request.
- **Idempotency đúng phạm vi** 🟠 (chốt theo review 2): mỗi event mang một **`event_id` UUID** sinh khi tạo event (ổn định qua các lần job retry). `dedupe_key = "{event_id}:{user_id}:{type}:{channel}"`, ghi vào cột `notifications.dedupe_key` (UNIQUE, §4.3). Chống trùng bằng **unique constraint ở DB** (bắt lỗi trùng → bỏ qua), KHÔNG dùng `exists()`+`create()`.
  - KHÔNG dùng key `trip_id + user_id + type`: một trip có nhiều incident (đổi tài xế nhiều lần) — incident thứ 2 sẽ bị coi là trùng incident đầu và **mất thông báo**. `event_id` riêng từng lần phát bảo đảm incident/reassign sau vẫn gửi.
- **Đối tượng nhận** (chốt theo review):
  - Hành khách: booking `status ∈ {confirmed, checked_in}` (đã cam kết chỗ). **Không** gửi cho `pending` (chưa thanh toán, tạm; và 6.3 đã chặn họ confirm lên chuyến này), `cancelled`/`no_show`/`completed`.
  - `TripDriverUnavailableEvent(eventId, tripId, reason, reportedDriverId)`:
    - → nhà xe (operator user): "Tài xế {tên} báo không chạy được chuyến {tuyến} {giờ} — lý do: {reason}. Cần sắp xếp lại."
    - → hành khách (confirmed/checked_in): "Chuyến {tuyến} {giờ} đang được sắp xếp lại tài xế. Sẽ cập nhật sớm."
    - *(Cũng phát cho 1 khách lẻ khi callback thanh toán in-flight confirm vé lên chuyến đang có cờ — §6.3.)*
  - `TripDriverReassignedEvent(eventId, tripId, oldDriverId, newDriverId)`:
    - → hành khách: "Chuyến {tuyến} {giờ} đã sắp xếp tài xế và chạy bình thường."
    - → tài xế mới: "Bạn được phân công chuyến {tuyến} {giờ}."
    - → **tài xế cũ**: "Chuyến {tuyến} {giờ} đã được chuyển cho tài xế khác." *(bổ sung theo review)*
- Kênh theo `NotificationChannelEnum` hiện dùng (in-app; realtime nếu bật). Không thêm kênh mới.

## 10. Edge cases & concurrency

| Tình huống | Xử lý |
|---|---|
| Báo 2 lần | Idempotent (R4) — không tạo incident/event lần 2 |
| Báo < 15' trước giờ | 422 `REPORT_CUTOFF_PASSED` |
| Báo khi in_progress/kết thúc/hủy | 422 `TRIP_NOT_REPORTABLE` |
| **report ‖ start (tài xế cũ) đồng thời** | Cùng khóa trip. start trước → in_progress → report thấy R2 fail. report trước → cờ set → start thấy guard 6.4 fail |
| **start (tài xế cũ) ‖ reassign đồng thời** | Khóa trip serialize. reassign trước → `driver_id` đổi sang tài xế mới → start của tài xế cũ đọc lại trong txn thấy `trip.driver_id !== driverId` → **404** (không start thay tài xế mới). start trước → in_progress → reassign thấy A2 fail |
| **report ‖ reassign đồng thời** | Khóa trip serialize. reassign trước (đổi driver + xóa cờ) → report của tài xế cũ đọc lại thấy `trip.driver_id !== driverId` → **404** (ownership fail, KHÔNG phải "luôn nhất quán bất kể thứ tự"). report trước → cờ set + incident open → reassign đóng incident, xóa cờ |
| **reassign ‖ cancel/auto-resolve** | Khóa trip serialize: cancel trước → reassign thấy A2/A3 fail; reassign trước → cancel đóng incident đã resolved thì bỏ qua |
| **2 reassign cùng 1 tài xế mới, 2 chuyến khác nhau** | Khóa record tài xế mới trước khi kiểm A7 → serialize; người sau thấy `DRIVER_SCHEDULE_CONFLICT` |
| **payment callback đến sau khi có cờ** | Không chặn: ghi Paid+Confirmed, giữ ghế, gửi `TripDriverUnavailable` cho khách đó (§6.3). Không tự hoàn |
| **incident thứ 2 trên cùng trip** | `event_id` riêng → notification incident 2 KHÔNG bị dedupe nhầm với incident 1 (§9) |
| reassign sau giờ chạy (trong 2h grace) | 422 `TRIP_ALREADY_DEPARTED` (A3) |
| reassign chuyến chưa có cờ | Cho phép (đổi chủ động); nếu có incident open thì đóng, nếu không thì không tạo incident |
| reassign về tài xế cũ | 422 `DRIVER_UNCHANGED` |
| cancel chuyến đang có cờ | Xóa cờ + đóng incident(cancelled) trong cùng txn (6.5) — không để `is_awaiting_reassignment` treo trên chuyến đã hủy |
| Chuyến có cờ nhưng tới giờ chưa xử lý | `trips:auto-resolve` (2h grace) hủy + hoàn tiền + xóa cờ + đóng incident(cancelled). Không thêm timer mới |
| Vé pending khi có cờ | Không giải phóng ghế; khởi tạo thanh toán mới bị chặn (6.3); auto-expire 15' hoặc auto-resolve xử lý |

## 11. Quyết định đã chốt (từ review)
1. **Nhật ký sự cố**: **CÓ trong V1** — bảng `trip_driver_incidents` (§4.2). Cờ trên trips giữ làm trạng thái nhanh.
2. **NotificationType**: **thêm 2 case mới** `TripDriverUnavailable`, `TripDriverReassigned`.
3. **Cutoff**: tài xế chịu 15'; operator **không** chịu 15' nhưng **chỉ reassign khi `depart_at > now()`** (A3).

## 12. Testing strategy (khi lên plan)

**Feature (Pest, sqlite):**
- Driver report: happy (cờ set + incident open + event) · in_progress → 422 · không phải chuyến mình → 404 · báo 2 lần idempotent (1 incident).
- **Boundary cutoff**: `depart_at = now + 15'` (biên), `+15'1s` (được báo), `+14'59s` (bị chặn) — chốt dấu so sánh chính xác.
- Operator reassign: happy (đổi driver + xóa cờ + incident resolved(reassigned) + event) · khác nhà xe → 422 · chưa verified/ GPLX hết hạn → 422 · **`depart_at <= now` → 422** · trùng lịch → 422 · trùng tài xế cũ → 422 · reassign chuyến chưa cờ vẫn được.
- startTrip guard: chuyến có cờ → 422 `TRIP_AWAITING_REASSIGNMENT` (test cả ở service); **start bởi tài xế không phải chủ chuyến → 404** (ownership trong txn).
- Booking guard: chuyến có cờ bị loại khỏi `scopeAvailable`/search; `BookingService::create` từ chối; **`PaymentService::initiate` từ chối khởi tạo thanh toán mới** (422); sau reassign đặt/thanh toán lại được.
- **Payment callback đến sau incident**: `processCallback` vẫn ghi Paid+Confirmed, giữ ghế, và gửi `TripDriverUnavailable` cho khách đó — không tự hoàn.
- **cancelTrip xóa cờ + đóng incident**: sau cancel, `driver_unavailable_at = null` và incident `resolution=cancelled` (không còn `is_awaiting_reassignment`).
- **Incident thứ 2 vẫn gửi notification**: report → reassign → report lần 2 (tài xế mới) → khách nhận đủ, không bị dedupe nuốt.
- Notification: gửi đúng confirmed+checked_in, không gửi pending; tài xế cũ nhận tin khi reassigned.
- Regression: cancelTrip/auto-resolve hành vi cũ không đổi (ngoài việc nay xóa cờ/đóng incident).

**Concurrency (integration, ưu tiên chạy trên MySQL):**
- report ‖ start (tài xế cũ) · **reassign ‖ start (tài xế cũ) → start trả 404** · reassign ‖ cancel · **reassign trước rồi report tài xế cũ chạy sau → 404** · 2×reassign cùng tài xế → chỉ 1 thắng, trạng thái cuối nhất quán, không có 2 incident open, không gán trùng lịch.
- *(Ghi chú: sqlite in-memory không mô phỏng tốt lock đồng thời; test race chạy ở tầng MySQL như `MySqlSpatialIntegrationTest`, có thể cần harness chạy 2 tiến trình/transaction song song.)*
