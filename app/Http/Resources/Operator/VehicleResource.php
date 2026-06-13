<?php

namespace App\Http\Resources\Operator;

use App\Enums\VehicleStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'plate_number'     => $this->plate_number,
            'vehicle_type'     => $this->vehicle_type?->value,
            'seat_count'       => $this->seat_count,
            'manufacture_year' => $this->year,
            'image_url'        => $this->image_url,
            'is_active'         => $this->status === VehicleStatus::Active,
            'current_driver_id' => $this->assignedDriver?->id,
            'current_driver'    => $this->assignedDriver ? [
                'full_name' => $this->assignedDriver->user?->full_name,
                'phone'     => $this->assignedDriver->user?->phone,
            ] : null,
        ];
    }
}
