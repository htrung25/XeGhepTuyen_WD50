<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Models\Voucher;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vouchers = Voucher::with('operator')
                           ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
                           ->latest()
                           ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $vouchers->items(),
            'meta'    => ['current_page' => $vouchers->currentPage(), 'total' => $vouchers->total()],
        ]);
    }

    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $voucher = Voucher::create($request->validated());

        app(AuditLogService::class)->log(
            action: 'create_voucher',
            model: $voucher,
            description: "Đã tạo voucher mới: {$voucher->code} (Giảm: " . ($voucher->discount_type === 'percent' ? $voucher->discount_value . '%' : number_format((float) $voucher->discount_value, 0, ',', '.') . 'đ') . ")",
            newValues: $voucher->toArray()
        );

        return response()->json(['success' => true, 'message' => 'Tạo voucher thành công', 'data' => $voucher], 201);
    }

    public function show(string $id): JsonResponse
    {
        $voucher = Voucher::with(['usages.user', 'operator'])->find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $voucher]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại'], 404);
        }

        $validated = $request->validate([
            'discount_type'  => ['sometimes', 'in:percent,fixed'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'min_order'      => ['sometimes', 'integer', 'min:0'],
            'max_discount'   => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_limit'    => ['sometimes', 'integer', 'min:1'],
            'valid_from'     => ['sometimes', 'date'],
            'valid_until'    => ['sometimes', 'date', 'after:valid_from'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $oldValues = $voucher->toArray();
        $voucher->update($validated);

        app(AuditLogService::class)->log(
            action: 'update_voucher',
            model: $voucher,
            description: "Đã cập nhật voucher: {$voucher->code}",
            oldValues: $oldValues,
            newValues: $voucher->toArray()
        );

        return response()->json(['success' => true, 'message' => 'Cập nhật voucher thành công', 'data' => $voucher]);
    }

    public function toggle(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại'], 404);
        }

        $oldStatus = $voucher->is_active;
        $voucher->update(['is_active' => !$voucher->is_active]);

        $status = $voucher->is_active ? 'kích hoạt' : 'vô hiệu hoá';

        app(AuditLogService::class)->log(
            action: 'toggle_voucher',
            model: $voucher,
            description: "Đã " . ($voucher->is_active ? "kích hoạt" : "vô hiệu hoá") . " voucher: {$voucher->code}",
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => $voucher->is_active]
        );

        return response()->json(['success' => true, 'message' => "Đã {$status} voucher"]);
    }

    public function destroy(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại'], 404);
        }

        if ($voucher->usages()->exists()) {
            return response()->json(['success' => false, 'message' => 'Không thể xoá voucher đã được sử dụng'], 422);
        }

        $oldValues = $voucher->toArray();
        $voucher->delete();

        app(AuditLogService::class)->log(
            action: 'delete_voucher',
            model: $voucher,
            description: "Đã xoá voucher: {$voucher->code}",
            oldValues: $oldValues
        );

        return response()->json(['success' => true, 'message' => 'Đã xoá voucher']);
    }
}
