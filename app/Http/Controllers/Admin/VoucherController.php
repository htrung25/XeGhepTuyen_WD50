<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Models\Voucher;
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

    public function toggle(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại'], 404);
        }

        $voucher->update(['is_active' => !$voucher->is_active]);

        $status = $voucher->is_active ? 'kích hoạt' : 'vô hiệu hoá';

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

        $voucher->delete();

        return response()->json(['success' => true, 'message' => 'Đã xoá voucher']);
    }
}
