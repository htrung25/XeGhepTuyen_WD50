<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuditLogController extends Controller
{
    #[OA\Get(
        path: "/api/admin/audit-logs",
        summary: "Lấy danh sách nhật ký hoạt động (Audit Logs)",
        tags: ["Admin Audit Logs"],
        security: [["sanctum" => []]]
    )]
    #[OA\QueryParameter(name: "user_id", required: false, description: "Lọc theo ID người thực hiện", schema: new OA\Schema(type: "string", format: "uuid"))]
    #[OA\QueryParameter(name: "action", required: false, description: "Lọc theo loại hành động", schema: new OA\Schema(type: "string"))]
    #[OA\QueryParameter(name: "model_type", required: false, description: "Lọc theo loại Model (Class FQCN)", schema: new OA\Schema(type: "string"))]
    #[OA\QueryParameter(name: "date_from", required: false, description: "Từ ngày (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date"))]
    #[OA\QueryParameter(name: "date_to", required: false, description: "Đến ngày (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date"))]
    #[OA\QueryParameter(name: "search", required: false, description: "Tìm kiếm từ khóa", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Danh sách nhật ký hoạt động",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                new OA\Property(property: "meta", type: "object")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Chưa xác thực")]
    #[OA\Response(response: 403, description: "Không có quyền truy cập")]
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->model_type) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('model_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $logs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $log = AuditLog::with('user')->find($id);

        if (! $log) {
            return response()->json(['success' => false, 'message' => 'Nhật ký không tồn tại'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($log),
        ]);
    }
}
