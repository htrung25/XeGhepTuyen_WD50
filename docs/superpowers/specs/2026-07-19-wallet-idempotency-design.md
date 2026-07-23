# Idempotency cấp DB cho giao dịch ví (chống hoàn tiền trùng) — Task Spec (v2, đã chốt)

> Trạng thái: quyết định đã chốt (§7). Phạm vi: backend. Chưa sinh code.
> Nguyên tắc xuyên suốt: **đây là tiền thật** — không xóa dữ liệu tự động, audit trước khi ràng buộc, mọi ngoại lệ do người quyết.

## 1. Vấn đề

Redis queue là **at-least-once**: job có thể được giao lại (worker chết giữa chừng, `retry_after` hết hạn khi job vẫn chạy, `tries=3`). Vì vậy `ProcessRefundJob` **phải** chịu được duplicate delivery.

Hiện trạng đã kiểm chứng trong code:

| Lớp bảo vệ | Trạng thái |
|---|---|
| Guard trạng thái `payment->isSuccessful()` | Có — nhưng chỉ chặn khi lần chạy trước **đã commit** |
| `lockForUpdate()` hàng payment trong `refund()` | **Đã có** (thêm ở đợt sửa queue) — serialize 2 luồng cùng booking |
| Ràng buộc DB chống ghi trùng | **KHÔNG có** — `wallet_transactions` không có unique nào ngoài PK |
| Idempotency key nghiệp vụ ở `Wallet::credit()` | **KHÔNG có** — mọi caller đều có thể cộng ví nhiều lần |

**Vì sao lock chưa đủ:** lock chỉ bảo vệ khi cả hai luồng đi qua đúng đoạn code đó, cùng một DB, và không bị refactor gỡ mất. Nó không bảo vệ trước: caller khác gọi thẳng `walletService->credit()`, job chạy trên hai deploy khác phiên bản, hoặc lock bị bỏ khi tối ưu sau này. Đây là **tiền thật** ⇒ cần bảo đảm ở tầng cấu trúc (constraint), lock chỉ là lớp phụ.

## 2. Chặn quan trọng với đề xuất `unique(booking_id, transaction_type)`

`WalletService::credit()` **hardcode** `WalletTransactionTypeEnum::Refund` cho mọi khoản cộng. Trong `BookingService::cancelByOperator()`, cùng một `booking_id` sinh **hai giao dịch hợp lệ đều mang type `refund`**:

1. Tiền hoàn vé (qua `ProcessRefundJob` → `PaymentService::refund`)
2. Bồi thường 20.000đ (gọi trực tiếp `walletService->credit`)

⇒ Unique `(booking_id, type)` sẽ **chặn nhầm** cặp này: hoặc bồi thường trượt, hoặc hoàn tiền trượt. Không dùng được nguyên trạng.

**Hai hướng xử lý (cần bạn chọn — §7):**

- **(A) Tách type**: thêm case `compensation` vào `WalletTransactionTypeEnum` + mở rộng cột ENUM, rồi unique `(booking_id, type)`. Ưu: đúng ngữ nghĩa kế toán, báo cáo phân biệt được bồi thường vs hoàn tiền. Nhược: vẫn cứng — không đỡ được trường hợp hợp lệ cần nhiều giao dịch cùng type/booking (hoàn tiền từng phần sau này).
- **(B) `idempotency_key` tường minh** *(khuyến nghị)*: thêm cột `idempotency_key` nullable + UNIQUE. Caller tự quyết định độ mịn: `"refund:{booking_id}"`, `"compensation:{booking_id}"`, `"topup:{payment_id}"`. Ưu: linh hoạt, phủ mọi loại giao dịch, không phải phân loại lại enum. Nhược: caller phải nhớ truyền key (giảm rủi ro bằng cách bắt buộc tham số ở chữ ký hàm).

Có thể làm **cả hai**: (B) là cơ chế chống trùng, (A) là cải thiện ngữ nghĩa báo cáo — nhưng (A) không bắt buộc để đóng lỗ hổng.

## 3. Phát hiện thêm khi khảo sát (nằm trong phạm vi task)

**3.1 `balance_after` có thể sai dưới đồng thời.** `Wallet::credit()` làm `increment('balance')` (atomic ở SQL) rồi `refresh()` để lấy `balance_after`. Hai credit song song lên cùng ví: tổng số dư đúng, nhưng `balance_after` của từng dòng có thể phản ánh cả phần của giao dịch kia ⇒ sổ ví đọc lên bị lệch/nhảy cóc. Cần **khóa hàng wallet** (`lockForUpdate`) ở đầu `credit()`/`debit()` để chuỗi ghi và `balance_after` nhất quán.

**3.2 Duplicate phải là no-op, không được làm job fail.** Nếu unique violation ném ra ngoài, `ProcessRefundJob` (tries=3) sẽ retry rồi vào `failed()` → log lỗi giả, và trong `PaymentService::refund()` sẽ **rollback cả việc đánh dấu payment `refunded`** ⇒ kẹt vòng lặp. Xử lý: bắt vi phạm unique → coi như "đã hoàn rồi" → tiếp tục đánh dấu trạng thái, trả về bình thường.

**3.3 Transaction lồng.** `Wallet::credit()` mở `DB::transaction` riêng, khi gọi từ trong transaction của `refund()` sẽ thành savepoint. Thiết kế phải chỉ rõ điểm bắt lỗi unique nằm ở đâu để không làm vỡ transaction cha.

**3.4 `debit()` cũng cùng lớp rủi ro.** Thanh toán bằng ví (`initiateWallet`) chạy trong request HTTP nên ít lộ hơn, nhưng double-submit vẫn có thể trừ hai lần. Nên áp cùng cơ chế key.

## 4. Phạm vi đề xuất (V1)

**Trong phạm vi:**
- Audit + phân loại + backfill dữ liệu production (bước 1–6 của §7bis).
- Cột `idempotency_key` + UNIQUE index trên `wallet_transactions`.
- `Wallet::credit()/debit()` + `WalletService` nhận key; khóa hàng wallet; duplicate → no-op trả giao dịch đã có.
- Gắn key cho **cả 3 điểm credit lẫn debit**: hoàn tiền vé, bồi thường hủy chuyến, **thanh toán bằng ví**.
- `PaymentService::refund()` xử lý duplicate như "đã hoàn" (vẫn đảm bảo payment/booking về trạng thái `refunded`).

**Ngoài phạm vi (làm SAU khi V1 xong):**
- **(A) Tách `compensation` khỏi type `refund`** — đã chốt là sẽ làm, nhưng ở đợt sau: thêm case enum + mở rộng cột ENUM + backfill phân loại lại dòng bồi thường cũ. Không phải điều kiện để chống trùng.
- Hoàn tiền từng phần nhiều lần.
- `ShouldBeUnique` cho `ProcessRefundJob`: chỉ giảm xác suất trùng ở tầng queue, **không** thay thế constraint (lock nhả sau khi job xong, redelivery sau đó vẫn qua được).

## 5. Data model

Thêm vào `wallet_transactions`:

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `idempotency_key` | `string(191)` **nullable**, **UNIQUE** | Khóa nghiệp vụ do caller sinh, tất định |

- Nullable để **dữ liệu lịch sử giữ nguyên** (MySQL cho phép nhiều NULL trong unique index) — không cần backfill, không rủi ro migration vỡ vì trùng dữ liệu cũ.
- Quy ước đặt key: `"{mục đích}:{định danh gốc}"` — `refund:{booking_id}`, `compensation:{booking_id}`, `wallet_payment:{booking_id}`, `topup:{payment_id}`.

## 6. Luồng sau khi sửa

```
ProcessRefundJob (có thể được giao 2 lần)
        ↓
PaymentService::refund()  — transaction + lockForUpdate(payment)
        ↓
WalletService::credit(key="refund:{booking_id}")
        ↓
Wallet::credit()  — lockForUpdate(wallet) → increment → insert transaction
        ↓
   ┌─ insert OK        → trả transaction mới
   └─ UNIQUE violation → KHÔNG cộng tiền, trả transaction đã tồn tại (no-op)
        ↓
payment→refunded, booking→refunded  (luôn đạt trạng thái cuối đúng)
```

Bất biến cần giữ: **một `idempotency_key` ⇒ tối đa một dòng `wallet_transactions` ⇒ tiền chỉ cộng/trừ một lần**, bất kể job được giao bao nhiêu lần.

## 7. Quyết định đã chốt

1. **(B) `idempotency_key`** cho V1. **(A) tách type `compensation`** làm **sau khi B xong** (cải thiện ngữ nghĩa kế toán/báo cáo, không phải điều kiện chống trùng).
2. **Bất biến nghiệp vụ**: mỗi booking chỉ nhận **một** khoản bồi thường do nhà xe hủy chuyến ⇒ key `compensation:{booking_id}`.
3. **Áp key cho cả `debit`** (thanh toán bằng ví) trong V1, không chỉ credit.
4. **Audit production là bước BẮT BUỘC** về tài chính, thực hiện trước khi thêm constraint.

## 7bis. Trình tự triển khai bắt buộc (theo thứ tự, không đảo)

| # | Bước | Tính chất |
|---|---|---|
| 1 | **Audit production chỉ đọc** — thống kê ứng viên trùng trên `wallet_transactions` | Read-only |
| 2 | **Phân loại** duplicate thật vs giao dịch hợp lệ (refund + compensation cùng booking là HỢP LỆ). **Không xóa tự động** | Người quyết |
| 3 | **Chốt bất biến nghiệp vụ + format key** (§5) trước khi ghi bất cứ thứ gì | Thiết kế |
| 4 | Thêm cột `idempotency_key` **nullable**, **chưa** unique | Migration |
| 5 | **Backfill** key cho các dòng xác định chắc chắn (deterministic) | Migration/command, idempotent |
| 6 | **Xử lý thủ công** các ngoại lệ backfill không tự quyết được (trùng thật, dữ liệu mập mờ) | Người quyết |
| 7 | Thêm **UNIQUE index** — chỉ khi bước 5–6 đã sạch, nếu không migration sẽ vỡ | Migration |
| 8 | Áp key ở **cả credit và wallet-payment debit** + duplicate → no-op | Code |

**Vì sao thứ tự này bắt buộc:** thêm unique trước khi làm sạch sẽ khiến migration fail giữa chừng trên production; backfill trước khi chốt format key sẽ phải làm lại; xóa "trùng" trước khi phân loại có thể xóa mất giao dịch hợp lệ (refund + compensation).

**Lưu ý backfill (khác spec v1):** v1 đề xuất bỏ trống toàn bộ dòng lịch sử. v2 theo hướng bạn chốt — **có backfill** phần xác định chắc chắn, để constraint bảo vệ cả dữ liệu cũ và để lộ ra duplicate lịch sử. Dòng không thể xác định chắc chắn thì **giữ NULL** (unique index bỏ qua NULL) và ghi nhận ở bước 6.

### Kết quả AUDIT production (bước 1–2, chạy 2026-07-19, chỉ đọc)

| Chỉ số | Giá trị |
|---|---|
| `wallet_transactions` | **0 dòng** (5 ví tồn tại, chưa ví nào có giao dịch) |
| `payments` | 42 `success`, 18 `pending`, **0 `refunded`**; `refunded_at` null toàn bộ |
| `bookings` | 42 paid, 25 unpaid; **0 vé `cancelled` + `paid`** (không có ứng viên hoàn tiền) |
| `ProcessRefundJob` trong backlog `queues:high` | **0** |

**Kết luận:** **chưa có bất kỳ khoản hoàn tiền nào từng chạy trên production**, do đó **không tồn tại duplicate lịch sử**. Bước 2 (phân loại), 5 (backfill), 6 (xử lý ngoại lệ thủ công) **rỗng trên thực tế** — nhưng vẫn giữ trong quy trình vì (a) đây là chuẩn cho mọi lần chạy lại ở môi trường khác, (b) phải kiểm lại ngay trước khi thêm constraint phòng khi dữ liệu phát sinh trong lúc làm.

**Nguyên nhân gốc:** `ProcessRefundJob` vào queue `high` mà worker chưa bao giờ nghe queue này ⇒ chưa refund nào được thực thi. Nghĩa là ta đang vá **trước khi tiền kịp chạy sai** — thời điểm lý tưởng nhất để thêm constraint (rủi ro migration = 0).

⚠️ **Hệ quả về thứ tự với việc bật worker `high`:** nên **thêm constraint + áp key TRƯỚC** khi bật worker/drain. Nếu bật worker trước, refund thật bắt đầu chạy trong lúc chưa có bảo vệ cấu trúc.

## 8. Testing strategy (khi lên plan)

- **Sequential**: gọi `refund()` 3 lần → đúng 1 dòng `wallet_transactions`, số dư cộng 1 lần, `payment.status=refunded`. *(Đã có test tương tự; sẽ mạnh hơn nhờ constraint.)*
- **Duplicate key trực tiếp**: gọi `credit()` hai lần cùng key → lần hai no-op, không tăng số dư, không ném lỗi ra ngoài.
- **Refund + compensation cùng booking**: cả hai đều ghi được (key khác nhau) — chốt rằng constraint không chặn nhầm.
- **`balance_after` dưới đồng thời**: nhiều credit liên tiếp → chuỗi `balance_after` tăng đơn điệu, khớp số dư cuối.
- **Concurrency thật (MySQL, 2 tiến trình)**: hai `refund()` song song cùng booking → đúng 1 giao dịch. ⚠️ Ghi rõ: sqlite đơn luồng **không** kiểm chứng được lock/constraint dưới đua — bài học từ đợt trước (test refund cũ vẫn pass khi gỡ `lockForUpdate`). Test này phải chạy trên harness MySQL 2 tiến trình, cùng loại hạ tầng đã dự trù cho spec driver-unavailability §12.
- **Regression**: luồng nạp tiền/thanh toán ví/payout hiện có không đổi hành vi.
