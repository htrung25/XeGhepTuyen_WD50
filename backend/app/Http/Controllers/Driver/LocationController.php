<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateLocationRequest;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LocationController extends Controller
{
    public function __construct(private readonly TrackingService $trackingService) {}

    public function update(UpdateLocationRequest $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        // Chống spam GPS: tối đa 1 lần / 10 giây MỖI tài xế (§4.4). Đếm qua RateLimiter
        // (backend cache = Redis). Client hợp lệ gửi mỗi 15s nên không chạm ngưỡng.
        $rlKey = "driver-gps:{$driver->id}";
        if (RateLimiter::tooManyAttempts($rlKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Gửi vị trí quá nhanh, vui lòng thử lại sau vài giây',
            ], 429);
        }
        RateLimiter::hit($rlKey, 10);

        try {
            $this->trackingService->updateLocation($driver, $request->lat, $request->lng);

            return response()->json(['success' => true, 'message' => 'Vị trí đã được cập nhật']);
        } catch (\Exception $e) {
            Log::error('Location update failed', ['driver' => $driver->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi khi cập nhật vị trí'], 500);
        }
    }
}
