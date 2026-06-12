<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReviewRequest;
use App\Models\Booking;
use App\Models\Review;
use App\Jobs\UpdateDriverRatingJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $booking = Booking::with('trip')->findOrFail($request->booking_id);

        if ($booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        if ($booking->booking_status !== BookingStatus::Completed) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể đánh giá chuyến đã hoàn thành'], 422);
        }

        if ($booking->completed_at && $booking->completed_at->diffInDays() > 7) {
            return response()->json(['success' => false, 'message' => 'Thời gian đánh giá đã hết (trong vòng 7 ngày)'], 422);
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Bạn đã đánh giá chuyến này rồi'], 422);
        }

        try {
            $review = Review::create([
                'booking_id'     => $booking->id,
                'user_id'        => auth('customer')->id(),
                'driver_id'      => $booking->trip->driver_id,
                'operator_id'    => $booking->trip->route->operator_id,
                'driver_rating'  => $request->driver_rating,
                'vehicle_rating' => $request->vehicle_rating,
                'service_rating' => $request->service_rating,
                'comment'        => $request->comment,
            ]);

            UpdateDriverRatingJob::dispatch($booking->trip->driver_id)->onQueue('default');

            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã đánh giá!',
                'data'    => ['review_id' => $review->id],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Review create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }
}
