<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTicketRequest;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService
    ) {}

    /**
     * Danh sách toàn bộ ticket hỗ trợ (Phân trang + Lọc + Tìm kiếm + Stats)
     */
    #[OA\Get(
        path: '/api/admin/support/tickets',
        summary: 'Danh sách ticket hỗ trợ (lọc + tìm kiếm + thống kê)',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'Lọc theo trạng thái', schema: new OA\Schema(type: 'string', enum: ['all', 'open', 'in_progress', 'resolved', 'closed']))]
    #[OA\Parameter(name: 'category', in: 'query', required: false, description: 'Lọc theo danh mục', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'priority', in: 'query', required: false, description: 'Lọc theo mức độ ưu tiên', schema: new OA\Schema(type: 'string', enum: ['all', 'low', 'normal', 'high', 'urgent']))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'Tìm theo mã ticket / tiêu đề / tên / SĐT khách', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang (phân trang, 15/trang)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Danh sách ticket + meta phân trang + thống kê theo trạng thái',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'meta', type: 'object'),
                new OA\Property(property: 'stats', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 403, description: 'Không có quyền truy cập')]
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with('user:id,full_name,phone,email')
            ->orderBy('created_at', 'desc');

        // Lọc theo trạng thái
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Lọc theo danh mục
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Lọc theo mức độ ưu tiên
        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        // Tìm kiếm (Mã ticket, tiêu đề, tên khách hàng, SĐT khách hàng)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->paginate(15);

        // Tính toán số liệu thống kê (stats)
        $stats = [
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
            'closed' => SupportTicket::where('status', 'closed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Xem chi tiết ticket hỗ trợ kèm thông tin khách hàng và lịch sử chat
     */
    #[OA\Get(
        path: '/api/admin/support/tickets/{id}',
        summary: 'Chi tiết ticket hỗ trợ kèm khách hàng và lịch sử tin nhắn',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ticket', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Chi tiết ticket',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 404, description: 'Ticket không tồn tại')]
    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with([
            'user:id,full_name,phone,email',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Admin trả lời khách hàng
     */
    #[OA\Post(
        path: '/api/admin/support/tickets/{id}/reply',
        summary: 'Admin trả lời khách hàng trên ticket',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ticket', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['body'],
            properties: [
                new OA\Property(property: 'body', type: 'string', minLength: 2, maxLength: 5000, example: 'Chào bạn, chúng tôi đang xử lý yêu cầu của bạn.', description: 'Nội dung phản hồi'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Trả lời thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Trả lời yêu cầu hỗ trợ thành công.'),
                new OA\Property(property: 'data', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc ticket đã đóng')]
    public function reply(ReplyMessageRequest $request, string $id): JsonResponse
    {
        $admin = auth('admin')->user();

        try {
            $message = $this->ticketService->replyToTicket($admin, $id, $request->input('body'), 'admin');

            return response()->json([
                'success' => true,
                'message' => 'Trả lời yêu cầu hỗ trợ thành công.',
                'data' => $message,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Giao việc (Phân công xử lý)
     */
    #[OA\Post(
        path: '/api/admin/support/tickets/{id}/assign',
        summary: 'Phân công ticket cho một admin xử lý',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ticket', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['assigned_to'],
            properties: [
                new OA\Property(property: 'assigned_to', type: 'string', format: 'uuid', description: 'ID người dùng admin được giao việc', example: '9b1c...'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Phân công thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Phân công yêu cầu hỗ trợ thành công.'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 422, description: 'Người được giao không hợp lệ')]
    public function assign(AssignTicketRequest $request, string $id): JsonResponse
    {
        $this->ticketService->assignTicket($id, $request->input('assigned_to'));

        return response()->json([
            'success' => true,
            'message' => 'Phân công yêu cầu hỗ trợ thành công.',
        ]);
    }

    /**
     * Đánh dấu đã giải quyết xong
     */
    #[OA\Post(
        path: '/api/admin/support/tickets/{id}/resolve',
        summary: 'Đánh dấu ticket đã giải quyết',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ticket', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Đã đánh dấu giải quyết',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Đã đánh dấu giải quyết yêu cầu hỗ trợ thành công.'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 404, description: 'Ticket không tồn tại')]
    public function resolve(string $id): JsonResponse
    {
        $this->ticketService->resolveTicket($id);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu giải quyết yêu cầu hỗ trợ thành công.',
        ]);
    }

    /**
     * Admin chủ động đóng ticket
     */
    #[OA\Post(
        path: '/api/admin/support/tickets/{id}/close',
        summary: 'Admin đóng ticket hỗ trợ',
        tags: ['Admin Support'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ticket', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Đã đóng ticket',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Đóng yêu cầu hỗ trợ thành công.'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa xác thực')]
    #[OA\Response(response: 404, description: 'Ticket không tồn tại')]
    public function close(string $id): JsonResponse
    {
        $this->ticketService->closeTicket($id);

        return response()->json([
            'success' => true,
            'message' => 'Đóng yêu cầu hỗ trợ thành công.',
        ]);
    }
}
