<?php

namespace App\Http\Resources\Operator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date'            => $this->date,
            'total_bookings'  => $this->total_bookings,
            'revenue'         => $this->revenue,
            'formatted'       => number_format($this->revenue, 0, ',', '.') . 'đ',
        ];
    }
}
