<?php

namespace App\Http\Requests\Customer;

use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'uuid', 'exists:bookings,id'],
            'method' => ['required', Rule::in([
                PaymentMethodEnum::Momo->value,
                PaymentMethodEnum::Vnpay->value,
                PaymentMethodEnum::Wallet->value,
                PaymentMethodEnum::Cash->value,
            ])],
        ];
    }
}
