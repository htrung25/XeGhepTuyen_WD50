<?php

namespace App\Http\Resources\Operator;

use App\Enums\DriverStatusEnum;
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
            'is_active' => $this->status === DriverStatusEnum::Verified,
            'status' => $this->status?->value,
            'status_label' => match ($this->status) {
                DriverStatusEnum::Pending => 'Chờ duyệt',
                DriverStatusEnum::Verified => 'Đã duyệt',
                DriverStatusEnum::Suspended => 'Đình chỉ',
                DriverStatusEnum::Rejected => 'Từ chối',
                default => '—',
            },
            'license_number' => $this->license_number,
            'license_class' => $this->license_class,
            'license_expiry' => $this->license_expiry?->toDateString(),
            'id_card_number' => $this->id_card_number,
            'id_card_front_url' => $this->id_card_front_url,
            'id_card_back_url' => $this->id_card_back_url,
            'license_front_url' => $this->license_front_url,
            'total_trips' => (int) $this->total_trips,
            'current_vehicle_id' => $this->current_vehicle_id,
            'current_vehicle' => $this->currentVehicle ? [
                'plate_number' => $this->currentVehicle->plate_number,
                'vehicle_type' => $this->currentVehicle->vehicle_type?->value,
            ] : null,
        ];
    }
}
