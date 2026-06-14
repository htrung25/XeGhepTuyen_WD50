<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'full_name'    => $this->full_name,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'role'         => $this->role->value,
            'is_verified'  => $this->is_verified,
            'is_active'    => $this->is_active,
            'last_login_at'=> $this->last_login_at?->format('Y-m-d H:i:s'),
            'wallet_balance'=> $this->wallet?->balance,
            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
            'deleted_at'   => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
