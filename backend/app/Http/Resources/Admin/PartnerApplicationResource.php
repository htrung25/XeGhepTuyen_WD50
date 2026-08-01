<?php

namespace App\Http\Resources\Admin;

use App\Services\PrivateDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $documents = app(PrivateDocumentService::class);

        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'vehicle_count' => $this->vehicle_count,
            'fleet_breakdown' => $this->fleet_breakdown ?? [],
            'fleet_summary' => $this->fleetSummary(),
            'representative_name' => $this->representative_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'business_license_url' => $documents->temporaryUrl($this->business_license_path),
            'fleet_images' => collect($this->fleet_image_paths ?? [])
                ->map(fn (string $path): ?string => $documents->temporaryUrl($path))
                ->values()
                ->all(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'note' => $this->note,
            'operator_id' => $this->operator_id,
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
