<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'booking_code'   => $this->booking?->booking_code,
            'payment_method' => $this->payment_method->value,
            'amount'         => $this->amount,
            'status'         => $this->status->value,
            'gateway_txn_id' => $this->gateway_txn_id,
            'is_refund'      => $this->is_refund ?? false,
            'paid_at'        => $this->paid_at?->format('Y-m-d H:i:s'),
            'user'           => [
                'full_name' => $this->booking?->user?->full_name,
                'phone'     => $this->booking?->user?->phone,
            ],
        ];
    }
}
