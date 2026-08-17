<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    /** Danh sách voucher đang hoạt động để hiển thị trên trang chủ. */
    public function index(): JsonResponse
    {
        $vouchers = Voucher::query()
            ->active()
            ->orderBy('valid_until')
            ->limit(6)
            ->get([
                'id',
                'code',
                'discount_type',
                'discount_value',
                'min_order',
                'max_discount',
                'usage_limit',
                'used_count',
                'valid_until',
            ]);

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
