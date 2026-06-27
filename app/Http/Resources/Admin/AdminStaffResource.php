<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'is_active' => (bool) $this->is_active,
            'admin_role' => $this->adminRole ? [
                'id' => $this->adminRole->id,
                'name' => $this->adminRole->name,
                'slug' => $this->adminRole->slug,
                'is_super' => (bool) $this->adminRole->is_super,
            ] : null,
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
