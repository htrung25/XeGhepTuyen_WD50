<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Services\AuditLogQueryService;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogQuery
    ) {}

    public function index(IndexAuditLogRequest $request): JsonResponse
    {
        $logs = $this->auditLogQuery->paginate($request->validated());

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $log = $this->auditLogQuery->find($id);

        if (! $log) {
            return response()->json(['success' => false, 'message' => 'Nhật ký không tồn tại'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($log),
        ]);
    }
}
