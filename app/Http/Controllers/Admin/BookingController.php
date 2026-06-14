<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingRepositoryInterface $bookingRepo) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingRepo->findAllForAdmin([
            'status'         => $request->status,
            'payment_status' => $request->payment_status,
            'search'         => $request->search,
            'date_from'      => $request->from_date,
            'date_to'        => $request->to_date,
        ]);

        return response()->json([
            'success' => true,
            'data'    => collect($bookings->items())->map(fn (Booking $b) => [
                'id'             => $b->id,
                'code'           => $b->booking_code,
                'customer'       => $b->contact_name ?? $b->user?->full_name,
                'phone'          => $b->contact_phone ?? $b->user?->phone,
                'route'          => $b->trip?->route
                    ? $b->trip->route->origin_city . ' → ' . $b->trip->route->dest_city
                    : '—',
                'depart_at'      => $b->trip?->depart_at?->format('Y-m-d H:i'),
                'passenger_count'=> (int) $b->passenger_count,
                'amount'         => (int) $b->final_amount,
                'payment_status' => $b->payment_status->value,
                'status'         => $b->booking_status->value,
                'created_at'     => $b->created_at->format('Y-m-d H:i'),
            ]),
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
                'last_page'    => $bookings->lastPage(),
            ],
        ]);
    }
}
