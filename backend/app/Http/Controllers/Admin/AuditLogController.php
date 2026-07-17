<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Services\AuditLogQueryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogQuery
    ) {}

    #[OA\Get(
        path: '/api/admin/audit-logs',
        summary: 'Lấy danh sách nhật ký hoạt động (Audit Logs)',
        tags: ['Admin Audit Logs'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'user_id', in: 'query', required: false, description: 'UUID admin thực hiện hành động', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'action', in: 'query', required: false, description: 'Mã hành động audit', schema: new OA\Schema(type: 'string', maxLength: 255))]
    #[OA\Parameter(name: 'model_type', in: 'query', required: false, description: 'Tên class model liên quan', schema: new OA\Schema(type: 'string', maxLength: 255))]
    #[OA\Parameter(name: 'date_from', in: 'query', required: false, description: 'Ngày bắt đầu', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'date_to', in: 'query', required: false, description: 'Ngày kết thúc', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'Tìm theo hành động, mô tả, model ID hoặc admin', schema: new OA\Schema(type: 'string', maxLength: 255))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20))]
    #[OA\Response(
        response: 200,
        description: 'Danh sách nhật ký hoạt động',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AuditLog')),
                new OA\Property(
                    property: 'meta',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'from', type: 'integer', nullable: true),
                        new OA\Property(property: 'to', type: 'integer', nullable: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 403, description: 'Không có quyền truy cập')]
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

    #[OA\Get(
        path: '/api/admin/audit-logs/{id}',
        summary: 'Xem chi tiết một nhật ký hoạt động',
        tags: ['Admin Audit Logs'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Chi tiết nhật ký hoạt động',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: '#/components/schemas/AuditLog'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 403, description: 'Không có quyền truy cập')]
    #[OA\Response(response: 404, description: 'Nhật ký không tồn tại')]
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
