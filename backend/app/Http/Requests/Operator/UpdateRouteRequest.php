<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'origin_city' => ['sometimes', 'string', 'max:100'],
            'dest_city' => ['sometimes', 'string', 'max:100'],
            'distance_km' => ['sometimes', 'integer', 'min:1', 'max:2000'],
            'est_duration_min' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'base_price' => ['sometimes', 'integer', 'min:50000', 'max:500000'],
            'is_round_trip' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
