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
            ->with('operator:id,company_name')
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
                'operator_id',
            ])
            ->map(fn (Voucher $voucher) => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'discount_type' => $voucher->discount_type->value,
                'discount_value' => $voucher->discount_value,
                'min_order' => $voucher->min_order,
                'max_discount' => $voucher->max_discount,
                'usage_limit' => $voucher->usage_limit,
                'used_count' => $voucher->used_count,
                'valid_until' => $voucher->valid_until->toIso8601String(),
                'operator' => $voucher->operator ? [
                    'id' => $voucher->operator->id,
                    'company_name' => $voucher->operator->company_name,
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
