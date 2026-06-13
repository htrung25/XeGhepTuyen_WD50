<?php

namespace App\Http\Controllers\Operator;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Operator\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingRepositoryInterface $bookingRepo) {}

    public function index(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $bookings = $this->bookingRepo->findByOperator($operator->id, [
            'trip_id'   => $request->trip_id,
            'date'      => $request->date,
            'status'    => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'data'    => BookingResource::collection($bookings),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $booking  = $this->bookingRepo->findById($id);

        if (!$booking || $booking->trip->vehicle->operator_id !== $operator->id) {
            return response()->json(['success' => false, 'message' => 'Đặt chỗ không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new BookingResource($booking)]);
    }
}
