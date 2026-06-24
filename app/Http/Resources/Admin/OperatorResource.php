<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Operator\VehicleResource;
use App\Http\Resources\Admin\DriverResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'tax_code' => $this->tax_code,
            'tax_number' => $this->tax_code, // Alias for compatibility
            'business_license' => $this->business_license,
            'license_number' => $this->business_license, // Alias for compatibility
            'status' => $this->status->value,
            'commission_rate' => $this->commission_rate,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'reject_reason' => $this->reject_reason,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'bank_account_name' => $this->bank_account_name,
            'logo_url' => $this->logo_url,
            'description' => $this->description,
            'total_vehicles' => $this->vehicles_count ?? $this->vehicles?->count(),
            'total_drivers' => $this->drivers_count ?? $this->drivers?->count(),
            // Đối chiếu cơ cấu đội xe: khai lúc đăng ký vs số xe thực tế đã thêm
            'declared_fleet_total' => $this->whenLoaded('partnerApplication', fn () => $this->partnerApplication?->vehicle_count ?? 0),
            'declared_fleet_summary' => $this->whenLoaded('partnerApplication', fn () => $this->partnerApplication?->fleetSummary() ?? '—'),
            'actual_vehicles_count' => $this->vehicles_count ?? 0,
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'drivers' => DriverResource::collection($this->whenLoaded('drivers')),
            'user' => [
                'full_name' => $this->user->full_name,
                'phone' => $this->user->phone,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
