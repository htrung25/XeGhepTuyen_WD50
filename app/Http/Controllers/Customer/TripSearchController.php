<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\TripSearchResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class TripSearchController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripRepositoryInterface $tripRepo,
    ) {}

    #[OA\Get(
        path: "/api/public/trips",
        summary: "Tìm kiếm chuyến xe ghép",
        tags: ["Trips"]
    )]
    #[OA\QueryParameter(name: "from_city", required: true, description: "Thành phố xuất phát", schema: new OA\Schema(type: "string"))]
    #[OA\QueryParameter(name: "to_city", required: true, description: "Thành phố đến", schema: new OA\Schema(type: "string"))]
    #[OA\QueryParameter(name: "date", required: true, description: "Ngày đi (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date"))]
    #[OA\QueryParameter(name: "passengers", required: false, description: "Số lượng hành khách (1-4)", schema: new OA\Schema(type: "integer", default: 1))]
    #[OA\QueryParameter(name: "sort", required: false, description: "Sắp xếp kết quả", schema: new OA\Schema(type: "string", enum: ["price_asc", "price_desc", "depart_asc"]))]
    #[OA\Response(
        response: 200,
        description: "Danh sách chuyến xe hợp lệ",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                new OA\Property(property: "meta", type: "object")
            ]
        )
    )]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'from_city'  => ['required', 'string'],
            'to_city'    => ['required', 'string'],
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:4'],
            'sort'       => ['nullable', 'in:price_asc,price_desc,depart_asc'],
        ]);

        try {
            $trips = $this->tripService->search($request->only(['from_city', 'to_city', 'date', 'passengers', 'sort']));

            return response()->json([
                'success' => true,
                'data'    => TripSearchResource::collection(collect($trips)),
                'meta'    => ['total' => count($trips)],
            ]);
        } catch (\Exception $e) {
            Log::error('Trip search failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tìm kiếm'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new TripSearchResource($trip),
        ]);
    }

    public function seats(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        $seats = $trip->seatMaps->map(fn($seat) => [
            'id'        => $seat->id,
            'seat_code' => $seat->seat_code,
            'seat_type' => $seat->seat_type->value,
            'price'     => $seat->price,
            'status'    => $seat->isLockExpired() ? 'available' : $seat->status->value,
        ]);

        return response()->json(['success' => true, 'data' => $seats]);
    }

    public function track(string $trackingCode): JsonResponse
    {
        $trip = $this->tripRepo->findByTrackingCode($trackingCode);

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy chuyến xe'], 404);
        }

        $location = null;
        if ($trip->isActive()) {
            $location = app(\App\Services\TrackingService::class)->getLocation($trip->driver);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'trip_id'      => $trip->id,
                'status'       => $trip->status->value,
                'depart_at'    => $trip->depart_at->format('H:i d/m/Y'),
                'driver_name'  => $trip->driver->user->full_name,
                'plate_number' => $trip->vehicle->plate_number,
                'location'     => $location,
            ],
        ]);
    }
}
