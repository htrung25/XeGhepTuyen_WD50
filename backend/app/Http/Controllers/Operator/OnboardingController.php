<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\PartnerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Tiến độ onboarding đội xe: cơ cấu đã khai lúc đăng ký vs số xe thực tế đã thêm.
     */
    public function fleet(Request $request): JsonResponse
    {
        $operator = $request->user()->operator;

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhà xe'], 404);
        }

        $application = $operator->partnerApplication;
        $declaredTotal = $application?->vehicle_count ?? 0;
        $actualCount = $operator->vehicles()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'declared_fleet' => $application?->fleet_breakdown ?? [],
                'declared_summary' => $application?->fleetSummary() ?? '—',
                'declared_total' => $declaredTotal,
                'actual_count' => $actualCount,
                'remaining' => max(0, $declaredTotal - $actualCount),
                'fleet_labels' => PartnerApplication::FLEET_LABELS,
            ],
        ]);
    }
}
