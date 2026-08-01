<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('driver')->check();
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['required', 'uuid', 'exists:trips,id'],
            'lat' => ['required', 'numeric', 'between:8,24'],
            'lng' => ['required', 'numeric', 'between:102,110'],
            'accuracy' => ['nullable', 'numeric', 'between:0,1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Vui lòng gửi vĩ độ',
            'lng.required' => 'Vui lòng gửi kinh độ',
            'trip_id.required' => 'Vui lòng gửi mã chuyến đang chạy',
            'lat.between' => 'Vĩ độ không hợp lệ (phạm vi Việt Nam: 8-24)',
            'lng.between' => 'Kinh độ không hợp lệ (phạm vi Việt Nam: 102-110)',
        ];
    }
}
