<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = Notification::where('user_id', $userId)
            ->orderByDesc('sent_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => Notification::where('user_id', $userId)->unread()->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Thông báo không tồn tại'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->unread()->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu tất cả là đã đọc']);
    }
}
