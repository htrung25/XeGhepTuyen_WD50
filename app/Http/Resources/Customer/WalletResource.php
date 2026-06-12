<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'balance'   => $this->balance,
            'formatted' => number_format($this->balance, 0, ',', '.') . 'đ',
        ];
    }
}
