<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $vehicleIds = DB::table('vehicles')
            ->where('vehicle_type', 'limousine_12')
            ->where('seat_count', 12)
            ->pluck('id');

        if ($vehicleIds->isEmpty()) {
            return;
        }

        $tripIds = DB::table('trips')
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status', ['scheduled', 'boarding'])
            ->pluck('id');

        foreach ($tripIds as $tripId) {
            $seats = DB::table('seat_maps')
                ->where('trip_id', $tripId)
                ->where('status', '!=', 'disabled')
                ->orderBy('seat_code')
                ->get(['id', 'status']);

            if ($seats->count() <= 12) {
                continue;
            }

            $referencedIds = DB::table('booking_passengers')
                ->whereIn('seat_map_id', $seats->pluck('id'))
                ->pluck('seat_map_id');

            $protectedIds = $seats
                ->filter(fn (object $seat): bool => $seat->status !== 'available')
                ->pluck('id')
                ->merge($referencedIds)
                ->unique()
                ->values();

            // Không vô hiệu hóa ghế đã có booking/lock. Trường hợp dữ liệu lỗi
            // có trên 12 ghế được bảo vệ cần được xử lý thủ công.
            if ($protectedIds->count() > 12) {
                continue;
            }

            $keepIds = $protectedIds->merge(
                $seats
                    ->where('status', 'available')
                    ->whereNotIn('id', $protectedIds)
                    ->pluck('id')
                    ->take(12 - $protectedIds->count())
            );

            DB::table('seat_maps')
                ->where('trip_id', $tripId)
                ->where('status', 'available')
                ->whereNotIn('id', $keepIds)
                ->update(['status' => 'disabled']);

            DB::table('trips')
                ->where('id', $tripId)
                ->update([
                    'available_seats' => DB::table('seat_maps')
                        ->where('trip_id', $tripId)
                        ->where('status', 'available')
                        ->count(),
                ]);
        }
    }

    public function down(): void
    {
        // Không tự bật lại ghế vì không thể phân biệt ghế vốn đã hỏng với ghế
        // được vô hiệu hóa để sửa dữ liệu sức chứa cũ.
    }
};
