<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class LockSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'trip_id'   => ['required', 'uuid', 'exists:trips,id'],
            'seat_ids'  => ['required', 'array', 'min:1', 'max:4'],
            'seat_ids.*'=> ['uuid', 'exists:seat_maps,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required'  => 'Vui lòng chọn chuyến đi',
            'seat_ids.required' => 'Vui lòng chọn ghế',
            'seat_ids.max'      => 'Tối đa 4 ghế mỗi lần đặt',
        ];
    }
}
