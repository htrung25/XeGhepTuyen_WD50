<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateTicketRequest;
use App\Http\Requests\Customer\ListSupportTicketsRequest;
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
    public function index(ListSupportTicketsRequest $request): JsonResponse
    {
        $user = auth('customer')->user();
        $validated = $request->validated();

        $query = SupportTicket::where('user_id', $user->id);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $tickets = $query
            ->withCount([
                'messages as message_count' => fn ($query) => $query->where('is_internal', false),
            ])
            ->withMax([
                'messages as last_reply_at' => fn ($query) => $query->where('is_internal', false),
            ], 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = SupportTicket::where('user_id', $user->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'stats' => [
                    'open' => (int) ($stats['open'] ?? 0),
                    'in_progress' => (int) ($stats['in_progress'] ?? 0),
                    'resolved' => (int) ($stats['resolved'] ?? 0),
                    'closed' => (int) ($stats['closed'] ?? 0),
                ],
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
        ], 201);
    }

    /**
     * Xem chi tiết ticket hỗ trợ kèm hội thoại chat
     */
    public function show(string $id): JsonResponse
    {
        $user = auth('customer')->user();
        $ticket = SupportTicket::findOrFail($id);

        if ($ticket->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập yêu cầu hỗ trợ này.',
            ], 403);
        }

        $ticket->load(['messages' => function ($query) {
            $query->where('is_internal', false)->orderBy('created_at', 'asc');
        }]);

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
        try {
            $result = $this->ticketService->replyAsCustomer($user, $id, $request->input('body'));

            return response()->json([
                'success' => true,
                'message' => 'Gửi tin nhắn phản hồi thành công.',
                'data' => $result->message,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
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
        try {
            $this->ticketService->closeAsCustomer($user, $id);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đóng yêu cầu hỗ trợ thành công.',
        ]);
    }
}
