<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function balance(): JsonResponse
    {
        $user    = auth('customer')->user();
        $wallet  = $this->walletService->getOrCreate($user);

        return response()->json([
            'success' => true,
            'data'    => ['balance' => $wallet->balance, 'formatted' => number_format($wallet->balance, 0, ',', '.') . 'đ'],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user    = auth('customer')->user();
        $history = $this->walletService->getTransactions($user, 20);

        return response()->json([
            'success' => true,
            'data'    => $history->items(),
            'meta'    => ['current_page' => $history->currentPage(), 'total' => $history->total()],
        ]);
    }

    public function topup(Request $request): JsonResponse
    {
        $request->validate([
            'amount'         => ['required', 'integer', 'min:50000', 'max:10000000'],
            'payment_method' => ['required', 'in:momo,vnpay'],
        ]);

        $user = auth('customer')->user();

        return response()->json([
            'success' => true,
            'message' => 'Chức năng nạp tiền đang được phát triển. Vui lòng thanh toán qua VNPay/MoMo khi đặt vé.',
        ]);
    }
}
