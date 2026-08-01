<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTicketRequest;
use App\Http\Requests\Admin\ListSupportTicketsRequest;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Http\Requests\Admin\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\AuditLogService;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Danh sách toàn bộ ticket hỗ trợ (Phân trang + Lọc + Tìm kiếm + Stats)
     */
    public function index(ListSupportTicketsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = SupportTicket::with('user:id,full_name,phone,email')
            ->with('assignee:id,full_name')
            ->withCount('messages as message_count')
            ->withMax('messages as last_reply_at', 'created_at')
            ->orderBy('created_at', 'desc');

        // Lọc theo trạng thái
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Lọc theo danh mục
        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        // Lọc theo mức độ ưu tiên
        if (! empty($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        // Tìm kiếm (Mã ticket, tiêu đề, tên khách hàng, SĐT khách hàng)
        if (! empty($validated['search'])) {
            $search = $validated['search'];
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
                'stats' => $stats,
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
            'assignee:id,full_name',
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
            $result = $this->ticketService->replyAsAdmin(
                $admin,
                $id,
                $request->input('body'),
                $request->boolean('is_internal'),
            );

            $this->auditLog->log(
                action: 'reply_support_ticket',
                model: $result->ticket,
                description: $request->boolean('is_internal')
                    ? "Đã thêm ghi chú nội bộ cho yêu cầu {$result->ticket->ticket_code}"
                    : "Đã phản hồi yêu cầu hỗ trợ {$result->ticket->ticket_code}",
                oldValues: $result->oldValues,
                newValues: $result->newValues,
                actor: $admin,
            );

            return response()->json([
                'success' => true,
                'message' => 'Trả lời yêu cầu hỗ trợ thành công.',
                'data' => $result->message,
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
        $admin = auth('admin')->user();
        try {
            $result = $this->ticketService->assignTicket($id, $request->input('assigned_to'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'assign_support_ticket',
            model: $result->ticket,
            description: "Đã phân công yêu cầu hỗ trợ {$result->ticket->ticket_code}",
            oldValues: $result->oldValues,
            newValues: $result->newValues,
            actor: $admin,
        );

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
        $admin = auth('admin')->user();
        try {
            $result = $this->ticketService->resolveTicket($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'resolve_support_ticket',
            model: $result->ticket,
            description: "Đã giải quyết yêu cầu hỗ trợ {$result->ticket->ticket_code}",
            oldValues: $result->oldValues,
            newValues: $result->newValues,
            actor: $admin,
        );

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
        $admin = auth('admin')->user();
        try {
            $result = $this->ticketService->closeTicket($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'close_support_ticket',
            model: $result->ticket,
            description: "Đã đóng yêu cầu hỗ trợ {$result->ticket->ticket_code}",
            oldValues: $result->oldValues,
            newValues: $result->newValues,
            actor: $admin,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đóng yêu cầu hỗ trợ thành công.',
        ]);
    }

    /**
     * Cập nhật trạng thái và mức độ ưu tiên theo state machine của ticket.
     */
    public function update(UpdateSupportTicketRequest $request, string $id): JsonResponse
    {
        $admin = auth('admin')->user();
        $validated = $request->validated();

        try {
            $result = $this->ticketService->updateTicket(
                $id,
                isset($validated['status']) ? TicketStatusEnum::from($validated['status']) : null,
                isset($validated['priority']) ? TicketPriorityEnum::from($validated['priority']) : null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'update_support_ticket',
            model: $result->ticket,
            description: "Đã cập nhật yêu cầu hỗ trợ {$result->ticket->ticket_code}",
            oldValues: $result->oldValues,
            newValues: $result->newValues,
            actor: $admin,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật yêu cầu hỗ trợ thành công.',
            'data' => $result->ticket,
        ]);
    }
}
