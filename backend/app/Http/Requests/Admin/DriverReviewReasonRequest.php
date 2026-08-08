<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DriverReviewReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do xử lý hồ sơ tài xế.',
            'reason.max' => 'Lý do không được vượt quá 500 ký tự.',
        ];
    }
}
