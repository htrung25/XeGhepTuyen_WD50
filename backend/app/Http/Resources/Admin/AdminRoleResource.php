<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'permissions' => $this->permissions ?? [],
            'is_super' => (bool) $this->is_super,
            'is_system' => (bool) $this->is_system,
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
