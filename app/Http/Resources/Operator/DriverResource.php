<?php

namespace App\Http\Resources\Operator;

use App\Enums\DriverStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->user?->full_name,
            'phone' => $this->user?->phone,
            'rating_avg' => $this->rating_avg !== null ? (float) $this->rating_avg : null,
            'is_online' => (bool) $this->is_online,
            'is_active' => $this->status === DriverStatus::Verified,
            'status' => $this->status?->value,
            'status_label' => match ($this->status) {
                DriverStatus::Pending => 'Chờ duyệt',
                DriverStatus::Verified => 'Đã duyệt',
                DriverStatus::Suspended => 'Đình chỉ',
                DriverStatus::Rejected => 'Từ chối',
                default => '—',
            },
            'license_class' => $this->license_class,
            'license_expiry' => $this->license_expiry?->format('d/m/Y'),
            'total_trips' => (int) $this->total_trips,
            'current_vehicle_id' => $this->current_vehicle_id,
            'current_vehicle' => $this->currentVehicle ? [
                'plate_number' => $this->currentVehicle->plate_number,
                'vehicle_type' => $this->currentVehicle->vehicle_type?->value,
            ] : null,
        ];
    }
}
