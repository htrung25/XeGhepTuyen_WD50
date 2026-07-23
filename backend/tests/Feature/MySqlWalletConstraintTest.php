<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/*
 * Vì sao cần test này: test sqlite đơn luồng KHÔNG chứng minh được lock ứng dụng
 * (test refund trước đây vẫn pass khi gỡ lockForUpdate). UNIQUE index thì kiểm
 * chứng trực tiếp được — chèn thẳng 2 dòng cùng key và assert DB từ chối, bỏ qua
 * toàn bộ lớp ứng dụng. Đây là bảo đảm duy nhất chịu được queue at-least-once.
 */

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

it('schema production-like: cột tồn tại và có UNIQUE index', function () {
    $sql = DB::selectOne('show create table wallet_transactions')->{'Create Table'};

    expect($sql)->toContain('idempotency_key')
        ->and(preg_match('/UNIQUE KEY.*idempotency_key/i', $sql))->toBe(1);
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
