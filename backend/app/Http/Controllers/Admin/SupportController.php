<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTicketRequest;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService
    ) {}

    /**
     * Danh sách toàn bộ ticket hỗ trợ (Phân trang + Lọc + Tìm kiếm + Stats)
     */
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
    public function close(string $id): JsonResponse
    {
        $this->ticketService->closeTicket($id);

        return response()->json([
            'success' => true,
            'message' => 'Đóng yêu cầu hỗ trợ thành công.',
        ]);
    }
}
