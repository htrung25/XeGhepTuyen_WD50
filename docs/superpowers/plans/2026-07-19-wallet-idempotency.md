# Wallet Idempotency (chống hoàn tiền/trừ tiền trùng) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đảm bảo mỗi khoản cộng/trừ ví chỉ xảy ra **đúng một lần** bất kể queue giao job bao nhiêu lần, bằng `idempotency_key` có UNIQUE index ở tầng DB + khóa hàng ví.

**Architecture:** Thêm cột `wallet_transactions.idempotency_key` (nullable + UNIQUE). `Wallet::credit()/debit()` khóa hàng ví (`lockForUpdate`) rồi kiểm key trong cùng transaction — trùng thì trả giao dịch cũ (no-op), không cộng/trừ lần hai. Ba điểm gọi truyền key tất định: `refund:{booking_id}`, `compensation:{booking_id}`, `wallet_payment:{booking_id}`.

**Tech Stack:** Laravel 12, MySQL 8 (production) / SQLite in-memory (test suite), Pest.

## Global Constraints

- **Đây là tiền thật**: không xóa dữ liệu tự động; mọi ngoại lệ do người quyết.
- Cột `idempotency_key` **nullable** — dòng lịch sử giữ NULL (MySQL cho phép nhiều NULL trong UNIQUE index), migration không thể vỡ vì dữ liệu cũ.
- **Thứ tự bắt buộc**: thêm UNIQUE **TRƯỚC** khi code bắt đầu ghi key. Làm ngược lại thì duplicate có thể lọt vào giữa hai lần deploy rồi migration UNIQUE fail.
- **Thêm constraint + áp key TRƯỚC khi bật worker `high`** trên production (audit đã xác nhận chưa refund nào từng chạy — giữ cửa sổ rủi ro bằng 0).
- Trước mỗi commit: `vendor/bin/pint <files>` + `php artisan test` pass 100%.
- Commit message kết thúc bằng: `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.
- Không đổi hành vi luồng nạp tiền/payout hiện có.

**Bối cảnh audit (đã chạy 2026-07-19, chỉ đọc):** `wallet_transactions` = **0 dòng**, `payments` 0 refunded, `bookings` 0 cancelled+paid, backlog `queues:high` không có `ProcessRefundJob` nào. ⇒ Bước "phân loại duplicate", "backfill", "xử lý ngoại lệ thủ công" của spec §7bis **rỗng trên thực tế**, nhưng Task 1 vẫn kiểm lại ngay trước khi thêm UNIQUE phòng dữ liệu phát sinh.

**Ngoài phạm vi (làm sau):** tách `compensation` khỏi type `refund` (hướng A trong spec) — đã chốt sẽ làm nhưng ở đợt riêng.

---

### Task 1: Schema — cột `idempotency_key` + UNIQUE index

**Files:**
- Create: `backend/database/migrations/2026_07_23_000001_add_idempotency_key_to_wallet_transactions_table.php`
- Modify: `backend/app/Models/WalletTransaction.php` (thêm vào `$fillable`)
- Test: `backend/tests/Feature/WalletIdempotencyTest.php` (tạo mới)

**Interfaces:**
- Produces: cột `wallet_transactions.idempotency_key` (`string(191)` nullable, UNIQUE). Task 2 ghi vào cột này.
- Sau task này **chưa có gì ghi key** → toàn bộ NULL → **không đổi hành vi runtime**.

- [ ] **Step 1: Kiểm lại dữ liệu production trước khi thêm UNIQUE (chỉ đọc)**

```bash
cd backend && php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
echo 'tong wallet_transactions: '.DB::table('wallet_transactions')->count().PHP_EOL;
foreach (DB::table('wallet_transactions')->select('booking_id','type',DB::raw('count(*) c'))
    ->whereNotNull('booking_id')->groupBy('booking_id','type')->having('c','>',1)->get() as \$r) {
    echo 'UNG VIEN TRUNG: booking='.\$r->booking_id.' type='.\$r->type.' sl='.\$r->c.PHP_EOL;
}
echo 'ket thuc kiem tra'.PHP_EOL;
"
```
Expected: `tong wallet_transactions: 0` và **không có dòng `UNG VIEN TRUNG`**.
⚠️ Nếu xuất hiện `UNG VIEN TRUNG`: **DỪNG**, không chạy migration. Phân loại thủ công (refund + compensation cùng booking là HỢP LỆ; cùng amount + cùng type mới là trùng thật), rồi mới tiếp tục.

- [ ] **Step 2: Viết test fail**

Tạo `backend/tests/Feature/WalletIdempotencyTest.php`:

```php
<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Schema;

function makeWalletUser(): User
{
    return User::factory()->create(['role' => UserRoleEnum::Customer]);
}

it('bảng wallet_transactions có cột idempotency_key', function () {
    expect(Schema::hasColumn('wallet_transactions', 'idempotency_key'))->toBeTrue();
});

it('idempotency_key nhận NULL nhiều lần (dòng lịch sử không vướng UNIQUE)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    WalletTransaction::create([
        'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 1000,
        'balance_after' => 1000, 'description' => 'a', 'idempotency_key' => null,
    ]);
    WalletTransaction::create([
        'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 2000,
        'balance_after' => 3000, 'description' => 'b', 'idempotency_key' => null,
    ]);

    expect(WalletTransaction::count())->toBe(2);
});
```

- [ ] **Step 3: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: FAIL — `Failed asserting that false is true` (chưa có cột).

- [ ] **Step 4: Viết migration**

Tạo `backend/database/migrations/2026_07_23_000001_add_idempotency_key_to_wallet_transactions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Khóa nghiệp vụ tất định do caller sinh: "refund:{booking_id}",
            // "compensation:{booking_id}", "wallet_payment:{booking_id}".
            // NULLABLE: dòng lịch sử giữ NULL — MySQL cho phép nhiều NULL trong UNIQUE
            // index nên migration không vỡ vì dữ liệu cũ.
            // UNIQUE: bảo đảm CẤU TRÚC chống ghi trùng — queue redis là at-least-once,
            // lock ở tầng ứng dụng có thể mất khi refactor, constraint thì không.
            $table->string('idempotency_key', 191)->nullable()->unique()->after('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
```

- [ ] **Step 5: Thêm vào `$fillable`**

Trong `backend/app/Models/WalletTransaction.php`, thêm `'idempotency_key'` ngay sau `'booking_id'`:

```php
    protected $fillable = [
        'wallet_id',
        'booking_id',
        'idempotency_key',
        'type',
        'amount',
        'balance_after',
        'description',
    ];
```

- [ ] **Step 6: Chạy test, xác nhận pass**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: PASS (2 tests)

- [ ] **Step 7: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint database/migrations/2026_07_23_000001_add_idempotency_key_to_wallet_transactions_table.php app/Models/WalletTransaction.php tests/Feature/WalletIdempotencyTest.php
php artisan test
git add -A && git commit -m "feat: them idempotency_key + unique index cho wallet_transactions

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: `Wallet::credit()/debit()` — khóa hàng ví + key + duplicate no-op

**Files:**
- Modify: `backend/app/Models/Wallet.php` (`credit()` và `debit()`)
- Test: `backend/tests/Feature/WalletIdempotencyTest.php` (thêm test)

**Interfaces:**
- Consumes: cột `idempotency_key` (Task 1).
- Produces:
  - `Wallet::credit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction`
  - `Wallet::debit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction`
  - Hợp đồng: key đã tồn tại → **trả giao dịch cũ, KHÔNG đổi số dư, KHÔNG ném lỗi**. Key `null` → hành vi như cũ (không chống trùng). Task 3 gọi hai hàm này.

- [ ] **Step 1: Viết test fail**

Thêm vào cuối `backend/tests/Feature/WalletIdempotencyTest.php`:

```php
use App\Enums\WalletTransactionTypeEnum;

it('credit() cùng idempotency_key hai lần chỉ cộng tiền MỘT lần', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $first = $wallet->credit(150000, 'Hoàn tiền vé X', WalletTransactionTypeEnum::Refund, null, 'refund:booking-1');
    $second = $wallet->credit(150000, 'Hoàn tiền vé X', WalletTransactionTypeEnum::Refund, null, 'refund:booking-1');

    expect($second->id)->toBe($first->id)                 // trả đúng giao dịch cũ
        ->and(WalletTransaction::count())->toBe(1)
        ->and($wallet->refresh()->balance)->toBe(150000); // KHÔNG phải 300000
});

it('credit() với key khác nhau vẫn ghi đủ (refund + compensation cùng booking)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(150000, 'Hoàn tiền', WalletTransactionTypeEnum::Refund, 'booking-1', 'refund:booking-1');
    $wallet->credit(20000, 'Bồi thường', WalletTransactionTypeEnum::Refund, 'booking-1', 'compensation:booking-1');

    expect(WalletTransaction::count())->toBe(2)
        ->and($wallet->refresh()->balance)->toBe(170000);
});

it('credit() không truyền key thì giữ hành vi cũ (ghi mọi lần)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(1000, 'x', WalletTransactionTypeEnum::Refund);
    $wallet->credit(1000, 'x', WalletTransactionTypeEnum::Refund);

    expect(WalletTransaction::count())->toBe(2)
        ->and($wallet->refresh()->balance)->toBe(2000);
});

it('debit() cùng idempotency_key hai lần chỉ trừ tiền MỘT lần', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 500000]);

    $first = $wallet->debit(150000, 'Thanh toán vé X', WalletTransactionTypeEnum::Payment, null, 'wallet_payment:booking-1');
    $second = $wallet->debit(150000, 'Thanh toán vé X', WalletTransactionTypeEnum::Payment, null, 'wallet_payment:booking-1');

    expect($second->id)->toBe($first->id)
        ->and(WalletTransaction::count())->toBe(1)
        ->and($wallet->refresh()->balance)->toBe(350000); // KHÔNG phải 200000
});

it('balance_after khớp số dư thật sau chuỗi giao dịch', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(100000, 'a', WalletTransactionTypeEnum::Refund, null, 'k1');
    $wallet->credit(50000, 'b', WalletTransactionTypeEnum::Refund, null, 'k2');
    $last = $wallet->debit(30000, 'c', WalletTransactionTypeEnum::Payment, null, 'k3');

    expect($last->balance_after)->toBe(120000)
        ->and($wallet->refresh()->balance)->toBe(120000);
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: FAIL — số dư 300000/200000 (cộng/trừ hai lần) vì chưa có cơ chế key.

- [ ] **Step 3: Implement `credit()`**

Thay toàn bộ `credit()` trong `backend/app/Models/Wallet.php`:

```php
    /**
     * Cộng tiền vào ví (nạp tiền / hoàn tiền).
     *
     * @param  string|null  $idempotencyKey  Khóa nghiệp vụ tất định. Truyền key ⇒ gọi lại
     *                                       nhiều lần chỉ ghi MỘT giao dịch (queue redis là
     *                                       at-least-once). null ⇒ hành vi cũ, không chống trùng.
     */
    public function credit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId, $idempotencyKey) {
            // Khóa hàng ví: (1) serialize các giao dịch cùng ví nên kiểm key bên dưới là
            // airtight, (2) giữ balance_after nhất quán — không bị giao dịch song song chen giữa
            // increment và refresh làm số dư ghi vào sổ bị nhảy.
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing; // no-op: đã xử lý ở lần giao trước
                }
            }

            $wallet->increment('balance', $amount);
            $wallet->refresh();
            $this->balance = $wallet->balance; // đồng bộ instance mà caller đang giữ

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'booking_id' => $bookingId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
```

- [ ] **Step 4: Implement `debit()`**

Thay toàn bộ `debit()` trong `backend/app/Models/Wallet.php`:

```php
    /**
     * Trừ tiền từ ví (thanh toán / rút tiền).
     *
     * @param  string|null  $idempotencyKey  Xem credit(). Kiểm key TRƯỚC kiểm số dư —
     *                                       lần giao trùng không được coi là "thiếu tiền".
     *
     * @throws InsufficientBalanceException
     */
    public function debit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId, $idempotencyKey) {
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException(
                    "Số dư ví không đủ. Cần {$amount}đ, hiện có {$wallet->balance}đ"
                );
            }

            $wallet->decrement('balance', $amount);
            $wallet->refresh();
            $this->balance = $wallet->balance;

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'booking_id' => $bookingId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: PASS (7 tests)

- [ ] **Step 6: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint app/Models/Wallet.php tests/Feature/WalletIdempotencyTest.php
php artisan test
git add -A && git commit -m "feat: Wallet credit/debit khoa hang vi + idempotency key (duplicate = no-op)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Truyền key tất định tại 3 điểm gọi ví

**Files:**
- Modify: `backend/app/Services/WalletService.php` (`credit()`, `debit()`)
- Modify: `backend/app/Services/PaymentService.php` (hoàn tiền ~dòng 149; thanh toán ví ~dòng 380)
- Modify: `backend/app/Services/BookingService.php` (bồi thường ~dòng 318)
- Test: `backend/tests/Feature/WalletIdempotencyTest.php` (thêm test)

**Interfaces:**
- Consumes: `Wallet::credit/debit(..., ?string $idempotencyKey)` (Task 2).
- Produces:
  - `WalletService::credit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction`
  - `WalletService::debit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction`
- Quy ước key (bất biến nghiệp vụ): `refund:{booking_id}` (mỗi vé hoàn tối đa 1 lần), `compensation:{booking_id}` (mỗi vé bồi thường tối đa 1 lần), `wallet_payment:{booking_id}` (mỗi vé trừ ví tối đa 1 lần).

- [ ] **Step 1: Viết test fail**

Thêm vào cuối `backend/tests/Feature/WalletIdempotencyTest.php`:

```php
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\WalletService;

it('refund() gọi lại nhiều lần chỉ ghi MỘT giao dịch ví (key refund:{booking})', function () {
    $booking = makePaidCancellableBooking(); // helper sẵn có ở RefundDispatchAfterCommitTest
    Payment::create([
        'booking_id' => $booking->id, 'user_id' => $booking->user_id,
        'amount' => 150000, 'method' => 'momo', 'status' => 'success',
        'gateway_order_id' => 'XEGHEP-'.Str::upper(Str::random(10)), 'paid_at' => now(),
    ]);

    $service = app(PaymentService::class);
    $service->refund($booking, 150000);
    $service->refund($booking->refresh(), 150000);
    $service->refund($booking->refresh(), 150000);

    expect(WalletTransaction::where('idempotency_key', "refund:{$booking->id}")->count())->toBe(1)
        ->and(app(WalletService::class)->getBalance($booking->user))->toBe(150000);
});

it('hoàn tiền và bồi thường cùng booking đều ghi được (key khác nhau)', function () {
    $user = makeWalletUser();
    $service = app(WalletService::class);

    $service->credit($user, 150000, 'Hoàn tiền', 'booking-9', 'refund:booking-9');
    $service->credit($user, 20000, 'Bồi thường', 'booking-9', 'compensation:booking-9');

    expect(WalletTransaction::count())->toBe(2)
        ->and($service->getBalance($user))->toBe(170000);
});
```

Thêm import `use Illuminate\Support\Str;` nếu file chưa có.

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: FAIL — `Failed asserting that 0 matches expected 1` (chưa điểm gọi nào truyền key).

- [ ] **Step 3: Cho `WalletService` truyền key xuống**

Thay hai method trong `backend/app/Services/WalletService.php`:

```php
    public function credit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->credit($amount, $description, WalletTransactionTypeEnum::Refund, $bookingId, $idempotencyKey);
    }

    public function debit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->debit($amount, $description, WalletTransactionTypeEnum::Payment, $bookingId, $idempotencyKey);
    }
```

- [ ] **Step 4: Gắn key ở điểm hoàn tiền**

Trong `backend/app/Services/PaymentService.php`, phần `refund()` — bổ sung tham số thứ 5:

```php
                $this->walletService->credit(
                    $booking->user,
                    $amount,
                    "Hoàn tiền vé {$booking->booking_code}",
                    $booking->id,
                    "refund:{$booking->id}",
                );
```

- [ ] **Step 5: Gắn key ở điểm thanh toán bằng ví**

Trong `backend/app/Services/PaymentService.php`, phần `initiateWallet()`:

```php
            $this->walletService->debit(
                $booking->user,
                $payment->amount,
                "Thanh toán vé {$booking->booking_code}",
                $booking->id,
                "wallet_payment:{$booking->id}",
            );
```

- [ ] **Step 6: Gắn key ở điểm bồi thường**

Trong `backend/app/Services/BookingService.php`, phần `cancelByOperator()`:

```php
                    $this->walletService->credit(
                        $booking->user,
                        self::COMPENSATION_AMOUNT,
                        "Bồi thường hủy chuyến — vé {$booking->booking_code}",
                        $booking->id,
                        "compensation:{$booking->id}",
                    );
```

- [ ] **Step 7: Chạy test, xác nhận pass**

Run: `cd backend && php artisan test --filter=WalletIdempotencyTest`
Expected: PASS (9 tests)

- [ ] **Step 8: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint app/Services/WalletService.php app/Services/PaymentService.php app/Services/BookingService.php tests/Feature/WalletIdempotencyTest.php
php artisan test
git add -A && git commit -m "feat: ap idempotency key cho hoan tien, boi thuong va thanh toan vi

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Chứng minh ràng buộc ở tầng DB (MySQL integration test)

**Files:**
- Create: `backend/tests/Feature/MySqlWalletConstraintTest.php`

**Interfaces:**
- Consumes: UNIQUE index (Task 1), key convention (Task 3).
- Produces: bằng chứng **cấu trúc** — DB từ chối dòng thứ hai cùng key, kể cả khi code ứng dụng bị bỏ qua hoàn toàn.

**Vì sao cần task này:** bài học từ đợt sửa queue — test sqlite đơn luồng **không** chứng minh được lock (test refund cũ vẫn pass khi gỡ `lockForUpdate`). UNIQUE index thì kiểm chứng được trực tiếp: chèn thẳng 2 dòng cùng key và assert DB ném lỗi. Đây là điều duy nhất bảo đảm an toàn khi at-least-once.

- [ ] **Step 1: Viết test**

Tạo `backend/tests/Feature/MySqlWalletConstraintTest.php`:

```php
<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Cần MySQL — xem hướng dẫn chạy ở cuối file');
    }
    // KHÓA AN TOÀN: RefreshDatabase xóa sạch DB — cấm tuyệt đối DB từ xa (Cloud)
    if (! in_array(DB::getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
        $this->fail('NGUY HIỂM: chỉ chạy trên MySQL localhost (RefreshDatabase sẽ wipe DB).');
    }
});

it('DB TỪ CHỐI dòng thứ hai cùng idempotency_key (bỏ qua mọi lớp ứng dụng)', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);

    $row = [
        'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 150000,
        'balance_after' => 150000, 'description' => 'x', 'idempotency_key' => 'refund:booking-dup',
    ];

    WalletTransaction::create($row);

    expect(fn () => WalletTransaction::create($row))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(WalletTransaction::where('idempotency_key', 'refund:booking-dup')->count())->toBe(1);
});

it('nhiều dòng idempotency_key = NULL vẫn hợp lệ trên MySQL', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);

    foreach ([1000, 2000, 3000] as $i => $amount) {
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => $amount,
            'balance_after' => $amount, 'description' => 'n'.$i, 'idempotency_key' => null,
        ]);
    }

    expect(WalletTransaction::whereNull('idempotency_key')->count())->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Cách chạy (MySQL 8 Docker — KHÔNG dùng DB Cloud):
|   docker run --rm -d --name xeghep-mysql-test -p 3307:3306 \
|     -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=xeghep_test mysql:8.0
|   cd backend && DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 \
|     DB_DATABASE=xeghep_test DB_USERNAME=root DB_PASSWORD=secret DB_URL="" \
|     php artisan test --filter=MySqlWalletConstraintTest
|--------------------------------------------------------------------------
*/
```

- [ ] **Step 2: Xác nhận suite sqlite skip sạch**

Run: `cd backend && php artisan test --filter=MySqlWalletConstraintTest`
Expected: 2 tests SKIPPED.

- [ ] **Step 3: Chạy thật trên MySQL Docker**

```bash
docker run --rm -d --name xeghep-mysql-test -p 3307:3306 \
  -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=xeghep_test mysql:8.0
# đợi container healthy (~10-20s), rồi:
cd backend && DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 \
  DB_DATABASE=xeghep_test DB_USERNAME=root DB_PASSWORD=secret DB_URL="" \
  php artisan test --filter=MySqlWalletConstraintTest
docker rm -f xeghep-mysql-test
```
Expected: PASS 2 tests.

- [ ] **Step 4: Thêm vào CI job `spatial-tests`**

Trong `.github/workflows/backend.yml`, đổi dòng chạy test của job `spatial-tests` để chạy cả hai nhóm MySQL:

```yaml
        run: ./vendor/bin/pest --filter='MySqlSpatialIntegrationTest|MySqlWalletConstraintTest'
```

- [ ] **Step 5: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint tests/Feature/MySqlWalletConstraintTest.php
php artisan test
git add -A && git commit -m "test: chung minh unique constraint idempotency_key tren MySQL + CI

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Triển khai production (chỉ chạy sau khi bạn duyệt)

> ⚠️ Ghi vào DB Cloud dùng chung. **Phải chạy TRƯỚC khi bật worker `high`.**

**Files:** không sửa code.

- [ ] **Step 1: Kiểm lại lần cuối (chỉ đọc)**

Chạy lại đúng lệnh ở Task 1 Step 1.
Expected: `tong wallet_transactions: 0`, không có `UNG VIEN TRUNG`. Nếu khác → **DỪNG**, phân loại thủ công trước.

- [ ] **Step 2: Backup 2 bảng liên quan**

```bash
mysqldump -h <DB_HOST> -P 3306 -u <DB_USERNAME> -p<DB_PASSWORD> main wallets wallet_transactions > ~/backup_wallet_$(date +%Y%m%d_%H%M).sql
```
Expected: file > 0 byte. Không tiếp tục nếu backup lỗi.

- [ ] **Step 3: Deploy code (migration chạy trong deploy command của Laravel Cloud)**

Merge PR → Laravel Cloud chạy `php artisan migrate --force && php artisan optimize`.

- [ ] **Step 4: Xác minh schema production**

```bash
cd backend && php artisan tinker --execute="
\$sql = \Illuminate\Support\Facades\DB::selectOne('show create table wallet_transactions')->{'Create Table'};
echo (str_contains(\$sql, 'idempotency_key') ? 'OK co cot' : 'THIEU cot').PHP_EOL;
echo (preg_match('/UNIQUE KEY.*idempotency_key/i', \$sql) ? 'OK co UNIQUE' : 'THIEU UNIQUE').PHP_EOL;
"
```
Expected: `OK co cot` và `OK co UNIQUE`.

- [ ] **Step 5: Chỉ SAU khi Step 4 xanh — bật worker `high`**

Sửa lệnh worker trong dashboard Laravel Cloud thành:
`php artisan queue:work --queue=high,notifications,default --sleep=3 --tries=3 --timeout=90`

- [ ] **Step 6: Theo dõi sau khi drain**

```bash
cd backend && php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
echo 'wallet_transactions: '.DB::table('wallet_transactions')->count().PHP_EOL;
echo 'co key: '.DB::table('wallet_transactions')->whereNotNull('idempotency_key')->count().PHP_EOL;
echo 'queues:high con: '.\Illuminate\Support\Facades\Redis::llen('queues:high').PHP_EOL;
"
```
Expected: `queues:high` giảm dần về 0; mọi giao dịch ví mới đều có key.

---

## Self-Review

**1. Spec coverage:** cột nullable+UNIQUE ✔T1 · khóa hàng ví + balance_after ✔T2 (spec §3.1) · duplicate no-op không làm job fail ✔T2 (§3.2) · transaction lồng: pre-check nằm trong transaction của `Wallet` nên không ném lỗi ra transaction cha ✔T2 (§3.3) · debit trong V1 ✔T3 (§7.3) · 3 điểm gọi ✔T3 · audit bắt buộc + backfill/ngoại lệ ✔T1 Step 1 + T5 Step 1 (rỗng thực tế, đã ghi rõ) · test constraint thật ✔T4 (§8) · tách type `compensation` = ngoài phạm vi, đã ghi.

**2. Placeholder scan:** không có TBD/TODO; mọi step có code hoặc lệnh cụ thể + expected output.

**3. Type consistency:** `credit/debit(int, string, WalletTransactionTypeEnum, ?string $bookingId, ?string $idempotencyKey)` khai ở T2 ↔ dùng ở T3 ✔ · `WalletService::credit/debit(User, int, string, ?string, ?string)` T3 ↔ test T3 ✔ · tên cột `idempotency_key` nhất quán T1→T4 ✔ · format key `refund:` / `compensation:` / `wallet_payment:` nhất quán spec ↔ T3 ↔ test ✔.

**Ghi chú phụ thuộc test:** Task 3 Step 1 dùng helper `makePaidCancellableBooking()` đang khai báo ở `tests/Feature/RefundDispatchAfterCommitTest.php`. Pest nạp mọi file test khi collect nên hàm khả dụng; nếu chạy `--filter` riêng lẻ báo undefined function thì chuyển helper sang `tests/Pest.php`.
