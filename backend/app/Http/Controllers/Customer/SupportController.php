<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateTicketRequest;
use App\Http\Requests\Customer\ReplyMessageRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService
    ) {}

    /**
     * Lấy danh sách ticket hỗ trợ của chính khách hàng đăng nhập
     */
    public function index(): JsonResponse
    {
        $user = auth('customer')->user();

        $tickets = SupportTicket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
            ],
        ]);
    }

    /**
     * Khách hàng tạo ticket hỗ trợ mới
     */
    public function store(CreateTicketRequest $request): JsonResponse
    {
        $user = auth('customer')->user();
        $ticket = $this->ticketService->createTicket($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo yêu cầu hỗ trợ thành công.',
            'data' => $ticket,
        ], 210); // Custom success code or 201 Created
    }

    /**
     * Xem chi tiết ticket hỗ trợ kèm hội thoại chat
     */
    public function show(string $id): JsonResponse
    {
        $user = auth('customer')->user();
        $ticket = SupportTicket::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        if ($ticket->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập yêu cầu hỗ trợ này.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Khách hàng gửi phản hồi chat
     */
    public function reply(ReplyMessageRequest $request, string $id): JsonResponse
    {
        $user = auth('customer')->user();
        $ticket = SupportTicket::findOrFail($id);

        if ($ticket->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền phản hồi yêu cầu hỗ trợ này.',
            ], 403);
        }

        try {
            $message = $this->ticketService->replyToTicket($user, $id, $request->input('body'), 'customer');

            return response()->json([
                'success' => true,
                'message' => 'Gửi tin nhắn phản hồi thành công.',
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
     * Khách hàng đóng ticket hỗ trợ của chính họ
     */
    public function close(string $id): JsonResponse
    {
        $user = auth('customer')->user();
        $ticket = SupportTicket::findOrFail($id);

        if ($ticket->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền đóng yêu cầu hỗ trợ này.',
            ], 403);
        }

        $this->ticketService->closeTicket($id);

        return response()->json([
            'success' => true,
            'message' => 'Đã đóng yêu cầu hỗ trợ thành công.',
        ]);
    }
}
