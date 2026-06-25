<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\DriverStatus;
use App\Enums\OperatorStatus;
use App\Enums\SeatStatus;
use App\Enums\SeatType;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DuskTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@xeghep.vn'],
            [
                'full_name'   => 'Super Admin',
                'phone'       => '0900000000',
                'password'    => Hash::make('Admin@123456'),
                'role'        => UserRole::Admin,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        // ── Operator (Bắc Hà) ─────────────────────────────────────────────────
        $operatorUser = User::firstOrCreate(
            ['phone' => '0900000001'],
            [
                'full_name'   => 'Nhà xe Bắc Hà',
                'email'       => 'operator@bacha.vn',
                'phone'       => '0900000001',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Operator,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        $operator = Operator::firstOrCreate(
            ['user_id' => $operatorUser->id],
            [
                'user_id'          => $operatorUser->id,
                'company_name'     => 'Nhà xe Bắc Hà',
                'tax_code'         => '0100000001',
                'business_license' => 'GP-VCHK-TEST01',
                'status'           => OperatorStatus::Verified,
                'commission_rate'  => 10.00,
                'verified_at'      => now(),
            ]
        );

        // ── Driver ────────────────────────────────────────────────────────────
        $driverUser = User::firstOrCreate(
            ['phone' => '0900000002'],
            [
                'full_name'   => 'Nguyễn Văn Tài',
                'email'       => 'driver@bacha.vn',
                'phone'       => '0900000002',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Driver,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        $driver = Driver::firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'operator_id'       => $operator->id,
                'user_id'           => $driverUser->id,
                'license_number'    => 'B2-DUSK001',
                'license_class'     => 'D',
                'license_expiry'    => now()->addYears(3),
                'id_card_number'    => '001199DUSK01',
                'status'            => DriverStatus::Verified,
                'rating_avg'        => 4.9,
                'total_trips'       => 10,
                'verified_at'       => now(),
            ]
        );

        // ── Customer ─────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['phone' => '0900000003'],
            [
                'full_name'   => 'Khách Hàng Test',
                'email'       => 'customer@test.vn',
                'phone'       => '0900000003',
                'password'    => Hash::make('Test@123456'),
                'role'        => UserRole::Customer,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        // ── Vehicle (MPV 7 chỗ) ───────────────────────────────────────────────
        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => '30A-12345'],
            [
                'operator_id'         => $operator->id,
                'plate_number'        => '30A-12345',
                'vehicle_type'        => VehicleType::Mpv7,
                'brand'               => 'Toyota',
                'model'               => 'Innova',
                'year'                => 2023,
                'color'               => 'Trắng',
                'seat_count'          => 7,
                'registration_expiry' => now()->addYear(),
                'amenities'           => ['wifi', 'điều_hoà'],
                'status'              => VehicleStatus::Active,
            ]
        );

        // ── Route HN → HP ─────────────────────────────────────────────────────
        $route = Route::firstOrCreate(
            ['name' => 'Hà Nội → Hải Phòng', 'operator_id' => $operator->id],
            [
                'operator_id'      => $operator->id,
                'name'             => 'Hà Nội → Hải Phòng',
                'origin_city'      => 'Hà Nội',
                'dest_city'        => 'Hải Phòng',
                'distance_km'      => 105,
                'est_duration_min' => 150,
                'base_price'       => 150000,
                'is_active'        => true,
            ]
        );

        // ── Route Stops ───────────────────────────────────────────────────────
        $stops = [
            ['stop_name' => 'Mỹ Đình',      'address' => 'Mỹ Đình, Nam Từ Liêm, Hà Nội',       'stop_order' => 1, 'lat' => 21.0285, 'lng' => 105.7817, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Cầu Giấy',     'address' => 'Cầu Giấy, Hà Nội',                   'stop_order' => 2, 'lat' => 21.0333, 'lng' => 105.8000, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Trung Hòa',    'address' => 'Trung Hòa, Cầu Giấy, Hà Nội',         'stop_order' => 3, 'lat' => 21.0089, 'lng' => 105.8002, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Gia Lâm',      'address' => 'Gia Lâm, Long Biên, Hà Nội',           'stop_order' => 4, 'lat' => 21.0200, 'lng' => 105.9000, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Trung tâm HP', 'address' => '1 Lạch Tray, Ngô Quyền, Hải Phòng',   'stop_order' => 5, 'lat' => 20.8529, 'lng' => 106.6877, 'is_pickup' => false, 'is_dropoff' => true],
        ];

        foreach ($stops as $stop) {
            RouteStop::firstOrCreate(
                ['route_id' => $route->id, 'stop_order' => $stop['stop_order']],
                array_merge($stop, ['route_id' => $route->id])
            );
        }

        // ── 3 Trips ngày mai (06:00, 10:00, 14:00) ────────────────────────────
        // Use tomorrow so trips are always in the future regardless of test run time.
        $departures = [
            ['time' => '06:00', 'code_suffix' => '001'],
            ['time' => '10:00', 'code_suffix' => '002'],
            ['time' => '14:00', 'code_suffix' => '003'],
        ];

        foreach ($departures as $departure) {
            $departAt = today()->addDay()->setTimeFromTimeString($departure['time']);
            $arriveAt = $departAt->copy()->addMinutes(150);
            $code     = 'HNHP' . today()->addDay()->format('ymd') . $departure['code_suffix'];

            $trip = Trip::firstOrCreate(
                ['tracking_code' => $code],
                [
                    'route_id'        => $route->id,
                    'vehicle_id'      => $vehicle->id,
                    'driver_id'       => $driver->id,
                    'tracking_code'   => $code,
                    'depart_at'       => $departAt,
                    'arrive_at'       => $arriveAt,
                    'price'           => 150000,
                    'available_seats' => 7,
                    'status'          => TripStatus::Scheduled,
                ]
            );

            // Seat map: A1, A2, B1, B2, B3, C1, C2
            if ($trip->seatMaps()->count() === 0) {
                $seatCodes = ['A1', 'A2', 'B1', 'B2', 'B3', 'C1', 'C2'];
                foreach ($seatCodes as $seatCode) {
                    SeatMap::create([
                        'trip_id'   => $trip->id,
                        'seat_code' => $seatCode,
                        'seat_type' => SeatType::Standard,
                        'price'     => 150000,
                        'status'    => SeatStatus::Available,
                    ]);
                }
            }
        }

        // ── Voucher WELCOME50 ─────────────────────────────────────────────────
        Voucher::firstOrCreate(
            ['code' => 'WELCOME50'],
            [
                'operator_id'    => null,
                'code'           => 'WELCOME50',
                'discount_type'  => DiscountType::Fixed,
                'discount_value' => 50000,
                'min_order'      => 100000,
                'max_discount'   => 50000,
                'usage_limit'    => 9999,
                'used_count'     => 0,
                'valid_from'     => now()->subDay(),
                'valid_until'    => now()->addYear(),
                'is_active'      => true,
            ]
        );

        $this->command->info('DuskTestSeeder: tạo xong dữ liệu test cố định.');
    }
}
