<?php

namespace Database\Seeders;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SeatStatus;
use App\Enums\SeatType;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RevenueDemoSeeder extends Seeder
{
    public function run(): void
    {
        $op = Operator::where('company_name', 'Nhà xe Hoàng Long')->first();
        if (!$op) {
            $this->command->warn('Không tìm thấy nhà xe Hoàng Long. Chạy OperatorSeeder trước.');
            return;
        }

        // ── Dọn data demo cũ (idempotent) ──────────────────────────────────
        $oldTripIds = Trip::where('tracking_code', 'like', 'RVD%')->pluck('id');
        if ($oldTripIds->isNotEmpty()) {
            $oldBkIds = Booking::whereIn('trip_id', $oldTripIds)->pluck('id');
            Payment::whereIn('booking_id', $oldBkIds)->delete();
            BookingPassenger::whereIn('booking_id', $oldBkIds)->delete();
            Booking::whereIn('id', $oldBkIds)->delete();
            SeatMap::whereIn('trip_id', $oldTripIds)->delete();
            Trip::whereIn('id', $oldTripIds)->delete();
        }

        // ── Tuyến: tuyến chính + tuyến chiều ngược (để "theo tuyến" có 2 cột) ──
        $route1 = Route::where('operator_id', $op->id)->first();
        $route2 = Route::firstOrCreate(
            ['operator_id' => $op->id, 'origin_city' => 'Hải Phòng', 'dest_city' => 'Hà Nội'],
            ['name' => 'Hải Phòng → Hà Nội', 'distance_km' => 120, 'est_duration_min' => 120, 'base_price' => 130000, 'is_active' => true]
        );
        if (RouteStop::where('route_id', $route2->id)->count() === 0) {
            RouteStop::create(['route_id' => $route2->id, 'stop_name' => 'Lạch Tray', 'address' => 'Hải Phòng', 'lat' => 20.84, 'lng' => 106.68, 'stop_order' => 1, 'offset_minutes' => 0, 'is_pickup' => true, 'is_dropoff' => false]);
            RouteStop::create(['route_id' => $route2->id, 'stop_name' => 'Mỹ Đình', 'address' => 'Hà Nội', 'lat' => 21.02, 'lng' => 105.78, 'stop_order' => 2, 'offset_minutes' => 120, 'is_pickup' => false, 'is_dropoff' => true]);
        }

        $routes   = [$route1, $route2];
        $vehicles = Vehicle::where('operator_id', $op->id)->get()->values();
        $drivers  = Driver::where('operator_id', $op->id)->get()->values();
        $customers = User::where('role', 'customer')->take(10)->get();
        if ($customers->isEmpty()) {
            $customers = collect([User::create([
                'full_name' => 'Khách Demo', 'phone' => '0309999999',
                'password' => bcrypt('x'), 'role' => 'customer', 'is_verified' => true,
            ])]);
        }

        $tripCount = 0;
        $bookCount = 0;
        $totalRev  = 0;

        // ~12 chuyến completed rải đều từ đầu tháng tới hôm nay
        for ($i = 0; $i < 12; $i++) {
            $day = now()->startOfMonth()->addDays($i);
            if ($day->isAfter(now())) break;

            $route   = $routes[$i % 2];
            $vehicle = $vehicles[$i % $vehicles->count()];
            $driver  = $drivers[$i % $drivers->count()];
            $price   = (int) ($route->base_price ?? 130000);

            $departAt = $day->copy()->setTime(6 + ($i % 3) * 4, 0);
            $arriveAt = $departAt->copy()->addMinutes((int) $route->est_duration_min);

            $trip = Trip::create([
                'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
                'depart_at' => $departAt, 'arrive_at' => $arriveAt,
                'available_seats' => $vehicle->seat_count, 'price' => $price,
                'tracking_code' => 'RVD' . strtoupper(Str::random(6)),
                'status' => TripStatus::Completed, 'started_at' => $departAt, 'completed_at' => $arriveAt,
            ]);

            // Sơ đồ ghế
            $seats = [];
            for ($s = 1; $s <= $vehicle->seat_count; $s++) {
                $seats[] = SeatMap::create([
                    'trip_id' => $trip->id, 'seat_code' => 'A' . str_pad($s, 2, '0', STR_PAD_LEFT),
                    'seat_type' => SeatType::Standard, 'price' => $price, 'status' => SeatStatus::Booked,
                ]);
            }

            $pickup  = RouteStop::where('route_id', $route->id)->where('is_pickup', true)->first();
            $dropoff = RouteStop::where('route_id', $route->id)->where('is_dropoff', true)->orderByDesc('stop_order')->first();

            // Lấp 50–90% ghế bằng các vé completed + paid
            $fill = (int) round($vehicle->seat_count * (0.5 + mt_rand(0, 40) / 100));
            $seatIdx = 0;

            while ($seatIdx < $fill) {
                $pax    = min(mt_rand(1, 3), $fill - $seatIdx);
                $cust   = $customers[$bookCount % $customers->count()];
                $amount = $pax * $price;

                $booking = Booking::create([
                    'booking_code'   => 'RVD' . $departAt->format('ymd') . str_pad(++$bookCount, 4, '0', STR_PAD_LEFT),
                    'user_id'        => $cust->id, 'trip_id' => $trip->id,
                    'pickup_stop_id' => $pickup->id, 'dropoff_stop_id' => $dropoff->id,
                    'passenger_count' => $pax, 'contact_name' => $cust->full_name, 'contact_phone' => $cust->phone,
                    'subtotal' => $amount, 'final_amount' => $amount,
                    'payment_method' => PaymentMethod::Momo,
                    'payment_status' => BookingPaymentStatus::Paid,
                    'booking_status' => BookingStatus::Completed,
                    'qr_token' => Str::random(32),
                    'confirmed_at' => $departAt->copy()->subDay(), 'completed_at' => $arriveAt,
                ]);

                for ($p = 0; $p < $pax; $p++) {
                    BookingPassenger::create([
                        'booking_id' => $booking->id, 'seat_map_id' => $seats[$seatIdx]->id,
                        'full_name' => 'Khách ' . ($p + 1), 'is_primary' => $p === 0,
                    ]);
                    $seatIdx++;
                }

                Payment::create([
                    'booking_id' => $booking->id, 'user_id' => $cust->id, 'amount' => $amount,
                    'method' => PaymentMethod::Momo, 'status' => PaymentStatus::Success,
                    'gateway_order_id' => 'DEMO-' . strtoupper(Str::random(8)), 'paid_at' => $departAt,
                ]);

                $totalRev += $amount;
            }

            $trip->update(['available_seats' => $vehicle->seat_count - $seatIdx]);
            $tripCount++;
        }

        $this->command->info("RevenueDemoSeeder: {$tripCount} chuyến completed, {$bookCount} vé, doanh thu " . number_format($totalRev, 0, ',', '.') . 'đ');
    }
}
