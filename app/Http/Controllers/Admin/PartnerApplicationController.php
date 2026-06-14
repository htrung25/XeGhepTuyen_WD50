<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PartnerApplicationResource;
use App\Repositories\Contracts\PartnerApplicationRepositoryInterface;
use App\Services\PartnerApplicationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartnerApplicationController extends Controller
{
    public function __construct(
        private readonly PartnerApplicationService $applicationService,
        private readonly PartnerApplicationRepositoryInterface $applicationRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $applications = $this->applicationRepo->paginate(
            $request->only(['status', 'search']),
            (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data' => PartnerApplicationResource::collection($applications->items()),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'total' => $applications->total(),
                'last_page' => $applications->lastPage(),
            ],
        ]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ]);

        $application = $this->applicationRepo->find($id);
        if (! $application) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn đăng ký'], 404);
        }

        try {
            $operator = $this->applicationService->approve(
                $application,
                (float) ($validated['commission_rate'] ?? 10),
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã duyệt đơn, tạo tài khoản nhà xe và gửi SMS thông tin đăng nhập',
                'data' => ['operator_id' => $operator->id],
            ]);

        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (\Throwable $e) {
            Log::error('Approve partner application failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại sau'], 500);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $application = $this->applicationRepo->find($id);
        if (! $application) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn đăng ký'], 404);
        }

        try {
            $this->applicationService->reject($application, $validated['reason'], $request->user());

            return response()->json(['success' => true, 'message' => 'Đã từ chối đơn đăng ký']);

        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
