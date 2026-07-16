<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefundBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền hoàn',
            'amount.integer' => 'Số tiền hoàn không hợp lệ',
            'amount.min' => 'Số tiền hoàn phải lớn hơn 0',
            'reason.required' => 'Vui lòng nhập lý do hoàn tiền',
            'reason.max' => 'Lý do tối đa 500 ký tự',
        ];
    }
}
