<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class ReassignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            // CHỈ validate định dạng UUID — KHÔNG dùng exists:drivers,id để tránh lộ khác biệt
            // "không tồn tại" vs "khác nhà xe". Service trả lỗi nghiệp vụ A4 thống nhất.
            'driver_id' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'Vui lòng chọn tài xế thay thế',
            'driver_id.uuid' => 'Mã tài xế không hợp lệ',
        ];
    }
}
