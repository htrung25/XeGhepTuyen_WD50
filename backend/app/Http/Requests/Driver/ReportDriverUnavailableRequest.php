<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class ReportDriverUnavailableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('driver')->check();
    }

    public function rules(): array
    {
        return [
            'issue_type' => ['sometimes', 'in:driver,vehicle'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'issue_type.in' => 'Loại sự cố không hợp lệ',
            'reason.required' => 'Vui lòng nhập lý do không chạy được chuyến',
            'reason.max' => 'Lý do tối đa 255 ký tự',
        ];
    }
}
