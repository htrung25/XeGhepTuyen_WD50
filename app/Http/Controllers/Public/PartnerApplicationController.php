<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePartnerApplicationRequest;
use App\Services\PartnerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PartnerApplicationController extends Controller
{
    public function __construct(
        private readonly PartnerApplicationService $applicationService,
    ) {}

    public function store(StorePartnerApplicationRequest $request): JsonResponse
    {
        try {
            $application = $this->applicationService->submit(
                $request->safe()->except(['business_license', 'fleet_images']),
                $request->file('business_license'),
                $request->file('fleet_images', []),
            );

            return response()->json([
                'success' => true,
                'message' => 'Gửi yêu cầu đăng ký thành công! Chúng tôi sẽ liên hệ lại trong vòng 24h làm việc.',
                'data' => ['id' => $application->id],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Partner application submit failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra, vui lòng thử lại sau',
            ], 500);
        }
    }
}
