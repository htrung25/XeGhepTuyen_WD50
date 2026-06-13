<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateLocationRequest;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function __construct(private readonly TrackingService $trackingService) {}

    public function update(UpdateLocationRequest $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        try {
            $this->trackingService->updateLocation($driver, $request->lat, $request->lng);

            return response()->json(['success' => true, 'message' => 'Vị trí đã được cập nhật']);
        } catch (\Exception $e) {
            Log::error('Location update failed', ['driver' => $driver->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi khi cập nhật vị trí'], 500);
        }
    }
}
