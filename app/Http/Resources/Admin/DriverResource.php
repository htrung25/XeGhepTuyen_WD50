<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'full_name'     => $this->user->full_name,
            'phone'         => $this->user->phone,
            'photo_url'     => $this->user->avatar_url,
            'operator_name' => $this->operator->company_name,
            'status'        => $this->status->value,
            'created_at'    => $this->created_at->format('Y-m-d H:i:s'),
            'documents'     => [
                'id_card_front'         => $this->id_card_front_url,
                'id_card_back'          => $this->id_card_back_url,
                'driver_license'        => $this->license_front_url,
                'driver_license_number' => $this->license_number,
                'driver_license_class'  => $this->license_class,
                'driver_license_expiry' => $this->license_expiry?->format('Y-m-d'),
            ],
        ];
    }
}
