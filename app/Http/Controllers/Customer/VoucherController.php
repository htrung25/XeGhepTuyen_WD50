<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $voucherService
    ) {}

    /**
     * Xem trước mức giảm của mã giảm giá trước khi đặt vé.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'    => ['required', 'string', 'max:20'],
            'trip_id' => ['required', 'uuid', 'exists:trips,id'],
            'amount'  => ['required', 'integer', 'min:0'],
        ], [
            'code.required'    => 'Vui lòng nhập mã giảm giá',
            'trip_id.required' => 'Thiếu thông tin chuyến đi',
            'amount.required'  => 'Thiếu giá trị đơn hàng',
        ]);

        try {
            $voucher  = $this->voucherService->validate(
                $validated['code'],
                $validated['amount'],
                auth('customer')->user(),
                $validated['trip_id'],
            );
            $discount = $voucher->calculateDiscount($validated['amount']);

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công',
                'data'    => [
                    'code'            => $voucher->code,
                    'discount_amount' => $discount,
                    'final_amount'    => max(0, $validated['amount'] - $discount),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => 'VOUCHER_INVALID',
            ], 422);
        }
    }
}
