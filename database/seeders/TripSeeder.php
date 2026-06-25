<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $operator = Operator::where('status', 'verified')->first();
        if (!$operator) {
            $this->command->warn('No verified operator found. Run OperatorSeeder first.');
            return;
        }

        // Route HN ↔ HP
        $route = Route::firstOrCreate(
            ['name' => 'Hà Nội → Hải Phòng', 'operator_id' => $operator->id],
            [
                'operator_id'     => $operator->id,
                'name'            => 'Hà Nội → Hải Phòng',
                'origin_city'     => 'Hà Nội',
                'dest_city'       => 'Hải Phòng',
                'distance_km'     => 105,
                'est_duration_min'=> 150,
                'base_price'      => 120000,
                'is_active'       => true,
            ]
        );

        // Stops HN → HP
        $stops = [
            ['stop_name' => 'Bến xe Nước Ngầm',   'address' => 'Bến xe Nước Ngầm, Hoàng Mai, Hà Nội',    'stop_order' => 1, 'lat' => 20.9727, 'lng' => 105.8432, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Bến xe Giáp Bát',     'address' => 'Bến xe Giáp Bát, Hoàng Mai, Hà Nội',     'stop_order' => 2, 'lat' => 20.9847, 'lng' => 105.8479, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Cầu Vượt Pháp Vân',  'address' => 'Nút giao Pháp Vân, Hà Nội',               'stop_order' => 3, 'lat' => 20.9623, 'lng' => 105.8512, 'is_pickup' => true,  'is_dropoff' => false],
            ['stop_name' => 'Trung tâm Hải Phòng', 'address' => '1 Lạch Tray, Ngô Quyền, Hải Phòng',      'stop_order' => 4, 'lat' => 20.8529, 'lng' => 106.6877, 'is_pickup' => false, 'is_dropoff' => true],
            ['stop_name' => 'Bến xe Lạc Long',     'address' => 'Bến xe Lạc Long, Lê Chân, Hải Phòng',    'stop_order' => 5, 'lat' => 20.8611, 'lng' => 106.6753, 'is_pickup' => false, 'is_dropoff' => true],
            ['stop_name' => 'Sân bay Cát Bi',      'address' => 'Sân bay Cát Bi, Ngô Quyền, Hải Phòng',   'stop_order' => 6, 'lat' => 20.8197, 'lng' => 106.7241, 'is_pickup' => false, 'is_dropoff' => true],
        ];

        foreach ($stops as $stop) {
            RouteStop::firstOrCreate(
                ['route_id' => $route->id, 'stop_order' => $stop['stop_order']],
                array_merge($stop, ['route_id' => $route->id])
            );
        }

        // Create 2 vehicles
        $vehicle1 = Vehicle::firstOrCreate(
            ['plate_number' => '29A-12345'],
            [
                'operator_id'         => $operator->id,
                'plate_number'        => '29A-12345',
                'vehicle_type'        => VehicleType::Van9,
                'brand'               => 'Ford',
                'model'               => 'Transit',
                'year'                => 2023,
                'color'               => 'Trắng',
                'seat_count'          => 9,
                'registration_expiry' => now()->addYear(),
                'amenities'           => ['wifi', 'usb', 'điều_hoà'],
                'status'              => VehicleStatus::Active,
            ]
        );

        $vehicle2 = Vehicle::firstOrCreate(
            ['plate_number' => '29B-67890'],
            [
                'operator_id'         => $operator->id,
                'plate_number'        => '29B-67890',
                'vehicle_type'        => VehicleType::Minibus16,
                'brand'               => 'Hyundai',
                'model'               => 'Solati',
                'year'                => 2022,
                'color'               => 'Bạc',
                'seat_count'          => 12,
                'registration_expiry' => now()->addYear(),
                'amenities'           => ['wifi', 'usb', 'điều_hoà', 'cửa_sổ_panoramic'],
                'status'              => VehicleStatus::Active,
            ]
        );

        // Create 2 driver users
        $driverUser1 = User::firstOrCreate(
            ['phone' => '0931111111'],
            [
                'full_name'   => 'Nguyễn Văn Tài',
                'email'       => 'driver1@xeghep.vn',
                'password'    => Hash::make('Driver@123456'),
                'role'        => UserRole::Driver,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        $driver1 = Driver::firstOrCreate(
            ['user_id' => $driverUser1->id],
            [
                'operator_id'        => $operator->id,
                'user_id'            => $driverUser1->id,
                'license_number'     => 'B2-123456',
                'license_class'      => 'D',
                'license_expiry'     => now()->addYears(3),
                'id_card_number'     => '001199012345',
                'id_card_front_url'  => null,
                'id_card_back_url'   => null,
                'license_front_url'  => null,
                'status'      => DriverStatus::Verified,
                'rating_avg'  => 4.8,
                'total_trips' => 150,
                'verified_at' => now(),
            ]
        );

        $driverUser2 = User::firstOrCreate(
            ['phone' => '0932222222'],
            [
                'full_name'   => 'Trần Văn Nam',
                'email'       => 'driver2@xeghep.vn',
                'password'    => Hash::make('Driver@123456'),
                'role'        => UserRole::Driver,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );

        $driver2 = Driver::firstOrCreate(
            ['user_id' => $driverUser2->id],
            [
                'operator_id'        => $operator->id,
                'user_id'            => $driverUser2->id,
                'license_number'     => 'B2-654321',
                'license_class'      => 'D',
                'license_expiry'     => now()->addYears(2),
                'id_card_number'     => '001199054321',
                'id_card_front_url'  => null,
                'id_card_back_url'   => null,
                'license_front_url'  => null,
                'status'             => DriverStatus::Verified,
                'rating_avg'  => 4.7,
                'total_trips' => 80,
                'verified_at' => now(),
            ]
        );

        // Tạo chuyến cho 7 ngày (hôm nay → +6) để luôn có chuyến sắp tới ở mọi thời điểm
        $departures = ['06:00', '07:30', '09:00', '10:30', '12:00', '13:30', '15:00', '16:30', '18:00', '19:30'];
        $totalDays  = 7;
        $created    = 0;

        for ($dayOffset = 0; $dayOffset < $totalDays; $dayOffset++) {
            $day = today()->copy()->addDays($dayOffset);

            foreach ($departures as $index => $time) {
                $vehicle  = $index % 2 === 0 ? $vehicle1 : $vehicle2;
                $driver   = $index % 2 === 0 ? $driver1 : $driver2;
                $departAt = $day->copy()->setTimeFromTimeString($time);
                $arriveAt = $departAt->copy()->addMinutes(150);
                $seats    = $vehicle->seat_count;

                $code = 'HNHP' . $day->format('ymd') . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                $trip = Trip::firstOrCreate(
                    ['tracking_code' => $code],
                    [
                        'route_id'        => $route->id,
                        'vehicle_id'      => $vehicle->id,
                        'driver_id'       => $driver->id,
                        'tracking_code'   => $code,
                        'depart_at'       => $departAt,
                        'arrive_at'       => $arriveAt,
                        'price'           => 120000,
                        'available_seats' => $seats,
                        'status'          => TripStatus::Scheduled,
                    ]
                );

                // Generate seat map if not exists
                if ($trip->seatMaps()->count() === 0) {
                    for ($seat = 1; $seat <= $seats; $seat++) {
                        SeatMap::create([
                            'trip_id'   => $trip->id,
                            'seat_code' => 'A' . str_pad($seat, 2, '0', STR_PAD_LEFT),
                            'seat_type' => SeatType::Standard,
                            'price'     => 120000,
                            'status'    => SeatStatus::Available,
                        ]);
                    }
                }
                $created++;
            }
        }

        $this->command->info("TripSeeder: {$created} trips created across {$totalDays} days from " . today()->format('Y-m-d'));
    }
}
