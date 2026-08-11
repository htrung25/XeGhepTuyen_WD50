<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $baseQuery = Notification::query()->forUser($userId)->inApp();
        $notifications = (clone $baseQuery)->orderByDesc('sent_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => (clone $baseQuery)->unread()->count(),
            ],
        ]);
    }

    public function pendingCounts(Request $request): JsonResponse
    {
        $operatorId = $request->user()->operator->id;

        $count = Trip::query()
            ->whereNotNull('driver_unavailable_at')
            ->whereHas('route', fn ($query) => $query->where('operator_id', $operatorId))
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['/operator/trips' => $count],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::query()
            ->forUser($request->user()->id)
            ->inApp()
            ->find($id);

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Thông báo không tồn tại'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->inApp()
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu tất cả là đã đọc']);
    }
}
