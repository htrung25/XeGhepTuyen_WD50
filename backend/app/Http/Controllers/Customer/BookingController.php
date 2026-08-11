<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\LocationOutsideServiceAreaException;
use App\Exceptions\SeatNotAvailableException;
use App\Exceptions\ServiceAreaNotConfiguredException;
use App\Exceptions\TripNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CancelBookingRequest;
use App\Http\Requests\Customer\LockSeatsRequest;
use App\Http\Requests\Customer\StoreBookingRequest;
use App\Http\Resources\Customer\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\BookingService;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingRepositoryInterface $bookingRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingRepo->findByUser(
            auth('customer')->id(),
            $request->only(['status'])
        );

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $booking = $this->bookingRepo->findById($id);

        if (! $booking || $booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new BookingResource($booking)]);
    }

    public function lockSeats(LockSeatsRequest $request): JsonResponse
    {
        try {
            $this->bookingService->lockSeats(
                $request->seat_ids,
                auth('customer')->id(),
                $request->trip_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã giữ ghế trong 10 phút',
            ]);
        } catch (SeatNotAvailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'SEAT_NOT_AVAILABLE'], 422);
        } catch (\Exception $e) {
            Log::error('LockSeats failed', ['error' => $e->getMessage(), 'user' => auth('customer')->id()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->create(
                $request->validated(),
                auth('customer')->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'data' => new BookingResource($booking->load(['passengers', 'pickupStop', 'trip.route'])),
            ], 201);
        } catch (SeatNotAvailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'SEAT_NOT_AVAILABLE'], 422);
        } catch (TripNotAvailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'TRIP_NOT_AVAILABLE'], 422);
        } catch (ServiceAreaNotConfiguredException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'SERVICE_AREA_NOT_CONFIGURED'], 422);
        } catch (LocationOutsideServiceAreaException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'LOCATION_OUTSIDE_SERVICE_AREA'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Booking create failed', ['error' => $e->getMessage(), 'user' => auth('customer')->id()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại sau'], 500);
        }
    }

    public function cancel(CancelBookingRequest $request, string $id): JsonResponse
    {
        $booking = $this->bookingRepo->findById($id);

        if (! $booking || $booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        try {
            $result = $this->bookingService->cancel(
                $booking,
                auth('customer')->user(),
                (string) ($request->validated('reason') ?? ''),
            );

            return response()->json([
                'success' => true,
                'message' => 'Hủy vé thành công',
                'data' => [
                    'refund_percent' => $result['refund_percent'],
                    'refund_amount' => $result['refund_amount'],
                    'refund_note' => $result['refund_amount'] > 0
                        ? 'Tiền hoàn sẽ về ví trong 3-5 ngày làm việc'
                        : 'Không có hoàn tiền',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Booking cancel failed', ['error' => $e->getMessage(), 'booking' => $id]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    public function qr(string $id, QrCodeService $qrCodeService): JsonResponse
    {
        $booking = $this->bookingRepo->findById($id);

        if (! $booking || $booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        if (! $booking->canAccessTicket()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng hoàn tất thanh toán trước khi xem mã QR',
                'code' => 'PAYMENT_REQUIRED',
            ], 409);
        }

        return response()->json([
            'success' => true,
            // Sinh trực tiếp từ token đã lưu: không phụ thuộc file public trên
            // filesystem tạm của container Laravel Cloud.
            'data' => [
                'qr_code' => 'data:image/svg+xml;base64,'.base64_encode(
                    $qrCodeService->renderSvg($booking)
                ),
            ],
        ]);
    }

    public function downloadTicket(string $id, QrCodeService $qrCodeService): Response|JsonResponse
    {
        $booking = $this->bookingRepo->findById($id);

        if (! $booking || $booking->user_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        if (! $booking->canAccessTicket()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng hoàn tất thanh toán trước khi tải vé',
                'code' => 'PAYMENT_REQUIRED',
            ], 409);
        }

        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode(
            $qrCodeService->renderSvg($booking)
        );

        return Pdf::loadView('pdfs.customer-ticket', [
            'booking' => $booking,
            'qrDataUri' => $qrDataUri,
        ])->setPaper('a5')->download("ve-{$booking->booking_code}.pdf");
    }
}
