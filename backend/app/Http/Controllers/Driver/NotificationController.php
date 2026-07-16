<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('sent_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'unread_count' => Notification::where('user_id', $user->id)->unread()->count(),
            'meta' => ['current_page' => $notifications->currentPage(), 'total' => $notifications->total()],
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
}
